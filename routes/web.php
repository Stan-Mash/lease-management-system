<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DownloadLeaseController;
use App\Http\Controllers\LeaseVerificationController;

Route::get('/', function () {
    return view('welcome');
});

// Public lease verification routes (no auth required)
Route::get('/verify/lease', [LeaseVerificationController::class, 'show'])
    ->name('lease.verify');
Route::get('/api/verify/lease', [LeaseVerificationController::class, 'api'])
    ->name('lease.verify.api');

Route::middleware(['auth'])->group(function () {
    // Immediate Download
    Route::get('/leases/{lease}/download', DownloadLeaseController::class)
        ->name('lease.download');

    // Print Preview
    Route::get('/leases/{lease}/preview', [DownloadLeaseController::class, 'preview'])
        ->name('lease.preview');
});
