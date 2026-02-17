<?php

namespace App\Services;

use App\Models\LeaseAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class ActingDelegationService
{
    /**
     * The Spatie role name whose permissions are granted during delegation.
     */
    protected const DELEGATED_ROLE = 'zone_manager';

    /**
     * Activate delegation: grant a backup officer temporary zone-manager
     * permissions while the zone manager is away or on leave.
     *
     * @throws RuntimeException If no backup officer is configured.
     */
    public function activateDelegation(User $zoneManager): void
    {
        if (! $zoneManager->backup_officer_id) {
            Log::warning("ActingDelegation: Zone manager [{$zoneManager->id}] has no backup officer configured.");

            throw new RuntimeException(
                "Zone manager {$zoneManager->name} does not have a backup officer configured.",
            );
        }

        $backupOfficer = User::find($zoneManager->backup_officer_id);

        if (! $backupOfficer) {
            Log::warning("ActingDelegation: Backup officer [{$zoneManager->backup_officer_id}] for zone manager [{$zoneManager->id}] not found.");

            throw new RuntimeException(
                "Backup officer (ID: {$zoneManager->backup_officer_id}) could not be found.",
            );
        }

        // Prevent double-delegation: the backup officer is already acting for someone.
        if ($backupOfficer->acting_for_user_id !== null) {
            Log::warning("ActingDelegation: Backup officer [{$backupOfficer->id}] is already acting for user [{$backupOfficer->acting_for_user_id}].");

            throw new RuntimeException(
                "{$backupOfficer->name} is already acting for another user.",
            );
        }

        DB::transaction(function () use ($zoneManager, $backupOfficer) {
            // Mark the backup officer as acting for this zone manager.
            $backupOfficer->update([
                'acting_for_user_id' => $zoneManager->id,
            ]);

            // Grant zone-manager permissions via Spatie.
            $zoneManagerPermissions = $this->getZoneManagerPermissions();

            if (! empty($zoneManagerPermissions)) {
                $backupOfficer->givePermissionTo($zoneManagerPermissions);
            }

            // Clear Spatie's permission cache so changes take effect immediately.
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // Audit log entry.
            $this->logDelegationEvent(
                action: 'delegation_activated',
                description: "{$backupOfficer->name} is now acting for {$zoneManager->name}",
                userId: $zoneManager->id,
                additionalData: [
                    'zone_manager_id' => $zoneManager->id,
                    'zone_manager_name' => $zoneManager->name,
                    'backup_officer_id' => $backupOfficer->id,
                    'backup_officer_name' => $backupOfficer->name,
                    'permissions_granted' => $zoneManagerPermissions,
                ],
            );

            Log::info("ActingDelegation: Activated - {$backupOfficer->name} (ID:{$backupOfficer->id}) is now acting for {$zoneManager->name} (ID:{$zoneManager->id}).");
        });
    }

    /**
     * Deactivate delegation: revoke the temporary zone-manager permissions
     * and clear the acting relationship when the zone manager returns.
     */
    public function deactivateDelegation(User $zoneManager): void
    {
        $actingOfficer = User::where('acting_for_user_id', $zoneManager->id)->first();

        if (! $actingOfficer) {
            Log::info("ActingDelegation: No officer is currently acting for zone manager [{$zoneManager->id}]. Nothing to deactivate.");

            return;
        }

        DB::transaction(function () use ($zoneManager, $actingOfficer) {
            // Clear the acting relationship.
            $actingOfficer->update([
                'acting_for_user_id' => null,
            ]);

            // Sync back to the officer's original role permissions.
            // Remove all direct permissions, then re-apply only those from their own Spatie role.
            $this->syncToOriginalRolePermissions($actingOfficer);

            // Clear Spatie's permission cache.
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // Audit log entry.
            $this->logDelegationEvent(
                action: 'delegation_deactivated',
                description: "{$actingOfficer->name} is no longer acting for {$zoneManager->name}",
                userId: $zoneManager->id,
                additionalData: [
                    'zone_manager_id' => $zoneManager->id,
                    'zone_manager_name' => $zoneManager->name,
                    'officer_id' => $actingOfficer->id,
                    'officer_name' => $actingOfficer->name,
                ],
            );

            Log::info("ActingDelegation: Deactivated - {$actingOfficer->name} (ID:{$actingOfficer->id}) is no longer acting for {$zoneManager->name} (ID:{$zoneManager->id}).");
        });
    }

    /**
     * Orchestrator: handle a change in the user's availability status.
     *
     * Updates the status and triggers activation/deactivation of delegation
     * as appropriate.
     */
    public function handleAvailabilityChange(User $user, string $newStatus): void
    {
        $validStatuses = ['available', 'on_leave', 'away'];

        if (! in_array($newStatus, $validStatuses, true)) {
            throw new InvalidArgumentException(
                "Invalid availability status: {$newStatus}. Must be one of: " . implode(', ', $validStatuses),
            );
        }

        $previousStatus = $user->availability_status;

        // Update the availability status.
        $user->update([
            'availability_status' => $newStatus,
        ]);

        // Determine whether delegation should be activated or deactivated.
        $wasUnavailable = in_array($previousStatus, ['on_leave', 'away'], true);
        $isNowUnavailable = in_array($newStatus, ['on_leave', 'away'], true);
        $isNowAvailable = $newStatus === 'available';

        if ($isNowUnavailable && ! $wasUnavailable) {
            // User is going away -- activate delegation if they are a zone manager.
            if ($user->isZoneManager()) {
                $this->activateDelegation($user);
            }
        } elseif ($isNowAvailable && $wasUnavailable) {
            // User is returning -- deactivate delegation.
            if ($user->isZoneManager()) {
                $this->deactivateDelegation($user);
            }
        }

        Log::info("ActingDelegation: Availability for {$user->name} (ID:{$user->id}) changed from '{$previousStatus}' to '{$newStatus}'.");
    }

    /**
     * Get information about who a given user is currently acting for.
     *
     * Returns null if the user is not acting for anyone.
     *
     * @return array{acting_for_user_id: int, acting_for_user_name: string, acting_for_role: string, acting_since: string|null}|null
     */
    public function getActingInfo(User $user): ?array
    {
        if (! $user->acting_for_user_id) {
            return null;
        }

        $actingFor = User::find($user->acting_for_user_id);

        if (! $actingFor) {
            return null;
        }

        // Attempt to find the audit log that records when this delegation started.
        $activationLog = LeaseAuditLog::where('action', 'delegation_activated')
            ->whereJsonContains('additional_data->backup_officer_id', $user->id)
            ->whereJsonContains('additional_data->zone_manager_id', $actingFor->id)
            ->latest()
            ->first();

        return [
            'acting_for_user_id' => $actingFor->id,
            'acting_for_user_name' => $actingFor->name,
            'acting_for_role' => $actingFor->getRoleDisplayName(),
            'acting_since' => $activationLog?->created_at?->toDateTimeString(),
        ];
    }

    /**
     * Retrieve the list of permission names attached to the zone_manager
     * Spatie role.
     *
     * @return string[]
     */
    protected function getZoneManagerPermissions(): array
    {
        $role = SpatieRole::findByName(self::DELEGATED_ROLE);

        if (! $role) {
            Log::error('ActingDelegation: Spatie role "' . self::DELEGATED_ROLE . '" not found.');

            return [];
        }

        return $role->permissions->pluck('name')->toArray();
    }

    /**
     * Sync a user's direct permissions back to only those granted by their
     * original Spatie role (i.e., remove any extra direct permissions that
     * were added during delegation).
     */
    protected function syncToOriginalRolePermissions(User $user): void
    {
        // Revoke all direct (non-role) permissions.
        $directPermissions = $user->getDirectPermissions();

        if ($directPermissions->isNotEmpty()) {
            $user->revokePermissionTo($directPermissions);
        }
    }

    /**
     * Write a delegation event into the lease_audit_logs table.
     */
    protected function logDelegationEvent(
        string $action,
        string $description,
        ?int $userId = null,
        array $additionalData = [],
    ): void {
        try {
            LeaseAuditLog::create([
                'lease_id' => null,
                'action' => $action,
                'description' => $description,
                'user_id' => $userId,
                'user_role_at_time' => $userId ? User::find($userId)?->role : null,
                'ip_address' => request()->ip(),
                'additional_data' => $additionalData,
            ]);
        } catch (Throwable $e) {
            // Logging should never break the delegation flow.
            Log::error("ActingDelegation: Failed to write audit log - {$e->getMessage()}", [
                'action' => $action,
                'description' => $description,
            ]);
        }
    }
}
