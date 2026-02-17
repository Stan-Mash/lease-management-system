<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Send lease expiry alerts daily at 8 AM
Schedule::command('leases:send-expiry-alerts')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/lease-expiry-alerts.log'));

// Apply rent escalations daily at 6 AM
Schedule::command('leases:apply-rent-escalations')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/rent-escalations.log'));

// Generate renewal offers daily at 7 AM (60 days before expiry)
Schedule::command('leases:generate-renewal-offers --days=60')
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/renewal-offers.log'));

/*
|--------------------------------------------------------------------------
| Database Backup & Maintenance
|--------------------------------------------------------------------------
*/

// Daily database backup at 2 AM (low traffic period)
Schedule::command('db:backup --compress --retention=30')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/database-backups.log'))
    ->onFailure(function () {
        Illuminate\Support\Facades\Log::critical('Database backup failed!');
    });

// Weekly full backup on Sunday at 3 AM (kept for 90 days)
Schedule::command('db:backup --compress --retention=90')
    ->weeklyOn(0, '03:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/database-backups.log'));

/*
|--------------------------------------------------------------------------
| Data Retention & Cleanup
|--------------------------------------------------------------------------
*/

// Clean up expired OTPs daily at 1 AM
Schedule::command('data:cleanup --type=otp --days=30')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/data-cleanup.log'));

// Clean up old audit logs monthly (keep 1 year)
Schedule::command('data:cleanup --type=audit --days=365')
    ->monthlyOn(1, '01:30')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/data-cleanup.log'));

// Clean up old document audit trails monthly (keep 2 years)
Schedule::command('data:cleanup --type=document-audit --days=730')
    ->monthlyOn(1, '02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/data-cleanup.log'));
