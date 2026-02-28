<?php

use App\Http\Controllers\DownloadLeaseController;
use App\Http\Controllers\FieldOfficerController;
use App\Http\Controllers\LandlordApprovalController;
use App\Http\Controllers\LeaseDocumentController;
use App\Http\Controllers\LeaseVerificationController;
use App\Http\Controllers\TemplatePreviewController;
use App\Http\Controllers\TenantSigningController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

// Public lease verification routes (no auth required)
Route::get('/verify/lease', [LeaseVerificationController::class, 'show'])
    ->name('lease.verify');
Route::get('/api/verify/lease', [LeaseVerificationController::class, 'api'])
    ->middleware('throttle:10,1')
    ->name('lease.verify.api');

// Tenant signing portal routes (secured by signed URLs + rate limiting)
Route::prefix('tenant')->name('tenant.')->middleware('throttle:30,1')->group(function () {
    Route::get('/sign/{lease}', [TenantSigningController::class, 'show'])
        ->name('sign-lease');
    Route::post('/sign/{lease}/request-otp', [TenantSigningController::class, 'requestOTP'])
        ->middleware('throttle:5,1') // Stricter limit on OTP requests: 5 per minute
        ->name('request-otp');
    Route::post('/sign/{lease}/verify-otp', [TenantSigningController::class, 'verifyOTP'])
        ->middleware('throttle:10,1') // 10 verification attempts per minute
        ->name('verify-otp');
    Route::post('/sign/{lease}/submit-signature', [TenantSigningController::class, 'submitSignature'])
        ->middleware('throttle:3,1') // Very strict: 3 signature submissions per minute
        ->name('submit-signature');
    Route::get('/sign/{lease}/view', [TenantSigningController::class, 'viewLease'])
        ->name('view-lease');
    Route::post('/sign/{lease}/reject', [TenantSigningController::class, 'rejectLease'])
        ->middleware('throttle:5,1')
        ->name('reject-lease');
    Route::post('/sign/{lease}/upload-id', [TenantSigningController::class, 'uploadIdCopy'])
        ->middleware('throttle:5,1')
        ->name('upload-id');
});

// Landlord approval portal routes (for landlord app integration)
Route::middleware(['auth:sanctum'])->prefix('landlord/{landlordId}/approvals')->name('landlord.approvals.')->group(function () {
    Route::get('/', [LandlordApprovalController::class, 'index'])->name('index');
    Route::get('/{leaseId}', [LandlordApprovalController::class, 'show'])->name('show');
    Route::post('/{leaseId}/approve', [LandlordApprovalController::class, 'approve'])->name('approve');
    Route::post('/{leaseId}/reject', [LandlordApprovalController::class, 'reject'])->name('reject');
});

// API routes for landlord mobile app
Route::middleware(['auth:sanctum'])->prefix('api/landlord/{landlordId}')->name('api.landlord.')->group(function () {
    Route::get('/approvals', [LandlordApprovalController::class, 'apiIndex'])->name('approvals.index');
    Route::get('/approvals/{leaseId}', [LandlordApprovalController::class, 'apiShow'])->name('approvals.show');
    Route::post('/approvals/{leaseId}/approve', [LandlordApprovalController::class, 'apiApprove'])->name('approvals.approve');
    Route::post('/approvals/{leaseId}/reject', [LandlordApprovalController::class, 'apiReject'])->name('approvals.reject');
});

// API routes for field officer mobile app
Route::middleware(['auth:sanctum'])->prefix('api/field-officer')->name('api.field-officer.')->group(function () {
    Route::get('/dashboard', [FieldOfficerController::class, 'dashboard'])->name('dashboard');
    Route::get('/pending-approvals', [FieldOfficerController::class, 'pendingApprovals'])->name('pending-approvals');
    Route::get('/pending-by-landlord', [FieldOfficerController::class, 'pendingByLandlord'])->name('pending-by-landlord');
    Route::get('/overdue-approvals', [FieldOfficerController::class, 'overdueApprovals'])->name('overdue-approvals');
    Route::get('/approval-history', [FieldOfficerController::class, 'approvalHistory'])->name('approval-history');
    Route::get('/lease/{leaseId}/status', [FieldOfficerController::class, 'leaseApprovalStatus'])->name('lease-status');
});

Route::middleware(['auth'])->group(function () {
    // Lease PDF Generation — rate limited to prevent abuse of CPU-intensive PDF rendering
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/leases/{lease}/download', DownloadLeaseController::class)
            ->name('lease.download');
        Route::get('/leases/{lease}/preview', [DownloadLeaseController::class, 'preview'])
            ->name('lease.preview');
        Route::get('/leases/{lease}/pdf', DownloadLeaseController::class)
            ->name('leases.pdf');
    });

    // Scanned Document Download — uses policy for zone-based authorization
    Route::get('/documents/{document}/download', [LeaseDocumentController::class, 'download'])
        ->name('document.download')
        ->middleware('throttle:60,1')
        ->can('download', 'document');

    // Template Preview Routes — restricted to admin roles, rate limited
    Route::prefix('templates')->name('templates.')->middleware(['can:viewAny,App\Models\LeaseTemplate', 'throttle:20,1'])->group(function () {
        Route::get('/{template}/preview-pdf', [TemplatePreviewController::class, 'previewPdf'])
            ->name('preview-pdf');
        Route::get('/{template}/preview-html', [TemplatePreviewController::class, 'previewHtml'])
            ->name('preview-html');
        Route::get('/preview-direct', [TemplatePreviewController::class, 'previewDirect'])
            ->name('preview-direct');
    });

    // Lease Document Routes — uses policy for authorization, rate limited
    Route::prefix('lease-documents')->name('lease-documents.')->middleware('throttle:60,1')->group(function () {
        Route::get('/{leaseDocument}/download', [LeaseDocumentController::class, 'download'])
            ->name('download');
        Route::get('/{leaseDocument}/preview', [LeaseDocumentController::class, 'preview'])
            ->name('preview');
        Route::get('/{leaseDocument}/verify', [LeaseDocumentController::class, 'verifyIntegrity'])
            ->name('verify');
    });
});
