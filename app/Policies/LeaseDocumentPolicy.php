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
     * Access is determined by the user's zone assignment. Prefer document's zone_id
     * to avoid N+1 when authorizing many documents; fall back to lease zone when needed.
     */
    private function hasAccess(User $user, LeaseDocument $document): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->hasZoneRestriction() || ! $user->zone_id) {
            return false;
        }

        // Use document's zone_id when set to avoid loading lease (N+1 and null-lease safe)
        if ($document->zone_id !== null) {
            return $document->zone_id === $user->zone_id;
        }

        $lease = $document->lease;

        return $lease && $lease->zone_id === $user->zone_id;
    }
}
