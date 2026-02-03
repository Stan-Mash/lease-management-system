<?php

namespace App\Http\Controllers;

use App\Enums\LeaseWorkflowState;
use App\Models\Lease;
use App\Models\Tenant;
use App\Notifications\LeaseDisputedNotification;
use App\Services\DigitalSigningService;
use App\Services\OTPService;
use App\Services\TenantEventService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    /**
     * Reject/Dispute a lease.
     *
     * Allows tenants to raise a dispute about the lease terms.
     * Transitions the lease to DISPUTED state and notifies the Zone Manager.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function rejectLease(Request $request, Lease $lease)
    {
        // Verify the signed URL
        if (! $request->hasValidSignature()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired link.',
            ], 403);
        }

        // Validate request
        $validated = $request->validate([
            'reason' => 'required|string|in:rent_too_high,wrong_dates,incorrect_details,terms_disagreement,not_my_lease,other',
            'comment' => 'nullable|string|max:1000',
        ]);

        // Check if lease can be disputed (must be in signing-related states)
        $allowedStates = [
            LeaseWorkflowState::SENT_DIGITAL->value,
            LeaseWorkflowState::PENDING_OTP->value,
            LeaseWorkflowState::PENDING_TENANT_SIGNATURE->value,
        ];

        if (! in_array($lease->workflow_state, $allowedStates)) {
            return response()->json([
                'success' => false,
                'message' => 'This lease cannot be disputed at this stage.',
            ], 400);
        }

        // Check if already signed - cannot dispute after signing
        if ($lease->hasDigitalSignature()) {
            return response()->json([
                'success' => false,
                'message' => 'This lease has already been signed and cannot be disputed.',
            ], 400);
        }

        try {
            DB::transaction(function () use ($lease, $validated) {
                // Store dispute details in lease metadata
                $disputeData = [
                    'reason' => $validated['reason'],
                    'comment' => $validated['comment'] ?? null,
                    'disputed_at' => now()->toIso8601String(),
                    'disputed_from_state' => $lease->workflow_state,
                    'tenant_ip' => request()->ip(),
                ];

                // Update lease notes with dispute info
                $existingNotes = $lease->notes ?? '';
                $disputeNote = sprintf(
                    "\n\n--- DISPUTE RAISED [%s] ---\nReason: %s\nComment: %s\n---",
                    now()->format('Y-m-d H:i:s'),
                    $this->getReasonLabel($validated['reason']),
                    $validated['comment'] ?? 'No comment provided'
                );

                $lease->update([
                    'notes' => $existingNotes . $disputeNote,
                ]);

                // Transition to DISPUTED state
                $lease->transitionTo(LeaseWorkflowState::DISPUTED);

                // Log the dispute event in tenant timeline
                TenantEventService::logDispute(
                    tenant: $lease->tenant,
                    title: 'Lease Disputed',
                    description: sprintf(
                        'Tenant disputed lease %s. Reason: %s. %s',
                        $lease->reference_number,
                        $this->getReasonLabel($validated['reason']),
                        $validated['comment'] ?? ''
                    ),
                    category: 'lease_dispute',
                    followUpAt: now()->addDays(2)  // Follow up within 2 days
                );

                // Also log as a lease event
                TenantEventService::logLeaseEvent(
                    tenant: $lease->tenant,
                    action: 'Disputed',
                    lease: $lease,
                    details: [
                        'reason' => $validated['reason'],
                        'reason_label' => $this->getReasonLabel($validated['reason']),
                        'comment' => $validated['comment'] ?? null,
                        'previous_state' => $disputeData['disputed_from_state'],
                    ]
                );

                // Notify the Zone Manager
                $this->notifyZoneManager($lease, $validated['reason'], $validated['comment'] ?? null);

                Log::info('Lease disputed by tenant', [
                    'lease_id' => $lease->id,
                    'tenant_id' => $lease->tenant_id,
                    'reference_number' => $lease->reference_number,
                    'reason' => $validated['reason'],
                    'previous_state' => $disputeData['disputed_from_state'],
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Your dispute has been submitted successfully. Our team will contact you shortly to resolve this issue.',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to process lease dispute', [
                'lease_id' => $lease->id,
                'tenant_id' => $lease->tenant_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit your dispute. Please try again or contact support.',
            ], 500);
        }
    }

    /**
     * Notify the Zone Manager about the lease dispute.
     */
    protected function notifyZoneManager(Lease $lease, string $reason, ?string $comment): void
    {
        // Get Zone Manager from the lease's zone
        $zoneManager = $lease->assignedZone?->zoneManager;

        if ($zoneManager) {
            $zoneManager->notify(new LeaseDisputedNotification($lease, $reason, $comment));

            Log::info('Zone Manager notified of lease dispute', [
                'lease_id' => $lease->id,
                'zone_manager_id' => $zoneManager->id,
                'zone_manager_email' => $zoneManager->email,
            ]);
        } else {
            // Fallback: notify all admins if no zone manager
            $admins = \App\Models\User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['super_admin', 'admin']);
            })->get();

            foreach ($admins as $admin) {
                $admin->notify(new LeaseDisputedNotification($lease, $reason, $comment));
            }

            Log::warning('No Zone Manager found for lease dispute, notified admins instead', [
                'lease_id' => $lease->id,
                'zone_id' => $lease->zone_id,
                'admin_count' => $admins->count(),
            ]);
        }
    }

    /**
     * Get human-readable label for dispute reason.
     */
    protected function getReasonLabel(string $reason): string
    {
        return match ($reason) {
            'rent_too_high' => 'Rent Amount Too High',
            'wrong_dates' => 'Incorrect Lease Dates',
            'incorrect_details' => 'Incorrect Personal/Property Details',
            'terms_disagreement' => 'Disagreement with Terms & Conditions',
            'not_my_lease' => 'This is Not My Lease',
            'other' => 'Other Reason',
            default => ucfirst(str_replace('_', ' ', $reason)),
        };
    }
}
