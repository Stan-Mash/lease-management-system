<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum representing valid dispute reasons for lease rejection by tenants.
 */
enum DisputeReason: string
{
    case RENT_TOO_HIGH = 'rent_too_high';
    case WRONG_DATES = 'wrong_dates';
    case INCORRECT_DETAILS = 'incorrect_details';
    case TERMS_DISAGREEMENT = 'terms_disagreement';
    case NOT_MY_LEASE = 'not_my_lease';
    case OTHER = 'other';

    /**
     * Get human-readable label for this reason.
     */
    public function label(): string
    {
        return match ($this) {
            self::RENT_TOO_HIGH => 'Rent Amount Too High',
            self::WRONG_DATES => 'Incorrect Lease Dates',
            self::INCORRECT_DETAILS => 'Incorrect Personal/Property Details',
            self::TERMS_DISAGREEMENT => 'Disagreement with Terms & Conditions',
            self::NOT_MY_LEASE => 'This is Not My Lease',
            self::OTHER => 'Other Reason',
        };
    }

    /**
     * Get all values as a validation rule string.
     */
    public static function validationRule(): string
    {
        return 'in:' . implode(',', array_column(self::cases(), 'value'));
    }

    /**
     * Get all reasons as options for form selects.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
