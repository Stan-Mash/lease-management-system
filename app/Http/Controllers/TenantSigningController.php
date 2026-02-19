<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\DisputeReason;
use App\Enums\LeaseWorkflowState;
use App\Http\Requests\RejectLeaseRequest;
use App\Http\Requests\SubmitSignatureRequest;
use App\Http\Requests\VerifyOTPRequest;
use App\Models\Lease;
use App\Services\DigitalSigningService;
use App\Services\LeaseDisputeService;
use App\Services\OTPService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class TenantSigningController extends Controller
{
    /**
     * Display the signing portal for a tenant.
     */
    public function show(Request $request, Lease $lease): View
    {
        $this->verifySignedUrlAndTenant($request, $lease);

        // Check if already signed
        if ($lease->hasDigitalSignature()) {
            return view('tenant.signing.already-signed', compact('lease'));
        }

        // Get signing status
        $status = DigitalSigningService::getSigningStatus($lease);

        return view('tenant.signing.portal', [
            'lease' => $lease,
            'tenant' => $lease->tenant,
            'status' => $status,
        ]);
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
            $signature = DB::transaction(function () use ($request, $lease) {
                // Lock the lease row to prevent concurrent signature submissions
                $lockedLease = Lease::lockForUpdate()->findOrFail($lease->id);

                // Re-check inside transaction with locked row
                if (! DigitalSigningService::canSign($lockedLease)) {
                    throw new Exception('Please verify your OTP before signing.');
                }

                if ($lockedLease->hasDigitalSignature()) {
                    throw new Exception('This lease has already been signed.');
                }

                $latestOTP = OTPService::getLatestOTP($lockedLease);

                return DigitalSigningService::captureSignature($lockedLease, [
                    'signature_data' => $request->validated('signature_data'),
                    'signature_type' => 'canvas',
                    'latitude' => $request->validated('latitude'),
                    'longitude' => $request->validated('longitude'),
                    'otp_verification_id' => $latestOTP?->id,
                    'metadata' => [
                        'browser' => $request->userAgent(),
                        'screen_resolution' => $request->input('screen_resolution'),
                    ],
                ]);
            });

            Log::info('Signature submitted successfully', [
                'lease_id' => $lease->id,
                'tenant_id' => $lease->tenant_id,
                'signature_id' => $signature->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lease signed successfully!',
                'signature_id' => $signature->id,
            ]);
        } catch (Exception $e) {
            Log::error('Signature submission failed', [
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
     * Display lease PDF for review.
     */
    public function viewLease(Request $request, Lease $lease): View
    {
        $this->verifySignedUrlAndTenant($request, $lease);

        return view('tenant.signing.lease-preview', compact('lease'));
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
     * Verify both the signed URL and tenant ownership on every request.
     *
     * Prevents IDOR where a valid signed URL for one lease could be
     * used to interact with a different lease's signing endpoints.
     */
    private function verifySignedUrlAndTenant(Request $request, Lease $lease): void
    {
        // The signed URL was generated for GET /tenant/sign/{lease}.
        // Sub-routes (request-otp, verify-otp, submit-signature) carry the same
        // query params (expires, tenant, signature) but on a different path.
        // Laravel's hasValidSignature() includes the path in the HMAC, so we
        // reconstruct a fake request pointing at the original signed route to verify.
        //
        // NOTE: route() may append `tenant` as a query param (since it's not a path
        // segment), so we strip everything after `?` to get a clean base URL before
        // appending the signed params — avoids a double-`?` malformed URL.
        $routeUrl = route('tenant.sign-lease', ['lease' => $lease->id]);
        $baseUrl = explode('?', $routeUrl)[0];
        $queryParams = array_filter($request->only(['expires', 'tenant', 'signature']));
        $reconstructed = $baseUrl . '?' . http_build_query($queryParams);

        $fakeRequest = \Illuminate\Http\Request::create($reconstructed, 'GET');

        if (! app('url')->hasValidSignature($fakeRequest)) {
            abort(403, 'This signing link has expired or is invalid.');
        }

        if ((int) $request->get('tenant') !== $lease->tenant_id) {
            abort(403, 'Unauthorized access.');
        }
    }
}
