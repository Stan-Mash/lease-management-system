<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Supported languages for tenant communication (SMS, Email).
 * Focused on Kenyan market: English and Swahili.
 */
enum PreferredLanguage: string implements HasLabel
{
    case ENGLISH = 'en';
    case SWAHILI = 'sw';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ENGLISH => 'English',
            self::SWAHILI => 'Kiswahili',
        };
    }

    /**
     * Get the locale code for Laravel's localization.
     */
    public function getLocale(): string
    {
        return $this->value;
    }

    /**
     * Get native language name for display to tenants.
     */
    public function getNativeName(): string
    {
        return match ($this) {
            self::ENGLISH => 'English',
            self::SWAHILI => 'Kiswahili',
        };
    }

    /**
     * Get options array for form selects.
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])
            ->all();
    }

    /**
     * Get the default language.
     */
    public static function default(): self
    {
        return self::ENGLISH;
    }
}
