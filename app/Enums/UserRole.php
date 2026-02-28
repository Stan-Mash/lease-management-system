<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Canonical enum for all user roles in the Chabrin RBAC system.
 *
 * Before this enum existed, role names were hardcoded as magic strings
 * scattered across 15+ files (middleware, policies, resource guards, etc.).
 * A typo in any one of those strings would silently break access control.
 *
 * Usage:
 *   $user->hasRole(UserRole::SuperAdmin->value)  // instead of 'super_admin'
 *   $user->assignRole(UserRole::FieldOfficer->value)
 *   UserRole::from('zone_manager')                // safe parsing
 */
enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case ZoneManager = 'zone_manager';
    case FieldOfficer = 'field_officer';
    case Lawyer = 'lawyer';
    case LandlordUser = 'landlord_user';
    case ReadOnly = 'read_only';

    /**
     * Human-readable label for UI display.
     */
    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Administrator',
            self::Admin => 'Administrator',
            self::ZoneManager => 'Zone Manager',
            self::FieldOfficer => 'Field Officer',
            self::Lawyer => 'Lawyer',
            self::LandlordUser => 'Landlord',
            self::ReadOnly => 'Read Only',
        };
    }

    /**
     * Whether this role can create or manage lease records.
     */
    public function canManageLeases(): bool
    {
        return in_array($this, [
            self::SuperAdmin,
            self::Admin,
            self::ZoneManager,
            self::FieldOfficer,
        ], strict: true);
    }

    /**
     * Whether this role has access to the full admin panel.
     */
    public function isAdminRole(): bool
    {
        return in_array($this, [
            self::SuperAdmin,
            self::Admin,
        ], strict: true);
    }

    /**
     * Whether this role is scoped to a specific zone.
     */
    public function isZoneScoped(): bool
    {
        return in_array($this, [
            self::ZoneManager,
            self::FieldOfficer,
        ], strict: true);
    }

    /**
     * Return all values as a plain array — useful for Filament Select options.
     */
    public static function options(): array
    {
        return array_column(
            array_map(fn (self $role) => ['value' => $role->value, 'label' => $role->label()], self::cases()),
            'label',
            'value',
        );
    }
}
