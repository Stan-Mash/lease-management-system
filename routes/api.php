<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LeaseApiController;
use App\Http\Controllers\Api\TenantApiController;
use App\Http\Controllers\Api\PropertyApiController;
use App\Http\Controllers\Api\LandlordApiController;

Route::prefix('v1')
    ->middleware(['api', 'throttle:60,1'])
    ->group(function () {
        // Public verification endpoint
        Route::get('/leases/{lease}/verify', [LeaseApiController::class, 'verify'])
            ->withoutMiddleware('throttle')
            ->middleware('throttle:10,1')
            ->name('api.leases.verify');

        // Protected API endpoints
        Route::middleware(['auth:sanctum'])->group(function () {
            // Leases
            Route::apiResource('leases', LeaseApiController::class);
            Route::post('leases/{lease}/transition', [LeaseApiController::class, 'transition'])
                ->name('api.leases.transition');

            // Tenants
            Route::apiResource('tenants', TenantApiController::class);

            // Properties
            Route::apiResource('properties', PropertyApiController::class);

            // Landlords
            Route::apiResource('landlords', LandlordApiController::class);
        });
    });
