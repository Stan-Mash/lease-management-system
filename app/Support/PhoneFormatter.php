<?php

namespace App\Support;

/**
 * Utility class for formatting phone numbers.
 * Centralizes phone number formatting logic used across the application.
 */
class PhoneFormatter
{
    /**
     * Default country code (Kenya).
     */
    public const DEFAULT_COUNTRY_CODE = '254';

    /**
     * Format a phone number to international format.
     *
     * @param string $phone The phone number to format
     * @param string $countryCode The country code to use (default: Kenya +254)
     * @return string The formatted phone number with + prefix
     */
    public static function toInternational(string $phone, string $countryCode = self::DEFAULT_COUNTRY_CODE): string
    {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // If already has + prefix and looks valid, return as-is
        if (str_starts_with($phone, '+') && strlen($phone) >= 10) {
            return $phone;
        }

        // Remove + if present (we'll add it back)
        $phone = ltrim($phone, '+');

        // If starts with 0, replace with country code
        if (str_starts_with($phone, '0')) {
            $phone = $countryCode . substr($phone, 1);
        }

        // If doesn't start with country code, prepend it
        if (!str_starts_with($phone, $countryCode)) {
            $phone = $countryCode . $phone;
        }

        return '+' . $phone;
    }

    /**
     * Format phone number for display (with spaces).
     *
     * @param string $phone The phone number
     * @return string Formatted for display (e.g., +254 712 345 678)
     */
    public static function forDisplay(string $phone): string
    {
        $formatted = self::toInternational($phone);

        // Format as +XXX XXX XXX XXX
        if (strlen($formatted) >= 13) {
            return substr($formatted, 0, 4) . ' ' .
                   substr($formatted, 4, 3) . ' ' .
                   substr($formatted, 7, 3) . ' ' .
                   substr($formatted, 10);
        }

        return $formatted;
    }

    /**
     * Mask a phone number for privacy (e.g., in logs).
     *
     * @param string $phone The phone number
     * @return string Masked phone (e.g., +254****678)
     */
    public static function mask(string $phone): string
    {
        $formatted = self::toInternational($phone);

        if (strlen($formatted) < 8) {
            return '****' . substr($formatted, -2);
        }

        return substr($formatted, 0, 4) . '****' . substr($formatted, -3);
    }

    /**
     * Validate if a phone number looks valid.
     *
     * @param string $phone The phone number to validate
     * @return bool True if the phone number appears valid
     */
    public static function isValid(string $phone): bool
    {
        $formatted = self::toInternational($phone);

        // Should be between 10 and 15 digits (international standard)
        $digits = preg_replace('/[^0-9]/', '', $formatted);

        return strlen($digits) >= 10 && strlen($digits) <= 15;
    }

    /**
     * Extract country code from a phone number.
     *
     * @param string $phone The phone number
     * @return string|null The country code or null if not found
     */
    public static function extractCountryCode(string $phone): ?string
    {
        $formatted = self::toInternational($phone);

        // Common country code lengths are 1-3 digits
        // For Kenya, it's 254
        if (str_starts_with($formatted, '+254')) {
            return '254';
        }

        // Try to extract first 1-3 digits after +
        if (preg_match('/^\+(\d{1,3})/', $formatted, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
