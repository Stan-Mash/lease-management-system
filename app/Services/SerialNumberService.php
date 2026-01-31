<?php

namespace App\Services;

use App\Models\Lease;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Service for generating unique serial numbers for documents
 * Format: LSE-{YEAR}-{SEQUENCE}
 * Example: LSE-2026-0001, LSE-2026-0002, etc.
 */
class SerialNumberService
{
    /**
     * Generate a unique serial number for a lease
     *
     * @param string $prefix Default prefix (LSE = Lease)
     */
    public static function generate(string $prefix = 'LSE'): string
    {
        $year = date('Y');

        // Get the highest serial number for this year
        $lastLease = Lease::where('serial_number', 'like', "{$prefix}-{$year}-%")
            ->orderBy('serial_number', 'desc')
            ->first();

        if ($lastLease && preg_match('/-(\d+)$/', $lastLease->serial_number, $matches)) {
            $sequence = intval($matches[1]) + 1;
        } else {
            $sequence = 1;
        }

        // Format: LSE-2026-0001
        return sprintf('%s-%s-%04d', $prefix, $year, $sequence);
    }

    /**
     * Generate unique serial number with transaction lock to prevent duplicates
     */
    public static function generateUnique(string $prefix = 'LSE'): string
    {
        return DB::transaction(function () use ($prefix) {
            $serialNumber = self::generate($prefix);

            // Double-check uniqueness
            $attempts = 0;
            while (Lease::where('serial_number', $serialNumber)->exists() && $attempts < 10) {
                $attempts++;
                $serialNumber = self::generate($prefix);
            }

            if ($attempts >= 10) {
                throw new Exception('Failed to generate unique serial number after 10 attempts');
            }

            return $serialNumber;
        });
    }

    /**
     * Validate serial number format
     */
    public static function isValid(string $serialNumber): bool
    {
        return preg_match('/^[A-Z]+-\d{4}-\d{4}$/', $serialNumber) === 1;
    }

    /**
     * Parse serial number into components
     *
     * @return array{prefix: string, year: string, sequence: string}|null
     */
    public static function parse(string $serialNumber): ?array
    {
        if (! self::isValid($serialNumber)) {
            return null;
        }

        $parts = explode('-', $serialNumber);

        return [
            'prefix' => $parts[0],
            'year' => $parts[1],
            'sequence' => $parts[2],
        ];
    }
}
