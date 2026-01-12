<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DownloadLeaseController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    // Immediate Download
    Route::get('/leases/{lease}/download', DownloadLeaseController::class)
        ->name('lease.download');

    // Print Preview
    Route::get('/leases/{lease}/preview', [DownloadLeaseController::class, 'preview'])
        ->name('lease.preview');
});
