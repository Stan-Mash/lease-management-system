<?php

namespace App\Services;

use App\Models\Lease;
use Exception;
use Illuminate\Support\Facades\DB;

class LeaseReferenceService
{
    /**
     * Source code mapping.
     *
     * Maps internal source identifiers to their short codes used in reference numbers.
     */
    private static array $sourceCodes = [
        'landlord_provided' => 'LL',
        'chabrin_issued' => 'CH',
    ];

    /**
     * Lease type code mapping per source.
     *
     * Defines which lease types are valid for each source and their corresponding
     * short codes used in reference numbers.
     */
    private static array $typeCodesBySource = [
        'landlord_provided' => [
            'residential' => 'RES',
            'commercial' => 'COM',
        ],
        'chabrin_issued' => [
            'commercial' => 'COM',
            'residential_major' => 'MAJ',
            'residential_micro' => 'MIC',
        ],
    ];

    /**
     * Generate a race-condition safe lease reference number.
     *
     * Format: [SOURCE]-[TYPE]-[UNIT_CODE]-[YEAR]-[SEQ]
     * Example: CH-MAC-484A-001-2024-052
     *
     * Uses database row locking to prevent duplicate sequences
     * even under concurrent requests. Sequences are keyed by
     * year + lease_type combination.
     *
     * @param string $source The source ('landlord_provided' or 'chabrin_issued')
     * @param string $leaseType The lease type (depends on source)
     * @param string $unitCode The unit code (e.g., '484A-001')
     * @param int|null $year Optional year (defaults to current year)
     *
     * @throws Exception If source or lease type is invalid
     *
     * @return string The generated reference number
     */
    public static function generate(string $source, string $leaseType, string $unitCode, ?int $year = null): string
    {
        // Validate source
        if (! isset(self::$sourceCodes[$source])) {
            $validSources = implode(', ', array_keys(self::$sourceCodes));

            throw new Exception("Invalid source: {$source}. Valid sources are: {$validSources}");
        }

        // Validate lease type for this source
        if (! isset(self::$typeCodesBySource[$source][$leaseType])) {
            $validTypes = implode(', ', array_keys(self::$typeCodesBySource[$source]));

            throw new Exception(
                "Invalid lease type '{$leaseType}' for source '{$source}'. Valid types are: {$validTypes}",
            );
        }

        // Default to current year if not specified
        $year = $year ?? now()->year;

        // Use database transaction with row locking to prevent race conditions
        return DB::transaction(function () use ($source, $leaseType, $unitCode, $year) {
            // Lock the row for this year/lease_type combination
            // Sequence is shared across all units for a given year + type
            $sequence = DB::table('lease_sequences')
                ->where('year', $year)
                ->where('lease_type', $leaseType)
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                // First lease of this type in this year
                DB::table('lease_sequences')->insert([
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

            // Build reference number components
            $sourceCode = self::$sourceCodes[$source];
            $typeCode = self::$typeCodesBySource[$source][$leaseType];
            $sequencePadded = str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT);

            return "{$sourceCode}-{$typeCode}-{$unitCode}-{$year}-{$sequencePadded}";
        });
    }

    /**
     * Generate a lease reference number from a Lease model instance.
     *
     * Auto-determines source, lease type, and unit code from the Lease
     * and its related Unit model.
     *
     * @param Lease $lease The lease model instance
     * @param int|null $year Optional year override (defaults to current year)
     *
     * @throws Exception If the lease is missing required fields or relationships
     *
     * @return string The generated reference number
     */
    public static function generateForLease(Lease $lease, ?int $year = null): string
    {
        // Validate source is present on the lease
        if (empty($lease->source)) {
            throw new Exception('Lease is missing the source field.');
        }

        // Validate lease_type is present on the lease
        if (empty($lease->lease_type)) {
            throw new Exception('Lease is missing the lease_type field.');
        }

        // Determine unit code: prefer the lease's own unit_code field,
        // fall back to the related Unit model's unit_code
        $unitCode = $lease->unit_code;

        if (empty($unitCode) && $lease->unit) {
            $unitCode = $lease->unit->unit_code;
        }

        if (empty($unitCode)) {
            throw new Exception('Unable to determine unit code. Ensure the lease has a unit_code or a related Unit.');
        }

        return self::generate($lease->source, $lease->lease_type, $unitCode, $year);
    }

    /**
     * Get the current sequence number for a year/lease_type combination
     * without incrementing it.
     *
     * @param string $leaseType The lease type key
     * @param int|null $year Optional year (defaults to current year)
     *
     * @return int Current sequence number (0 if no leases yet)
     */
    public static function getCurrentSequence(string $leaseType, ?int $year = null): int
    {
        $year = $year ?? now()->year;

        $sequence = DB::table('lease_sequences')
            ->where('year', $year)
            ->where('lease_type', $leaseType)
            ->value('last_sequence');

        return $sequence ?? 0;
    }

    /**
     * Reset sequence for a year/lease_type combination.
     *
     * USE WITH CAUTION - Only for testing or year-end resets.
     *
     * @param string $leaseType The lease type key
     * @param int|null $year Optional year (defaults to current year)
     *
     * @return bool True if a row was updated, false otherwise
     */
    public static function resetSequence(string $leaseType, ?int $year = null): bool
    {
        $year = $year ?? now()->year;

        return DB::table('lease_sequences')
            ->where('year', $year)
            ->where('lease_type', $leaseType)
            ->update(['last_sequence' => 0, 'updated_at' => now()]) > 0;
    }

    /**
     * Get statistics for all lease type sequences.
     *
     * Returns all sequence records for the given year, ordered by lease type.
     *
     * @param int|null $year Optional year (defaults to current year)
     */
    public static function getStatistics(?int $year = null): \Illuminate\Support\Collection
    {
        $year = $year ?? now()->year;

        return DB::table('lease_sequences')
            ->where('year', $year)
            ->orderBy('lease_type')
            ->get();
    }
}
