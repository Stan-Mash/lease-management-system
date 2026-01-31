<?php

namespace App\Http\Controllers;

use App\Models\Lease;
use App\Models\Tenant;
use App\Services\DigitalSigningService;
use App\Services\OTPService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TenantSigningController extends Controller
{
    /**
     * Display the signing portal for a tenant.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(Request $request, Lease $lease)
    {
        // Verify the signed URL is valid
        if (! $request->hasValidSignature()) {
            abort(403, 'This signing link has expired or is invalid.');
        }

        // Verify tenant ID matches
        if ((int) $request->get('tenant') !== $lease->tenant_id) {
            abort(403, 'Unauthorized access.');
        }

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
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestOTP(Request $request, Lease $lease)
    {
        // Verify the signed URL
        if (! $request->hasValidSignature()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired link.',
            ], 403);
        }

        try {
            // Generate and send OTP
            $otp = OTPService::generateAndSend(
                $lease,
                $lease->tenant->phone,
            );

            Log::info('OTP requested for signing', [
                'lease_id' => $lease->id,
                'tenant_id' => $lease->tenant_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'OTP sent to your phone.',
                'expires_in_minutes' => 10,
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
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyOTP(Request $request, Lease $lease)
    {
        // Verify the signed URL
        if (! $request->hasValidSignature()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired link.',
            ], 403);
        }

        $request->validate([
            'code' => 'required|string|size:4',
        ]);

        try {
            $verified = OTPService::verify(
                $lease,
                $request->code,
                $request->ip(),
            );

            if ($verified) {
                // Update lease state to pending_otp -> verified
                if ($lease->workflow_state === 'sent_digital') {
                    $lease->transitionTo('pending_otp');
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitSignature(Request $request, Lease $lease)
    {
        // Verify the signed URL
        if (! $request->hasValidSignature()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired link.',
            ], 403);
        }

        // Check if can sign (OTP verified)
        if (! DigitalSigningService::canSign($lease)) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your OTP before signing.',
            ], 400);
        }

        // Check if already signed
        if ($lease->hasDigitalSignature()) {
            return response()->json([
                'success' => false,
                'message' => 'This lease has already been signed.',
            ], 400);
        }

        $request->validate([
            'signature_data' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        try {
            // Get latest OTP for reference
            $latestOTP = OTPService::getLatestOTP($lease);

            // Capture signature
            $signature = DigitalSigningService::captureSignature($lease, [
                'signature_data' => $request->signature_data,
                'signature_type' => 'canvas',
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'otp_verification_id' => $latestOTP?->id,
                'metadata' => [
                    'browser' => $request->userAgent(),
                    'screen_resolution' => $request->input('screen_resolution'),
                ],
            ]);

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
                'message' => 'Failed to submit signature. Please try again.',
            ], 400);
        }
    }

    /**
     * Display lease PDF for review.
     *
     * @return \Illuminate\Http\Response
     */
    public function viewLease(Request $request, Lease $lease)
    {
        // Verify the signed URL
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired link.');
        }

        // Return lease PDF or view
        return view('tenant.signing.lease-preview', compact('lease'));
    }
}
