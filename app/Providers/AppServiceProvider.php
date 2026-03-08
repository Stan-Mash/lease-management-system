<?php

namespace App\Providers;

use App\Models\Lease;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Unit;
use App\Observers\LeaseObserver;
use App\Observers\RoleObserver;
use App\Observers\TenantObserver;
use App\Observers\UnitObserver;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
        // Redirect all outgoing mail to a single address during test periods.
        // To enable: set MAIL_REDIRECT_TO=someone@example.com in .env
        // To disable: remove the variable (or leave it empty)
        if ($redirect = config('mail.redirect_to')) {
            Mail::alwaysTo($redirect);
        }

        // Log every outgoing email's final recipient for tracing (remove once deliverability confirmed)
        Event::listen(MessageSent::class, function (MessageSent $event): void {
            $to = collect($event->sent->getTo() ?? [])->keys()->implode(', ');
            Log::info('Mail sent via SMTP', [
                'to'      => $to ?: '(none)',
                'subject' => $event->sent->getSubject() ?? '(no subject)',
            ]);
        });

        // Register observers
        Lease::observe(LeaseObserver::class);
        Role::observe(RoleObserver::class);
        Tenant::observe(TenantObserver::class);
        Unit::observe(UnitObserver::class);

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
