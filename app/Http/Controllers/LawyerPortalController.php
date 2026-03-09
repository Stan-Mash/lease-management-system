<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LeaseWorkflowState;
use App\Models\LeaseLawyerTracking;
use App\Services\DocumentUploadService;
use App\Services\LeasePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public lawyer portal: secure link for download lease PDF and upload stamped PDF.
 * No login; access via token in lease_lawyer_tracking.
 */
class LawyerPortalController extends Controller
{
    public function __construct(
        private readonly LeasePdfService $pdfService,
        private readonly DocumentUploadService $uploadService,
    ) {}

    /**
     * Show portal page: download link + upload form.
     */
    public function show(string $token): View|Response
    {
        $tracking = LeaseLawyerTracking::findByToken($token);

        if (! $tracking) {
            abort(404, 'This link is invalid or has expired.');
        }

        $tracking->load(['lease.tenant', 'lease.property', 'lawyer']);
        $lease = $tracking->lease;

        return response()
            ->view('lawyer.portal', [
                'tracking' => $tracking,
                'lease' => $lease,
                'token' => $token,
                'downloadUrl' => route('lawyer.portal.download', ['token' => $token]),
                'expiresAt' => $tracking->lawyer_link_expires_at,
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Download the lease PDF (for lawyer to stamp).
     */
    public function download(string $token): Response
    {
        $tracking = LeaseLawyerTracking::findByToken($token);

        if (! $tracking) {
            abort(404, 'This link is invalid or has expired.');
        }

        $lease = $tracking->lease;
        $lease->load(['tenant', 'unit', 'property', 'landlord', 'leaseTemplate', 'digitalSignatures']);

        $binary = $this->pdfService->generate($lease);
        $filename = $this->pdfService->filename($lease);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => strlen($binary),
        ]);
    }

    /**
     * Accept uploaded stamped PDF from lawyer.
     */
    public function upload(Request $request, string $token): Response
    {
        $tracking = LeaseLawyerTracking::findByToken($token);

        if (! $tracking) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'This link is invalid or has expired.'], 404);
            }

            return redirect()->route('lawyer.portal', $token)
                ->with('error', 'This link is invalid or has expired.');
        }

        $lease = $tracking->lease;

        try {
            // For now, only record that the lawyer has completed their part.
            // The actual stamping of the server-side PDF is handled internally
            // by staff using PdfOverlayService; no external PDFs are trusted.
            $tracking->markAsReturned('email', null, 'Lawyer confirmed completion via portal.');
        } catch (\Throwable $e) {
            Log::error('Lawyer portal upload failed', [
                'lease_id' => $lease->id,
                'tracking_id' => $tracking->id,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Upload failed. Please try again.'], 500);
            }

            return redirect()->route('lawyer.portal', $token)
                ->with('error', 'Upload failed. Please try again.');
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Stamped lease received. Thank you.']);
        }

        return redirect()->route('lawyer.portal', $token)
            ->with('success', 'Stamped lease received. Thank you.');
    }
}
