<?php

namespace App\Observers;

use App\Models\Role;
use App\Services\RoleService;

class RoleObserver
{
    /**
     * Handle the Role "created" event.
     */
    public function created(Role $role): void
    {
        RoleService::clearCache();
    }

    /**
     * Handle the Role "updated" event.
     */
    public function updated(Role $role): void
    {
        RoleService::clearCache();
    }

    /**
     * Handle the Role "deleted" event.
     */
    public function deleted(Role $role): void
    {
        RoleService::clearCache();
    }

    /**
     * Handle the Role "restored" event.
     */
    public function restored(Role $role): void
    {
        RoleService::clearCache();
    }
}
