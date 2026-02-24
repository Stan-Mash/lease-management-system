<?php

namespace App\Policies;

use App\Models\Lease;
use App\Models\User;

/**
 * Authorization policy for Lease model.
 *
 * Mirrors the access logic from Lease::scopeAccessibleByUser() to ensure
 * consistent authorization across API endpoints and query scopes.
 */
class LeasePolicy
{
    /**
     * Determine if the user can view the lease.
     */
    public function view(User $user, Lease $lease): bool
    {
        return $this->hasAccess($user, $lease);
    }

    /**
     * Determine if the user can update the lease.
     */
    public function update(User $user, Lease $lease): bool
    {
        return $this->hasAccess($user, $lease);
    }

    /**
     * Check if a user has access to a specific lease.
     *
     * - Super admins and admins can access all leases.
     * - Field officers can access leases assigned to them (assigned_field_officer_id).
     * - Other zone-restricted users (zone managers, auditors) can access leases in their zone.
     * - All other users are denied access (safety net).
     */
    private function hasAccess(User $user, Lease $lease): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Field officers: only leases assigned to them (matches FieldOfficerController scopedLeaseQuery)
        if ($user->isFieldOfficer()) {
            return $lease->assigned_field_officer_id === $user->id;
        }

        // Zone managers, auditors, etc.: leases in their zone
        if ($user->hasZoneRestriction() && $user->zone_id) {
            return $lease->zone_id === $user->zone_id;
        }

        return false;
    }
}
