<?php

declare(strict_types=1);

namespace App\Helpers;

use DivisionByZeroError;

/**
 * BCMath-based money arithmetic for Kenyan Shillings (KES).
 *
 * Why BCMath instead of native PHP floats?
 * -----------------------------------------
 * PHP floats use IEEE 754 binary floating point. This means:
 *   0.1 + 0.2 === 0.30000000000000004   (not 0.30)
 *   50000 * 1.055 === 52750.000000000007 (not 52750.00)
 *
 * Over hundreds of months of rent escalations and arrears calculations,
 * these rounding errors compound. BCMath uses arbitrary-precision decimal
 * arithmetic and is the correct choice for any financial calculation.
 *
 * All methods accept and return string values to prevent float conversion.
 * Use Money::format() only for display, never for further calculations.
 *
 * Usage:
 *   $newRent = Money::escalate('50000.00', '5.5');  // → '52750.00'
 *   $arrears = Money::arrears('50000.00', '45000.00'); // → '5000.00'
 *   echo Money::format('52750.00'); // → 'KES 52,750.00'
 */
class Money
{
    /** Decimal places for KES (Kenya Shillings: 2 decimal places) */
    private const SCALE = 2;

    /** Internal precision for intermediate calculations (prevents rounding mid-calculation) */
    private const INTERNAL_SCALE = 8;

    /**
     * Add two monetary amounts.
     *
     * @param string $a Monetary amount (e.g. '50000.00')
     * @param string $b Monetary amount to add
     */
    public static function add(string $a, string $b): string
    {
        return bcadd($a, $b, self::SCALE);
    }

    /**
     * Subtract b from a.
     */
    public static function subtract(string $a, string $b): string
    {
        return bcsub($a, $b, self::SCALE);
    }

    /**
     * Multiply a monetary amount by a factor.
     *
     * @param string $amount Monetary amount (e.g. '50000.00')
     * @param string $factor Multiplication factor (e.g. '1.055' for 5.5% increase)
     */
    public static function multiply(string $amount, string $factor): string
    {
        return bcmul($amount, $factor, self::SCALE);
    }

    /**
     * Divide a monetary amount by a divisor.
     *
     * @param string $amount Monetary amount
     * @param string $divisor Divisor (must not be zero)
     *
     * @throws DivisionByZeroError
     */
    public static function divide(string $amount, string $divisor): string
    {
        if (bccomp($divisor, '0', self::INTERNAL_SCALE) === 0) {
            throw new DivisionByZeroError('Cannot divide monetary amount by zero.');
        }

        return bcdiv($amount, $divisor, self::SCALE);
    }

    /**
     * Apply a percentage escalation to a rent amount.
     *
     * Example: escalate('50000.00', '5.5') → '52750.00'
     *
     * @param string $currentRent Current monthly rent as string (e.g. '50000.00')
     * @param string $percentageRate Escalation percentage (e.g. '5.5' for 5.5%)
     */
    public static function escalate(string $currentRent, string $percentageRate): string
    {
        // factor = 1 + (rate / 100)
        // Use INTERNAL_SCALE for intermediate division to preserve precision
        $divisor = bcdiv($percentageRate, '100', self::INTERNAL_SCALE);
        $factor = bcadd('1', $divisor, self::INTERNAL_SCALE);

        return bcmul($currentRent, $factor, self::SCALE);
    }

    /**
     * Calculate arrears (amount charged minus amount paid).
     * Returns '0.00' if paid >= charged (no arrears, possibly a credit).
     *
     * @param string $charged Total amount due
     * @param string $paid Amount actually received
     */
    public static function arrears(string $charged, string $paid): string
    {
        $diff = bcsub($charged, $paid, self::SCALE);

        // Negative diff means overpayment (credit) — return zero arrears
        return bccomp($diff, '0', self::SCALE) > 0 ? $diff : '0.00';
    }

    /**
     * Compare two monetary amounts.
     *
     * @return int -1 if a < b, 0 if a == b, 1 if a > b
     */
    public static function compare(string $a, string $b): int
    {
        return bccomp($a, $b, self::SCALE);
    }

    /**
     * Check if an amount is zero.
     */
    public static function isZero(string $amount): bool
    {
        return bccomp($amount, '0', self::SCALE) === 0;
    }

    /**
     * Check if an amount is positive (greater than zero).
     */
    public static function isPositive(string $amount): bool
    {
        return bccomp($amount, '0', self::SCALE) > 0;
    }

    /**
     * Format a monetary amount for display only.
     * NEVER use this value in further calculations — it is for UI output only.
     *
     * @param string $amount Monetary amount as string
     * @param string $currency Currency prefix (default: 'KES')
     */
    public static function format(string $amount, string $currency = 'KES'): string
    {
        return $currency . ' ' . number_format((float) $amount, 2, '.', ',');
    }

    /**
     * Safely convert a float or int to a BCMath-compatible string.
     * Use this at application boundaries (e.g. reading from a form input).
     */
    public static function of(float|int|string $value): string
    {
        return number_format((float) $value, self::SCALE, '.', '');
    }
}
