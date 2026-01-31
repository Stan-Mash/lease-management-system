<?php

namespace App\Providers;

use App\Contracts\DigitalSigningServiceInterface;
use App\Contracts\OTPServiceInterface;
use App\Contracts\SMSProviderInterface;
use App\Services\AfricasTalkingSMSProvider;
use App\Services\DigitalSigningService;
use App\Services\OTPService;
use Illuminate\Support\ServiceProvider;

class LeaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // SMS Provider - can be swapped for testing or different providers
        $this->app->singleton(SMSProviderInterface::class, function ($app) {
            return new AfricasTalkingSMSProvider;
        });

        // OTP Service - singleton for consistency
        $this->app->singleton(OTPServiceInterface::class, function ($app) {
            return new OTPService(
                $app->make(SMSProviderInterface::class),
            );
        });

        // Digital Signing Service
        $this->app->singleton(DigitalSigningServiceInterface::class, function ($app) {
            return new DigitalSigningService(
                $app->make(OTPServiceInterface::class),
                $app->make(SMSProviderInterface::class),
            );
        });

        // Bind aliases for easier resolution
        $this->app->alias(OTPServiceInterface::class, 'otp');
        $this->app->alias(SMSProviderInterface::class, 'sms');
        $this->app->alias(DigitalSigningServiceInterface::class, 'signing');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            SMSProviderInterface::class,
            OTPServiceInterface::class,
            DigitalSigningServiceInterface::class,
            'otp',
            'sms',
            'signing',
        ];
    }
}
