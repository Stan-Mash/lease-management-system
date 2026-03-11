<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LeaseWorkflowState;
use App\Models\DigitalSignature;
use App\Models\LeaseLawyerTracking;
use App\Models\User;
use App\Notifications\LeaseReturnedFromLawyerNotification;
use App\Services\DocumentUploadService;
use App\Services\LeasePdfService;
use App\Services\OTPService;
use App\Services\PdfOverlayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public lawyer portal: secure link for download lease PDF and upload stamped PDF,
 * or sign with pad/upload signature + optional stamp (we overlay and save).
 */
class LawyerPortalController extends Controller
{
    public function __construct(
        private readonly LeasePdfService $pdfService,
        private readonly DocumentUploadService $uploadService,
        private readonly PdfOverlayService $pdfOverlay,
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
        $alreadyProcessed = $tracking->status === 'returned'
            || ($lease->workflow_state !== null
                && ! in_array($lease->workflow_state, ['pending_advocate', 'with_lawyer'], true));

        return response()->view('lawyer.portal', [
            'tracking' => $tracking,
            'lease' => $lease,
            'token' => $token,
            'alreadyProcessed' => $alreadyProcessed,
            'downloadUrl' => route('lawyer.portal.download', ['token' => $token]),
            'expiresAt' => $tracking->lawyer_link_expires_at,
            'otpVerified' => session("otp_verified_{$token}") === true,
        ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Request OTP for advocate portal.
     */
    public function requestOtp(Request $request, string $token): \Illuminate\Http\JsonResponse
    {
        $tracking = LeaseLawyerTracking::findByToken($token);

        if (! $tracking) {
            return response()->json([
                'success' => false,
                'message' => 'This link is invalid or has expired.',
            ], 404);
        }

        $lease = $tracking->lease;
        $phone = $tracking->lawyer?->phone
            ?: config('services.sms_redirect_to');

        if (! $phone) {
            return response()->json([
                'success' => false,
                'message' => 'No mobile number is available for this advocate. Please contact Chabrin Agencies.',
            ], 400);
        }

        try {
            OTPService::generateAndSend($lease, $phone);

            return response()->json([
                'success' => true,
                'message' => 'A verification code has been sent to your phone.',
                'expires_in_minutes' => config('lease.otp.expiry_minutes', 10),
            ]);
        } catch (\Throwable $e) {
            Log::error('Lawyer portal OTP request failed', [
                'lease_id' => $lease->id,
                'tracking_id' => $tracking->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not send OTP. Please try again later.',
            ], 400);
        }
    }

    /**
     * Verify OTP for advocate portal.
     */
    public function verifyOtp(Request $request, string $token): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $tracking = LeaseLawyerTracking::findByToken($token);

        if (! $tracking) {
            return response()->json([
                'success' => false,
                'message' => 'This link is invalid or has expired.',
            ], 404);
        }

        $lease = $tracking->lease;

        try {
            $verified = OTPService::verify($lease, $data['code'], $request->ip());

            if (! $verified) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired code. Please request a new OTP.',
                ], 400);
            }

            session()->put("otp_verified_{$token}", true);

            return response()->json([
                'success' => true,
                'message' => 'Phone number verified. You can now submit the signed document.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Lawyer portal OTP verification failed', [
                'lease_id' => $lease->id,
                'tracking_id' => $tracking->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Verification failed. Please try again.',
            ], 400);
        }
    }

    /**
     * Download the lease PDF (for lawyer to stamp).
     * Access revoked once the document has been processed (tracking returned or lease moved past with_lawyer).
     */
    public function download(string $token): Response
    {
        $tracking = LeaseLawyerTracking::findByToken($token);

        if (! $tracking) {
            abort(404, 'This link is invalid or has expired.');
        }

        if ($tracking->status === 'returned') {
            abort(403, 'This document has already been processed and is no longer accessible via this secure link.');
        }

        $lease = $tracking->lease;
        if (! in_array($lease->workflow_state, ['pending_advocate', 'with_lawyer'], true)) {
            abort(403, 'This document has already been processed and is no longer accessible via this secure link.');
        }

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
     * Serve the lease PDF inline so the advocate can read it in the browser.
     * Access revoked once the document has been processed (tracking returned or lease moved past with_lawyer).
     */
    public function viewDocument(string $token): Response
    {
        $tracking = LeaseLawyerTracking::findByToken($token);

        if (! $tracking) {
            abort(404, 'This link is invalid or has expired.');
        }

        if ($tracking->status === 'returned') {
            abort(403, 'This document has already been processed and is no longer accessible via this secure link.');
        }

        $lease = $tracking->lease;
        if (! in_array($lease->workflow_state, ['pending_advocate', 'with_lawyer'], true)) {
            abort(403, 'This document has already been processed and is no longer accessible via this secure link.');
        }

        $lease->load(['tenant', 'unit', 'property', 'landlord', 'leaseTemplate', 'digitalSignatures']);

        $binary   = $this->pdfService->generate($lease);
        $filename = $this->pdfService->filename($lease);

        return response($binary, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Content-Length'      => strlen($binary),
            'Cache-Control'       => 'private, max-age=300',
        ]);
    }

    /**
     * Accept either: (1) uploaded stamped PDF, or (2) signature (pad base64 or file) + optional stamp (we overlay and save).
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

        if (! session("otp_verified_{$token}")) {
            abort(403, 'Unauthorized.');
        }

        // Guard against re-submission — findByToken() no longer blocks returned status
        // so the portal page remains accessible showing the "already processed" banner,
        // but we must still reject upload attempts on an already-returned record.
        if ($tracking->status === 'returned') {
            return $this->portalError(
                $request,
                $token,
                'This document has already been processed. No further uploads are accepted.',
            );
        }

        $lease = $tracking->lease;

        if ($request->hasFile('stamped_pdf')) {
            return $this->handleStampedPdfUpload($request, $token, $tracking, $lease);
        }

        return $this->handleSignatureAndStampSubmit($request, $token, $tracking, $lease);
    }

    private function handleStampedPdfUpload(Request $request, string $token, LeaseLawyerTracking $tracking, $lease): Response
    {
        $request->validate([
            'stamped_pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ], [
            'stamped_pdf.required' => 'Please select the stamped PDF file to upload.',
            'stamped_pdf.mimes' => 'The file must be a PDF.',
            'stamped_pdf.max' => 'The file must not exceed 20 MB.',
        ]);

        $file = $request->file('stamped_pdf');

        try {
            $document = $this->uploadService->upload(
                $file,
                $lease->id,
                'lawyer_stamped',
                'Stamped lease returned from advocate – ' . ($lease->reference_number ?? 'Lease ' . $lease->id),
                'Uploaded via lawyer portal.',
                now()->format('Y-m-d'),
                null,
            );

            if ($lease->unit_code) {
                $document->update(['unit_code' => $lease->unit_code]);
            }

            $tracking->markAsReturned('email', null, 'Returned via lawyer portal upload.');
            if ($lease->workflow_state === 'with_lawyer') {
                // Document already in system — skip pending_upload (that state is
                // only for physical returns where staff still need to scan & upload).
                $lease->transitionTo(LeaseWorkflowState::PENDING_DEPOSIT);
            }

            $this->notifyManagerLawyerReturned($lease, $tracking);
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

    private function handleSignatureAndStampSubmit(Request $request, string $token, LeaseLawyerTracking $tracking, $lease): Response
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'legal_consent' => ['required', 'accepted'],
            'signature_data' => ['nullable', 'string'],
            'signature_upload' => ['nullable', 'file', 'mimes:jpeg,jpg,png', 'max:5120'],
            'stamp_upload' => ['nullable', 'file', 'mimes:jpeg,jpg,png', 'max:5120'],
        ], [
            'legal_consent.required' => 'You must confirm that you are applying your legally binding signature and/or stamp.',
            'legal_consent.accepted' => 'You must confirm that you are applying your legally binding signature and/or stamp.',
        ]);
        $validator->after(function (Validator $v) use ($request) {
            if (! $request->filled('signature_data') && ! $request->hasFile('signature_upload')) {
                $v->errors()->add('signature', 'Please provide your signature by drawing in the pad or uploading an image.');
            }
        });
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $validator->errors()->first()], 422);
            }
            return redirect()->route('lawyer.portal', $token)->withErrors($validator)->withInput();
        }

        $tempDir = storage_path('app/advocate-portal-temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $signaturePath = null;
        $stampPath = null;
        $basePdfPath = null;
        $stampedPdfPath = null;
        $toDelete = [];

        try {
            if ($request->filled('signature_data')) {
                $dataUri = $request->input('signature_data');
                if (! preg_match('/^data:image\/\w+;base64,/', $dataUri)) {
                    return $this->portalError($request, $token, 'Invalid signature data.');
                }
                $decoded = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $dataUri), true);
                if ($decoded === false || strlen($decoded) < 100) {
                    return $this->portalError($request, $token, 'Signature image could not be decoded.');
                }
                $signaturePath = $tempDir . '/sig_' . uniqid() . '.png';
                file_put_contents($signaturePath, $decoded);
                $toDelete[] = $signaturePath;
            } else {
                $file = $request->file('signature_upload');
                $relPath = $file->storeAs('advocate-portal-temp', 'sig_' . uniqid() . '.' . $file->getClientOriginalExtension(), 'local');
                $signaturePath = Storage::disk('local')->path($relPath);
                $toDelete[] = $signaturePath;
            }

            if ($request->hasFile('stamp_upload')) {
                $file = $request->file('stamp_upload');
                $stampRel = $file->storeAs('advocate-portal-temp', 'stamp_' . uniqid() . '.' . $file->getClientOriginalExtension(), 'local');
                $stampPath = Storage::disk('local')->path($stampRel);
                $toDelete[] = $stampPath;
            }

            $lease->load(['leaseTemplate', 'tenant', 'unit', 'property', 'landlord', 'digitalSignatures']);
            $binary = $this->pdfService->generate($lease);
            $basePdfPath = $tempDir . '/base_' . uniqid() . '.pdf';
            file_put_contents($basePdfPath, $binary);
            $toDelete[] = $basePdfPath;

            $template = $lease->leaseTemplate;
            $coordinates = $template?->pdf_coordinate_map ?? [];
            $advocateCoord = is_array($coordinates) && isset($coordinates['advocate_signature'])
                ? $coordinates['advocate_signature']
                : ['page' => 1, 'x' => 160, 'y' => 250, 'width' => 45, 'height' => 18, 'anchor' => 'beside'];

            $stampedPdfPath = $tempDir . '/stamped_' . uniqid() . '.pdf';
            $toDelete[] = $stampedPdfPath;
            $this->pdfOverlay->applyAdvocateSignatureAndStamp($basePdfPath, $signaturePath, $stampPath, $advocateCoord, $stampedPdfPath);

            $stampedFile = new \Illuminate\Http\UploadedFile(
                $stampedPdfPath,
                'stamped-lease-' . $lease->reference_number . '.pdf',
                'application/pdf',
                0,
                true,
            );
            $document = $this->uploadService->upload(
                $stampedFile,
                $lease->id,
                'lawyer_stamped',
                'Stamped lease returned from advocate – ' . ($lease->reference_number ?? 'Lease ' . $lease->id),
                'Signed/stamped via lawyer portal (digital signature pad or upload).',
                now()->format('Y-m-d'),
                null,
            );
            if ($lease->unit_code) {
                $document->update(['unit_code' => $lease->unit_code]);
            }

            $signatureDataUri = $request->filled('signature_data')
                ? $request->input('signature_data')
                : 'data:image/png;base64,' . base64_encode(file_get_contents($signaturePath));

            DigitalSignature::createFromData([
                'lease_id' => $lease->id,
                'tenant_id' => null,
                'signer_type' => 'advocate',
                'signed_by_user_id' => null,
                'signed_by_name' => $tracking->lawyer?->name ?? 'Advocate',
                'signature_data' => $signatureDataUri,
                'signature_type' => 'drawn',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'signed_at' => now(),
                'metadata' => ['source' => 'lawyer_portal', 'tracking_id' => $tracking->id],
            ]);

            $tracking->markAsReturned('email', null, 'Returned via lawyer portal (signature and/or stamp applied).');
            if ($lease->workflow_state === 'with_lawyer') {
                // Document already in system — skip pending_upload (that state is
                // only for physical returns where staff still need to scan & upload).
                $lease->transitionTo(LeaseWorkflowState::PENDING_DEPOSIT);
            }

            $this->notifyManagerLawyerReturned($lease, $tracking);
        } catch (\Throwable $e) {
            Log::error('Lawyer portal signature/stamp failed', [
                'lease_id' => $lease->id,
                'tracking_id' => $tracking->id,
                'error' => $e->getMessage(),
            ]);
            foreach ($toDelete as $f) {
                if (is_string($f) && file_exists($f)) {
                    @unlink($f);
                }
            }
            return $this->portalError($request, $token, 'Could not apply signature. Please try again or upload a stamped PDF instead.');
        }

        foreach ($toDelete as $f) {
            if (is_string($f) && file_exists($f)) {
                @unlink($f);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Signature and stamp applied. Thank you.']);
        }

        return redirect()->route('lawyer.portal', $token)
            ->with('success', 'Your signature has been applied to the lease. Thank you.');
    }

    private function portalError(Request $request, string $token, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }
        return redirect()->route('lawyer.portal', $token)->with('error', $message);
    }

    /**
     * Notify the zone manager (or fallback to super_admin/admin) that the
     * advocate has returned the signed lease via the portal.
     */
    private function notifyManagerLawyerReturned(\App\Models\Lease $lease, LeaseLawyerTracking $tracking): void
    {
        try {
            $notification = new LeaseReturnedFromLawyerNotification($lease, $tracking);

            $zoneManager = $lease->assignedZone?->zoneManager;
            if ($zoneManager instanceof User) {
                $zoneManager->notify($notification);
                return;
            }

            User::whereIn('role', ['super_admin', 'admin'])->get()
                ->each(fn (User $u) => $u->notify($notification));
        } catch (\Throwable $e) {
            Log::warning('LawyerPortalController: failed to notify manager after lawyer return', [
                'lease_id' => $lease->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
