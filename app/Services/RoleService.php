<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;

class RoleService
{
    /**
     * Get all available roles
     */
    public static function getRoles(): array
    {
        return Config::get('roles.roles', []);
    }

    /**
     * Get role options for select dropdown
     */
    public static function getRoleOptions(): array
    {
        $roles = self::getRoles();
        $options = [];

        foreach ($roles as $key => $role) {
            $options[$key] = $role['name'];
        }

        return $options;
    }

    /**
     * Get role display name
     */
    public static function getRoleName(string $role): string
    {
        $roles = self::getRoles();
        return $roles[$role]['name'] ?? ucfirst($role);
    }

    /**
     * Get role description
     */
    public static function getRoleDescription(string $role): string
    {
        $roles = self::getRoles();
        return $roles[$role]['description'] ?? '';
    }

    /**
     * Get role color for badges
     */
    public static function getRoleColor(string $role): string
    {
        $roles = self::getRoles();
        return $roles[$role]['color'] ?? 'gray';
    }

    /**
     * Get role permissions
     */
    public static function getRolePermissions(string $role): array
    {
        $roles = self::getRoles();
        return $roles[$role]['permissions'] ?? [];
    }

    /**
     * Check if a role exists
     */
    public static function roleExists(string $role): bool
    {
        return array_key_exists($role, self::getRoles());
    }

    /**
     * Get default role
     */
    public static function getDefaultRole(): string
    {
        return Config::get('roles.default_role', 'staff');
    }

    /**
     * Get roles that a given role can manage
     */
    public static function getManagedRoles(string $role): array
    {
        $hierarchy = Config::get('roles.hierarchy', []);
        return $hierarchy[$role] ?? [];
    }

    /**
     * Check if role A can manage role B
     */
    public static function canManageRole(string $managerRole, string $targetRole): bool
    {
        $managedRoles = self::getManagedRoles($managerRole);
        return in_array($targetRole, $managedRoles);
    }

    /**
     * Get filterable role options based on current user's role
     */
    public static function getFilteredRoleOptions(?string $currentUserRole = null): array
    {
        if (!$currentUserRole) {
            $currentUserRole = auth()->user()?->role ?? 'viewer';
        }

        $allRoles = self::getRoles();
        $managedRoles = self::getManagedRoles($currentUserRole);

        if (empty($managedRoles)) {
            // If no hierarchy defined, return all roles
            return self::getRoleOptions();
        }

        $options = [];
        foreach ($managedRoles as $roleKey) {
            if (isset($allRoles[$roleKey])) {
                $options[$roleKey] = $allRoles[$roleKey]['name'];
            }
        }

        return $options;
    }
}
