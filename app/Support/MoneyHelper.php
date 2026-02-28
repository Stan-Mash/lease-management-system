<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Centralized monetary value handling for consistent rounding and display.
 * See docs/FINANCIAL_POLICY.md for policy.
 *
 * For high-precision calculations (e.g. bulk arrears, multi-step formulas),
 * use the bcmath methods (add, sub, mul, div) which require the PHP bcmath extension.
 */
class MoneyHelper
{
    /**
     * Default number of decimal places for currency (KES).
     */
    public const DECIMAL_PLACES = 2;

    /**
     * Round a monetary value to the standard decimal places.
     * Use this for all financial calculations to avoid inconsistent rounding.
     */
    public static function round(float|string|null $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return round((float) $value, self::DECIMAL_PLACES);
    }

    /**
     * Format a monetary value for display (2 decimal places, no symbol).
     */
    public static function format(float|string|null $value): string
    {
        return number_format(self::round($value), self::DECIMAL_PLACES);
    }

    /**
     * Format with currency code (e.g. "1,234.56 KES").
     */
    public static function formatWithCurrency(float|string|null $value, string $currency = 'KES'): string
    {
        return self::format($value) . ' ' . $currency;
    }

    /**
     * Add two monetary values using bcmath (no float rounding errors).
     * Requires PHP bcmath extension.
     */
    public static function add(float|string|null $a, float|string|null $b): string
    {
        $a = self::normalizeForBc($a);
        $b = self::normalizeForBc($b);

        return bcadd($a, $b, self::scale());
    }

    /**
     * Subtract b from a using bcmath.
     */
    public static function sub(float|string|null $a, float|string|null $b): string
    {
        $a = self::normalizeForBc($a);
        $b = self::normalizeForBc($b);

        return bcsub($a, $b, self::scale());
    }

    /**
     * Multiply two monetary values using bcmath.
     */
    public static function mul(float|string|null $a, float|string|null $b): string
    {
        $a = self::normalizeForBc($a);
        $b = self::normalizeForBc($b);

        return bcmul($a, $b, self::scale());
    }

    /**
     * Divide a by b using bcmath. b must not be zero.
     */
    public static function div(float|string|null $a, float|string|null $b): string
    {
        $a = self::normalizeForBc($a);
        $b = self::normalizeForBc($b);
        if (bccomp($b, '0', self::scale()) === 0) {
            return '0';
        }

        return bcdiv($a, $b, self::scale());
    }

    /**
     * Apply a percentage rate to an amount (e.g. escalation: amount * (1 + rate)).
     * Uses bcmath for precision. Rate is decimal (e.g. 0.10 for 10%).
     */
    public static function applyRate(float|string|null $amount, float|string $rate): string
    {
        $amount = self::normalizeForBc($amount);
        $rate = (string) $rate;
        $onePlusRate = bcadd('1', $rate, 6);

        return bcmul($amount, $onePlusRate, self::scale());
    }

    /**
     * Scale used for bcmath operations (decimal places).
     */
    private static function scale(): int
    {
        return self::DECIMAL_PLACES;
    }

    /**
     * Normalize value for bcmath (string with correct decimal places).
     */
    private static function normalizeForBc(float|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }
        $value = (string) $value;
        if (! is_numeric($value)) {
            return '0';
        }

        return sprintf('%.' . self::scale() . 'F', (float) $value);
    }
}
