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
