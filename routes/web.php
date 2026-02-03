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
    Route::post('/sign/{lease}/reject', [TenantSigningController::class, 'rejectLease'])
        ->name('reject-lease');
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
    // Lease PDF Generation
    Route::get('/leases/{lease}/download', DownloadLeaseController::class)
        ->name('lease.download');
    Route::get('/leases/{lease}/preview', [DownloadLeaseController::class, 'preview'])
        ->name('lease.preview');
    Route::get('/leases/{lease}/pdf', DownloadLeaseController::class)
        ->name('leases.pdf');

    // Scanned Document Download
    Route::get('/documents/{document}/download', function (\App\Models\LeaseDocument $document) {
        $path = storage_path('app/' . $document->file_path);
        if (!file_exists($path)) {
            abort(404, 'Document not found');
        }
        return response()->download($path, $document->original_filename);
    })->name('document.download');

    // Template Preview Routes (Admin Only)
    Route::prefix('templates')->name('templates.')->group(function () {
        Route::get('/{template}/preview-pdf', [TemplatePreviewController::class, 'previewPdf'])
            ->name('preview-pdf');
        Route::get('/{template}/preview-html', [TemplatePreviewController::class, 'previewHtml'])
            ->name('preview-html');
        Route::get('/preview-direct', [TemplatePreviewController::class, 'previewDirect'])
            ->name('preview-direct');
    });

    // Lease Document Routes
    Route::prefix('lease-documents')->name('lease-documents.')->group(function () {
        Route::get('/{leaseDocument}/download', [LeaseDocumentController::class, 'download'])
            ->name('download');
        Route::get('/{leaseDocument}/preview', [LeaseDocumentController::class, 'preview'])
            ->name('preview');
        Route::get('/{leaseDocument}/verify', [LeaseDocumentController::class, 'verifyIntegrity'])
            ->name('verify');
    });
});
