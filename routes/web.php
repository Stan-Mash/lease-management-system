<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DownloadLeaseController;
use App\Http\Controllers\LeaseVerificationController;
use App\Http\Controllers\TenantSigningController;

Route::get('/', function () {
    return view('welcome');
});

// Public lease verification routes (no auth required)
Route::get('/verify/lease', [LeaseVerificationController::class, 'show'])
    ->name('lease.verify');
Route::get('/api/verify/lease', [LeaseVerificationController::class, 'api'])
    ->name('lease.verify.api');

// Tenant signing portal routes (secured by signed URLs)
Route::prefix('tenant')->name('tenant.')->group(function () {
    Route::get('/sign/{lease}', [TenantSigningController::class, 'show'])
        ->name('sign-lease');
    Route::post('/sign/{lease}/request-otp', [TenantSigningController::class, 'requestOTP'])
        ->name('request-otp');
    Route::post('/sign/{lease}/verify-otp', [TenantSigningController::class, 'verifyOTP'])
        ->name('verify-otp');
    Route::post('/sign/{lease}/submit-signature', [TenantSigningController::class, 'submitSignature'])
        ->name('submit-signature');
    Route::get('/sign/{lease}/view', [TenantSigningController::class, 'viewLease'])
        ->name('view-lease');
});

Route::middleware(['auth'])->group(function () {
    // Immediate Download
    Route::get('/leases/{lease}/download', DownloadLeaseController::class)
        ->name('lease.download');

    // Print Preview
    Route::get('/leases/{lease}/preview', [DownloadLeaseController::class, 'preview'])
        ->name('lease.preview');
});
