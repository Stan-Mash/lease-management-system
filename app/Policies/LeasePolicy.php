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
     * - Zone managers and field officers can only access leases in their zone.
     * - All other users are denied access (safety net).
     */
    private function hasAccess(User $user, Lease $lease): bool
    {
        // Super admins and regular admins can see all leases
        if ($user->isAdmin()) {
            return true;
        }

        // Zone-restricted users can only see leases in their zone
        if ($user->hasZoneRestriction() && $user->zone_id) {
            return $lease->zone_id === $user->zone_id;
        }

        // Default: no access (safety net)
        return false;
    }
}
