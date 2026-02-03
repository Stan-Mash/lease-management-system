<?php

namespace App\Providers;

use App\Models\Lease;
use App\Models\Role;
use App\Observers\LeaseObserver;
use App\Observers\RoleObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\Facades\Pulse;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register observers
        Lease::observe(LeaseObserver::class);
        Role::observe(RoleObserver::class);

        // Pulse authorization - allow admin users to view the dashboard
        Gate::define('viewPulse', function ($user) {
            // Allow users with admin-level roles
            // Also allow in local environment for testing
            if (app()->environment('local')) {
                return true;
            }

            return $user->hasRole(['super_admin', 'admin', 'it_officer']);
        });

        // Customize Pulse user resolution
        Pulse::user(fn ($user) => [
            'name' => $user->name,
            'email' => $user->email,
            'extra' => $user->getRoleNames()->first() ?? 'User',
        ]);
    }
}
