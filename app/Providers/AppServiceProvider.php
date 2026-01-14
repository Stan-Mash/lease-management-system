<?php

namespace App\Providers;

use App\Models\Lease;
use App\Models\Role;
use App\Observers\LeaseObserver;
use App\Observers\RoleObserver;
use Illuminate\Support\ServiceProvider;

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
    }
}
