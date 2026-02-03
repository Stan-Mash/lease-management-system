<?php

declare(strict_types=1);

namespace App\Providers;

use App\Livewire\Pulse\ChipsDatabaseCard;
use App\Livewire\Pulse\SmsBalanceCard;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Livewire\LivewireManager;

/**
 * Service provider for Laravel Pulse custom cards.
 *
 * Registers custom Livewire components for the Pulse dashboard.
 */
class PulseServiceProvider extends ServiceProvider
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
        // Register custom Pulse Livewire cards using the same pattern as Laravel Pulse
        $this->callAfterResolving('livewire', function (LivewireManager $livewire, Application $app) {
            $livewire->component('pulse.sms-balance', SmsBalanceCard::class);
            $livewire->component('pulse.chips-database', ChipsDatabaseCard::class);
        });
    }
}
