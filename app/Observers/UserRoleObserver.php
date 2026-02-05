<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\RoleAuditLog;
use App\Models\User;

class UserRoleObserver
{
    /**
     * Handle role sync events from Spatie Permission
     */
    public static function onRolesSynced(User $user, array $oldRoles, array $newRoles): void
    {
        $oldRoleNames = collect($oldRoles)->pluck('name')->toArray();
        $newRoleNames = collect($newRoles)->pluck('name')->toArray();

        $addedRoles = array_diff($newRoleNames, $oldRoleNames);
        $removedRoles = array_diff($oldRoleNames, $newRoleNames);

        foreach ($addedRoles as $role) {
            RoleAuditLog::logRoleAssigned($user, $role);
        }

        foreach ($removedRoles as $role) {
            RoleAuditLog::logRoleRevoked($user, $role);
        }

        // If both added and removed, also log as a change
        if (!empty($addedRoles) && !empty($removedRoles)) {
            RoleAuditLog::logRoleChanged(
                $user,
                implode(', ', $oldRoleNames),
                implode(', ', $newRoleNames)
            );
        }
    }

    /**
     * Handle permission sync events
     */
    public static function onPermissionsSynced(User $user, array $oldPermissions, array $newPermissions): void
    {
        $oldPermNames = collect($oldPermissions)->pluck('name')->toArray();
        $newPermNames = collect($newPermissions)->pluck('name')->toArray();

        if ($oldPermNames !== $newPermNames) {
            RoleAuditLog::logPermissionChange(
                $user,
                RoleAuditLog::ACTION_PERMISSION_SYNCED,
                $oldPermNames,
                $newPermNames
            );
        }
    }
}
