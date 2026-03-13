<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\DisputeReason;
use App\Enums\LeaseWorkflowState;
use App\Exceptions\LeaseSigningException;
use App\Http\Requests\RejectLeaseRequest;
use App\Http\Requests\SubmitSignatureRequest;
use App\Http\Requests\VerifyOTPRequest;
use App\Models\Lease;
use App\Models\LeaseLawyerTracking;
use App\Models\LeaseWitness;
use App\Services\DigitalSigningService;
use App\Services\LeaseDisputeService;
use App\Services\LeasePdfService;
use App\Services\OTPService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TenantSigningController extends Controller
{
    /**
     * Display the signing portal for a tenant.
     */
    public function show(Request $request, Lease $lease): View|Response
    {
        $this->verifySignedUrlAndTenant($request, $lease);

        // Check if already signed
        if ($lease->hasDigitalSignature()) {
            return view('tenant.signing.already-signed', compact('lease'));
        }

        // Get signing status
        $status = DigitalSigningService::getSigningStatus($lease);

        return response()
            ->view('tenant.signing.portal', [
                'lease' => $lease,
                'tenant' => $lease->tenant,
                'status' => $status,
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Request OTP for signing.
     */
    public function requestOTP(Request $request, Lease $lease): JsonResponse
    {
        $this->verifySignedUrlAndTenant($request, $lease);

        try {
            $otp = OTPService::generateAndSend(
                $lease,
                $lease->tenant->mobile_number,
            );

            Log::info('OTP requested for signing', [
                'lease_id' => $lease->id,
                'tenant_id' => $lease->tenant_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'OTP sent to your phone.',
                'expires_in_minutes' => config('lease.otp.expiry_minutes', 10),
            ]);
        } catch (Exception $e) {
            Log::error('OTP request failed', [
                'lease_id' => $lease->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Verify OTP code.
     */
    public function verifyOTP(VerifyOTPRequest $request, Lease $lease): JsonResponse
    {
        $this->verifySignedUrlAndTenant($request, $lease);

        try {
            $verified = OTPService::verify(
                $lease,
                $request->validated('code'),
                $request->ip(),
            );

            if ($verified) {
                if ($lease->workflow_state === LeaseWorkflowState::SENT_DIGITAL->value) {
                    $lease->transitionTo(LeaseWorkflowState::PENDING_OTP);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'OTP verified successfully. You can now sign the lease.',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP code. Please try again.',
            ], 400);
        } catch (Exception $e) {
            Log::error('OTP verification failed', [
                'lease_id' => $lease->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Verification failed. Please try again.',
            ], 400);
        }
    }

    /**
     * Submit digital signature.
     *
     * Uses DB::transaction with lockForUpdate to prevent race conditions
     * where concurrent requests could submit duplicate signatures.
     */
    public function submitSignature(SubmitSignatureRequest $request, Lease $lease): JsonResponse
    {
        $this->verifySignedUrlAndTenant($request, $lease);

        try {
            $validated = $request->validated();

            $signature = DB::transaction(function () use ($request, $lease, $validated) {
                // Lock the lease row to prevent concurrent signature submissions
                $lockedLease = Lease::lockForUpdate()->findOrFail($lease->id);

                // Re-check inside transaction with locked row — use typed exceptions
                // so callers can differentiate domain errors from unexpected failures
                if (! DigitalSigningService::canSign($lockedLease)) {
                    throw LeaseSigningException::otpNotVerified();
                }

                if ($lockedLease->hasDigitalSignature()) {
                    throw LeaseSigningException::alreadySigned($lockedLease->id);
                }

                $latestOTP = OTPService::getLatestOTP($lockedLease);

                $signature = DigitalSigningService::captureSignature($lockedLease, [
                    'signature_data' => $validated['signature_data'],
                    'signature_type' => 'canvas',
                    'latitude' => $validated['latitude'] ?? null,
                    'longitude' => $validated['longitude'] ?? null,
                    'otp_verification_id' => $latestOTP?->id,
                    'metadata' => [
                        'browser' => $request->userAgent(),
                        'screen_resolution' => $request->input('screen_resolution'),
                    ],
                ]);

                // Persist in-person witness for tenant side (lessee)
                $witnessPngB64 = $validated['witness_signature_data'] ?? null;
                if (! is_string($witnessPngB64) || ! str_starts_with($witnessPngB64, 'data:image/png;base64,')) {
                    throw LeaseSigningException::invalidState('Invalid witness signature payload received.');
                }
                $witnessBytes = base64_decode(substr($witnessPngB64, strlen('data:image/png;base64,')), true);
                if ($witnessBytes === false || $witnessBytes === '') {
                    throw LeaseSigningException::invalidState('Witness signature image could not be decoded.');
                }

                $witnessDir = 'lease-witness-signatures/lease-' . $lockedLease->id;
                $witnessFilename = 'tenant-witness-' . Str::uuid()->toString() . '.png';
                $witnessPath = $witnessDir . '/' . $witnessFilename;
                Storage::disk('local')->put($witnessPath, $witnessBytes);

                // Update top-level lease witness metadata
                $lockedLease->update([
                    'lessee_witness_name' => $validated['lessee_witness_name'] ?? null,
                    'lessee_witness_id' => $validated['lessee_witness_id'] ?? null,
                ]);

                LeaseWitness::create([
                    'lease_id' => $lockedLease->id,
                    'witnessed_party' => 'tenant',
                    'witnessed_by_user_id' => null,
                    'witnessed_by_name' => $validated['lessee_witness_name'] ?? '',
                    'witnessed_by_title' => null,
                    'witness_type' => 'external',
                    'lsk_number' => null,
                    'witness_id_number' => $validated['lessee_witness_id'] ?? null,
                    'witness_signature_path' => $witnessPath,
                    'witnessed_at' => now(),
                    'ip_address' => $request->ip(),
                    'notes' => 'Captured via tenant signing portal.',
                ]);

                return $signature;
            });

            Log::info('Signature submitted successfully', [
                'lease_id' => $lease->id,
                'tenant_id' => $lease->tenant_id,
                'signature_id' => $signature->id,
            ]);

            // Advocate routing — after successful tenant + witness signing
            $advocateSelection = $request->validated()['advocate_selection'] ?? null;
            if ($advocateSelection === 'own_advocate') {
                $advocateName = $request->validated()['tenant_advocate_name'] ?? null;
                $advocateEmail = $request->validated()['tenant_advocate_email'] ?? null;

                if ($advocateEmail) {
                    // Persist tenant-selected advocate details on the lease for later reuse (e.g. resend link)
                    $lease->update([
                        'tenant_advocate_name'  => $advocateName,
                        'tenant_advocate_email' => $advocateEmail,
                    ]);

                    $tracking = LeaseLawyerTracking::create([
                        'lease_id'    => $lease->id,
                        'lawyer_id'   => null,
                        'sent_method' => 'email',
                        'sent_by'     => null,
                        'sent_notes'  => 'Tenant-selected advocate: ' . ($advocateName ?? '') . ' <' . $advocateEmail . '>',
                        'status'      => 'sent',
                        'sent_at'     => now(),
                    ]);

                    $token = LeaseLawyerTracking::generateToken();
                    $tracking->update([
                        'lawyer_link_token'      => $token,
                        'lawyer_link_expires_at' => now()->addDays(14),
                        'sent_via_portal_link'   => true,
                    ]);

                    // Strict dev override for all advocate handoff communications (non-production)
                    $targetEmail = $advocateEmail;
                    $targetPhone = null; // no advocate phone captured in current schema

                    if (config('app.env') !== 'production') {
                        $targetEmail = 'stanely.macharia@chabrinagencies.co.ke';
                        $targetPhone = '+254720854389';
                        Log::info('Advocate Handoff Redirected to Dev', [
                            'email' => $targetEmail,
                            'phone' => $targetPhone,
                        ]);
                    }

                    // Send unified HTML notification to advocate via on-demand routing
                    $notification = new \App\Notifications\LeaseSentToLawyerNotification(
                        $lease,
                        new \App\Models\Lawyer(), // dummy model; routing is on-demand
                        $tracking->fresh(),
                        false // portal link mode (no PDF attachment)
                    );

                    $route = \Illuminate\Support\Facades\Notification::route('mail', $targetEmail);
                    // notifyNow() bypasses the queue so the email is dispatched synchronously
                    // while the tenant is still on the signing page — instant delivery.
                    $route->notifyNow($notification);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Lease signed successfully!',
                'signature_id' => $signature->id,
            ]);
        } catch (LeaseSigningException $e) {
            // Known domain error — return structured response with machine-readable code
            Log::warning('Lease signing domain error', [
                'lease_id'   => $lease->id,
                'error_code' => $e->getErrorCode(),
                'message'    => $e->getMessage(),
            ]);

            return $e->toJsonResponse();
        } catch (Exception $e) {
            // Unexpected error — log full detail, return generic message to client
            Log::error('Signature submission failed unexpectedly', [
                'lease_id' => $lease->id,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again or contact support.',
            ], 500);
        }
    }

    /**
     * Stream the lease PDF inline for tenant review.
     * Falls back to the Blade preview page if PDF generation fails.
     */
    public function viewLease(Request $request, Lease $lease): Response|View
    {
        $this->verifySignedUrlAndTenant($request, $lease);

        try {
            $pdfService = app(LeasePdfService::class);
            $pdfContent = $pdfService->generate($lease);
            $filename = $pdfService->filename($lease);

            return response($pdfContent, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
                'Content-Length'      => strlen($pdfContent),
                'Cache-Control'       => 'private, max-age=300',
            ]);
        } catch (Exception $e) {
            Log::warning('TenantSigningController: PDF generation failed, falling back to preview', [
                'lease_id' => $lease->id,
                'error'    => $e->getMessage(),
            ]);

            return view('tenant.signing.lease-preview', compact('lease'));
        }
    }

    /**
     * Reject/Dispute a lease.
     *
     * Delegates to LeaseDisputeService for all dispute business logic.
     */
    public function rejectLease(RejectLeaseRequest $request, Lease $lease): JsonResponse
    {
        $this->verifySignedUrlAndTenant($request, $lease);

        $reason = DisputeReason::from($request->validated('reason'));

        try {
            app(LeaseDisputeService::class)->dispute(
                lease: $lease,
                reason: $reason,
                comment: $request->validated('comment'),
                ipAddress: $request->ip(),
            );

            return response()->json([
                'success' => true,
                'message' => 'Your dispute has been submitted successfully. Our team will contact you shortly to resolve this issue.',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to process lease dispute', [
                'lease_id' => $lease->id,
                'tenant_id' => $lease->tenant_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit your dispute. Please try again or contact support.',
            ], 500);
        }
    }

    /**
     * Handle ID copy upload from the tenant after signing.
     *
     * Accepts one or more image/PDF files (JPG, PNG, PDF, max 5 MB each).
     * Files are stored in storage/app/private/tenant-id-documents/{lease_uuid}/
     * using UUID filenames to prevent path traversal and enumeration attacks.
     */
    public function uploadIdCopy(Request $request, Lease $lease): JsonResponse
    {
        $this->verifySignedUrlAndTenant($request, $lease);

        $request->validate([
            'id_documents'   => ['required', 'array', 'min:1', 'max:5'],
            'id_documents.*' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        try {
            $storedPaths = [];

            foreach ($request->file('id_documents') as $file) {
                // 1. Validate the ACTUAL file magic bytes — not just the declared MIME type.
                //    This prevents attackers from renaming malware.php → malware.pdf.
                $finfo    = new \finfo(FILEINFO_MIME_TYPE);
                $realMime = $finfo->file($file->getRealPath());
                $allowed  = ['image/jpeg', 'image/png', 'application/pdf'];

                if (! in_array($realMime, $allowed, strict: true)) {
                    return response()->json([
                        'success' => false,
                        'message' => "File type [{$realMime}] is not permitted. Upload JPG, PNG, or PDF only.",
                    ], 422);
                }

                // 2. Map real MIME to a safe extension — never trust the original filename
                $extension = match ($realMime) {
                    'image/jpeg'      => 'jpg',
                    'image/png'       => 'png',
                    'application/pdf' => 'pdf',
                };

                // 3. Use UUID filename + lease UUID directory — no user input in path ever
                //    Stored in the private disk (storage/app/private) — never web-accessible
                $filename = Str::uuid()->toString() . '.' . $extension;
                $directory = 'tenant-id-documents/' . ($lease->uuid ?? $lease->id);

                $path = $file->storeAs($directory, $filename, 'local');
                $storedPaths[] = $path;
            }

            Log::info('Tenant ID copy uploaded', [
                'lease_id'  => $lease->id,
                'tenant_id' => $lease->tenant_id,
                'files'     => count($storedPaths),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'ID document(s) uploaded successfully. Thank you!',
                'count'   => count($storedPaths),
            ]);
        } catch (Exception $e) {
            Log::error('Tenant ID upload failed', [
                'lease_id' => $lease->id,
                'error'    => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Upload failed. Please try again or contact support.',
            ], 500);
        }
    }

    /**
     * Verify both the signed URL and tenant ownership on every request.
     *
     * Uses Laravel's built-in URL signature verification instead of manually
     * reconstructing the signed URL — the old approach did not guarantee
     * query parameter ordering, creating a potential signature bypass.
     *
     * The signed URL was generated for GET /tenant/sign/{lease}.
     * Sub-routes carry the same query params (expires, tenant, signature)
     * on a different path. We reconstruct a fake request pointing at the
     * canonical signed route so hasValidSignature() checks the correct path.
     */
    private function verifySignedUrlAndTenant(Request $request, Lease $lease): void
    {
        // Build canonical base URL for the original signed route
        $routeUrl = route('tenant.sign-lease', ['lease' => $lease->id]);
        $baseUrl  = explode('?', $routeUrl)[0];

        // Extract only the signing params and sort them deterministically
        // so parameter order never causes a false-positive HMAC mismatch.
        $signingParams = array_filter($request->only(['expires', 'signature', 'tenant']));
        ksort($signingParams); // deterministic order for reproducible HMAC

        $canonicalUrl = $baseUrl . '?' . http_build_query($signingParams);

        // Delegate HMAC verification entirely to Laravel's UrlGenerator —
        // never roll your own signature verification logic.
        $fakeRequest = \Illuminate\Http\Request::create($canonicalUrl, 'GET');

        if (! app('url')->hasValidSignature($fakeRequest)) {
            abort(403, 'This signing link has expired or is invalid.');
        }

        // Verify the tenant ID embedded in the URL matches the lease record —
        // prevents a valid signed URL for lease A being used on lease B.
        if ((int) $request->get('tenant') !== $lease->tenant_id) {
            Log::warning('Tenant ID mismatch in signing portal', [
                'lease_id'       => $lease->id,
                'url_tenant_id'  => $request->get('tenant'),
                'lease_tenant_id' => $lease->tenant_id,
                'ip'             => $request->ip(),
            ]);
            abort(403, 'Unauthorized access.');
        }
    }
}
