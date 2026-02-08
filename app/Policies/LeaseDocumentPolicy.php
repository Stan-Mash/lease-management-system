<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LeaseDocument;
use App\Models\User;

/**
 * Authorization policy for LeaseDocument model.
 *
 * Enforces zone-based access control for document operations.
 */
class LeaseDocumentPolicy
{
    /**
     * Determine if the user can view any documents.
     */
    public function viewAny(User $user): bool
    {
        return true; // Filtered via query scope
    }

    /**
     * Determine if the user can view the document.
     */
    public function view(User $user, LeaseDocument $document): bool
    {
        return $this->hasAccess($user, $document);
    }

    /**
     * Determine if the user can download the document.
     */
    public function download(User $user, LeaseDocument $document): bool
    {
        return $this->hasAccess($user, $document);
    }

    /**
     * Determine if the user can create documents.
     */
    public function create(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Zone managers and field officers can upload documents
        return $user->hasAnyRole(['zone_manager', 'field_officer', 'manager']);
    }

    /**
     * Determine if the user can update the document.
     */
    public function update(User $user, LeaseDocument $document): bool
    {
        return $this->hasAccess($user, $document);
    }

    /**
     * Determine if the user can delete the document.
     */
    public function delete(User $user, LeaseDocument $document): bool
    {
        // Only admins can delete documents
        return $user->isAdmin();
    }

    /**
     * Check if a user has access to a specific document.
     *
     * Access is determined by the user's zone assignment relative
     * to the lease's zone that the document belongs to.
     */
    private function hasAccess(User $user, LeaseDocument $document): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Zone-restricted users can only access documents for leases in their zone
        if ($user->hasZoneRestriction() && $user->zone_id) {
            $lease = $document->lease;

            return $lease && $lease->zone_id === $user->zone_id;
        }

        return false;
    }
}
