<?php

namespace App\Policies;

use App\Models\LeaseTemplate;
use App\Models\User;

/**
 * Authorization policy for LeaseTemplate model.
 *
 * Controls access to lease template management and preview features.
 * Super admins/admins always have access; other users need the
 * 'manage_templates' Spatie permission.
 */
class LeaseTemplatePolicy
{
    /**
     * Determine if the user can view any templates.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->can('manage_templates');
    }

    /**
     * Determine if the user can view a specific template.
     */
    public function view(User $user, LeaseTemplate $template): bool
    {
        return $user->isAdmin() || $user->can('manage_templates');
    }

    /**
     * Determine if the user can create templates.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->can('manage_templates');
    }

    /**
     * Determine if the user can update a template.
     */
    public function update(User $user, LeaseTemplate $template): bool
    {
        return $user->isAdmin() || $user->can('manage_templates');
    }

    /**
     * Determine if the user can delete a template.
     */
    public function delete(User $user, LeaseTemplate $template): bool
    {
        return $user->isSuperAdmin();
    }
}
