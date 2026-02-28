<?php

use App\Http\Controllers\Api\LandlordApiController;
use App\Http\Controllers\Api\LeaseApiController;
use App\Http\Controllers\Api\PropertyApiController;
use App\Http\Controllers\Api\TenantApiController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(['api', 'throttle:60,1'])
    ->group(function () {
        // Health check for load balancers / monitoring (no auth)
        Route::get('/health', function () {
            $database = false;

            try {
                DB::connection()->getPdo();
                $database = true;
            } catch (Throwable) {
                // leave false
            }

            return response()->json([
                'status' => 'ok',
                'database' => $database,
            ], $database ? 200 : 503);
        })->name('api.health');

        // Public verification endpoint: requires serial + hash (no lease ID) to prevent IDOR
        Route::get('/verify/lease', [LeaseApiController::class, 'verifyBySerialAndHash'])
            ->withoutMiddleware('throttle')
            ->middleware('throttle:10,1')
            ->name('api.leases.verify');

        // Deprecated: old verify URL by lease ID (IDOR risk). Return 410 so clients migrate to ?serial=&hash=
        Route::get('/leases/{lease}/verify', [LeaseApiController::class, 'verifyDeprecated'])
            ->withoutMiddleware('throttle')
            ->middleware('throttle:10,1')
            ->name('api.leases.verify.deprecated');

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
