<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\PreferredLanguage;
use App\Models\Tenant;

/**
 * Tenant-level localization for lease-related strings (SMS, email, portal).
 * Uses the tenant's preferred_language; does not change app locale globally.
 */
class LocaleHelper
{
    public static function forTenant(Tenant $tenant, string $key, array $replace = []): string
    {
        $locale = self::tenantLocale($tenant);

        return (string) __('lease.' . $key, $replace, $locale);
    }

    public static function tenantLocale(Tenant $tenant): string
    {
        $pref = $tenant->preferred_language ?? null;
        if ($pref instanceof PreferredLanguage) {
            return $pref->value;
        }
        if (is_string($pref) && str_starts_with(strtolower($pref), 'sw')) {
            return 'sw';
        }

        return 'en';
    }
}
