<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class LeaseReferenceService
{
    /**
     * Type code mapping as per SRS Section 2.2
     */
    private static array $typeCodes = [
        'commercial' => 'COM',
        'residential_micro' => 'RMI',
        'residential_major' => 'RMA',
        'landlord_provided' => 'LAN',
    ];

    /**
     * Generate a race-condition safe lease reference number.
     *
     * Format: LSE-{TYPE}-{ZONE}-{SEQUENCE}-{YEAR}
     * Example: LSE-COM-A-00142-2026
     *
     * Uses database row locking to prevent duplicate sequences
     * even under concurrent requests.
     *
     * @param string $leaseType The lease type (commercial, residential_micro, etc.)
     * @param string $zone The zone letter (A-G)
     * @param int|null $year Optional year (defaults to current year)
     * @return string The generated reference number
     * @throws \Exception If lease type is invalid
     */
    public static function generate(string $leaseType, string $zone, ?int $year = null): string
    {
        // Validate lease type
        if (!isset(self::$typeCodes[$leaseType])) {
            throw new \Exception("Invalid lease type: {$leaseType}");
        }

        // Default to current year if not specified
        $year = $year ?? now()->year;

        // Normalize zone to uppercase
        $zone = strtoupper($zone);

        // Use database transaction with row locking to prevent race conditions
        return DB::transaction(function () use ($leaseType, $zone, $year) {
            // Lock the row for this zone/year/type combination
            // If row doesn't exist, create it
            $sequence = DB::table('lease_sequences')
                ->where('zone', $zone)
                ->where('year', $year)
                ->where('lease_type', $leaseType)
                ->lockForUpdate() // Exclusive lock - prevents concurrent access
                ->first();

            if ($sequence === null) {
                // First lease of this type in this zone/year
                DB::table('lease_sequences')->insert([
                    'zone' => $zone,
                    'year' => $year,
                    'lease_type' => $leaseType,
                    'last_sequence' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $nextSequence = 1;
            } else {
                // Increment sequence
                $nextSequence = $sequence->last_sequence + 1;

                DB::table('lease_sequences')
                    ->where('id', $sequence->id)
                    ->update([
                        'last_sequence' => $nextSequence,
                        'updated_at' => now(),
                    ]);
            }

            // Generate reference number
            // Format: LSE-{TYPE_CODE}-{ZONE}-{SEQUENCE_PADDED}-{YEAR}
            $typeCode = self::$typeCodes[$leaseType];
            $sequencePadded = str_pad($nextSequence, 5, '0', STR_PAD_LEFT);

            return "LSE-{$typeCode}-{$zone}-{$sequencePadded}-{$year}";
        });
    }

    /**
     * Get the current sequence number for a zone/year/type combination
     * without incrementing it.
     *
     * @param string $leaseType
     * @param string $zone
     * @param int|null $year
     * @return int Current sequence number (0 if no leases yet)
     */
    public static function getCurrentSequence(string $leaseType, string $zone, ?int $year = null): int
    {
        $year = $year ?? now()->year;
        $zone = strtoupper($zone);

        $sequence = DB::table('lease_sequences')
            ->where('zone', $zone)
            ->where('year', $year)
            ->where('lease_type', $leaseType)
            ->value('last_sequence');

        return $sequence ?? 0;
    }

    /**
     * Reset sequence for a zone/year/type combination.
     * USE WITH CAUTION - Only for testing or year-end resets.
     *
     * @param string $leaseType
     * @param string $zone
     * @param int|null $year
     * @return bool
     */
    public static function resetSequence(string $leaseType, string $zone, ?int $year = null): bool
    {
        $year = $year ?? now()->year;
        $zone = strtoupper($zone);

        return DB::table('lease_sequences')
            ->where('zone', $zone)
            ->where('year', $year)
            ->where('lease_type', $leaseType)
            ->update(['last_sequence' => 0]) > 0;
    }

    /**
     * Get statistics for all zones and types.
     *
     * @param int|null $year
     * @return \Illuminate\Support\Collection
     */
    public static function getStatistics(?int $year = null): \Illuminate\Support\Collection
    {
        $year = $year ?? now()->year;

        return DB::table('lease_sequences')
            ->where('year', $year)
            ->orderBy('zone')
            ->orderBy('lease_type')
            ->get();
    }
}
