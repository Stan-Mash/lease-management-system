<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LeaseWorkflowState;
use App\Models\DigitalSignature;
use App\Models\LeaseApproval;
use App\Models\User;
use App\Models\LeaseWitness;
use App\Services\LandlordApprovalService;
use App\Services\LeasePdfService;
use App\Services\OTPService;
use App\Services\SigningWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Filament\Notifications\Notification as FilamentNotification;

/**
 * Public landlord approval portal — no login required.
 *
 * Landlord receives a unique token link via SMS/email.
 * They open it, see the full lease document (same PDF as the admin sees),
 * and tap Approve, Request Changes, or Reject.
 * Token is single-use and expires after 7 days.
 */
class LandlordPublicApprovalController extends Controller
{
    /**
     * Show the public approval page.
     */
    public function show(string $token): View|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
    {
        $approval = $this->findValidApproval($token);

        if ($approval === 'not_found') {
            return view('landlord.public.invalid', ['reason' => 'not_found']);
        }
        if ($approval === 'expired') {
            return view('landlord.public.invalid', ['reason' => 'expired']);
        }
        if ($approval === 'actioned') {
            $approval = LeaseApproval::where('token', $token)
                ->with(['lease', 'landlord'])
                ->first();
            return response()
                ->view('landlord.public.already_actioned', compact('approval'))
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        }

        $documentUrl = route('landlord.public.document', $token);
        $otpVerified = session("otp_verified_{$token}") === true;

        return response()
            ->view('landlord.public.approval', compact('approval', 'documentUrl', 'otpVerified'))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Request OTP for landlord approval.
     */
    public function requestOtp(Request $request, string $token): \Illuminate\Http\JsonResponse
    {
        $approval = LeaseApproval::where('token', $token)
            ->with(['lease', 'landlord'])
            ->first();

        if (! $approval || ! $approval->tokenIsValid()) {
            return response()->json([
                'success' => false,
                'message' => 'This approval link is invalid or has expired.',
            ], 404);
        }

        $lease = $approval->lease;

        // Resolve landlord contact: real mobile → SMS_REDIRECT_TO fallback
        $phone = $approval->landlord?->mobile_number
            ?: config('services.sms_redirect_to');

        if (! $phone) {
            return response()->json([
                'success' => false,
                'message' => 'No mobile number is available for this landlord. Please contact Chabrin Agencies.',
            ], 400);
        }

        Log::info('Landlord portal OTP: resolved contact', [
            'lease_id' => $lease->id,
            'approval_id' => $approval->id,
            'phone_masked' => substr($phone, 0, 4) . '***',
        ]);

        try {
            OTPService::generateAndSend($lease, $phone);

            return response()->json([
                'success' => true,
                'message' => 'A verification code has been sent to your phone.',
                'expires_in_minutes' => config('lease.otp.expiry_minutes', 10),
            ]);
        } catch (\Throwable $e) {
            Log::error('Landlord portal OTP request failed', [
                'lease_id' => $lease->id,
                'approval_id' => $approval->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not send OTP. Please try again later.',
            ], 400);
        }
    }

    /**
     * Verify OTP for landlord approval.
     */
    public function verifyOtp(Request $request, string $token): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $approval = LeaseApproval::where('token', $token)
            ->with(['lease'])
            ->first();

        if (! $approval || ! $approval->tokenIsValid()) {
            return response()->json([
                'success' => false,
                'message' => 'This approval link is invalid or has expired.',
            ], 404);
        }

        $lease = $approval->lease;

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
                'message' => 'Phone number verified. You can now approve the lease.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Landlord portal OTP verification failed', [
                'lease_id' => $lease->id,
                'approval_id' => $approval->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Verification failed. Please try again.',
            ], 400);
        }
    }

    /**
     * Stream the lease PDF — authenticated by the approval token, no login required.
     * The landlord sees the exact same PDF the admin generates.
     */
    public function document(string $token): SymfonyResponse
    {
        $approval = LeaseApproval::where('token', $token)
            ->with(['lease.tenant', 'lease.unit', 'lease.property', 'lease.landlord', 'lease.leaseTemplate', 'lease.digitalSignatures'])
            ->first();

        if (! $approval || ! $approval->tokenIsValid()) {
            abort(403, 'Invalid or expired approval link.');
        }

        $lease = $approval->lease;
        $filename = 'Lease-' . $lease->reference_number . '.pdf';

        try {
            // Use LeasePdfService to generate a fresh, fully mapped PDF for the landlord.
            // Force draft watermark OFF and enforce strict use of the assigned template (no silent default fallback).
            $binary = app(LeasePdfService::class)->generate($lease, true, true);

            return response($binary, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
                'Content-Length'      => strlen($binary),
                'Cache-Control'       => 'private, max-age=300',
                'X-Frame-Options'     => 'SAMEORIGIN',
            ]);
        } catch (\Exception $e) {
            Log::error('Landlord portal: failed to generate lease PDF', [
                'lease_id' => $lease->id,
                'token'    => substr($token, 0, 8) . '...',
                'error'    => $e->getMessage(),
            ]);
            abort(500, 'Unable to generate lease document. Please contact Chabrin Agencies.');
        }
    }

    /**
     * Process an approval action (approve, request_changes, or reject).
     */
    public function action(Request $request, string $token): \Illuminate\Http\RedirectResponse|View
    {
        $approval = LeaseApproval::where('token', $token)
            ->with(['lease.tenant', 'lease.unit', 'lease.property', 'lease.landlord', 'lease.leaseTemplate', 'landlord'])
            ->first();

        if (! $approval || ! $approval->tokenIsValid()) {
            return redirect()->route('landlord.public.approval', $token)
                ->with('error', 'This approval link is no longer valid.');
        }

        if (! $approval->isPending()) {
            return redirect()->route('landlord.public.approval', $token)
                ->with('error', 'This lease has already been actioned.');
        }

        $action = $request->input('action');

        if ($action === 'approve' && ! session("otp_verified_{$token}")) {
            abort(403, 'Unauthorized.');
        }

        if ($action === 'approve') {
            $validated = $request->validate([
                'signature_data' => ['required', 'string'],
                'lessor_witness_name' => ['required', 'string', 'max:255'],
                'lessor_witness_id' => ['required', 'string', 'max:100'],
                'witness_signature_data' => ['required', 'string'],
                'comments' => ['nullable', 'string'],
            ]);

            $lease = $approval->lease;

            // Decode landlord signature
            $landlordPng = $validated['signature_data'];
            $prefix = 'data:image/png;base64,';
            if (! str_starts_with($landlordPng, $prefix)) {
                return redirect()->route('landlord.public.approval', $token)
                    ->with('error', 'Invalid landlord signature payload.');
            }
            $landlordBytes = base64_decode(substr($landlordPng, strlen($prefix)), true);
            if ($landlordBytes === false || $landlordBytes === '') {
                return redirect()->route('landlord.public.approval', $token)
                    ->with('error', 'Landlord signature could not be decoded. Please try again.');
            }

            // Decode witness signature
            $witnessPng = $validated['witness_signature_data'];
            if (! str_starts_with($witnessPng, $prefix)) {
                return redirect()->route('landlord.public.approval', $token)
                    ->with('error', 'Invalid witness signature payload.');
            }
            $witnessBytes = base64_decode(substr($witnessPng, strlen($prefix)), true);
            if ($witnessBytes === false || $witnessBytes === '') {
                return redirect()->route('landlord.public.approval', $token)
                    ->with('error', 'Witness signature could not be decoded. Please try again.');
            }

            // Store PNGs on local disk
            $leaseId = $lease->id;
            $sigDir = 'lease-signatures/lease-' . $leaseId;
            $witDir = 'lease-witness-signatures/lease-' . $leaseId;

            $landlordFilename = 'landlord-' . Str::uuid()->toString() . '.png';
            $landlordPath = $sigDir . '/' . $landlordFilename;
            Storage::disk('local')->put($landlordPath, $landlordBytes);

            $witnessFilename = 'lessor-witness-' . Str::uuid()->toString() . '.png';
            $witnessPath = $witDir . '/' . $witnessFilename;
            Storage::disk('local')->put($witnessPath, $witnessBytes);

            // Persist witness metadata on lease
            $lease->update([
                'lessor_witness_name' => $validated['lessor_witness_name'],
                'lessor_witness_id' => $validated['lessor_witness_id'],
            ]);

            // Create LeaseWitness record
            LeaseWitness::create([
                'lease_id' => $leaseId,
                'witnessed_party' => 'lessor',
                'witnessed_by_user_id' => null,
                'witnessed_by_name' => $validated['lessor_witness_name'],
                'witnessed_by_title' => null,
                'witness_type' => 'external',
                'lsk_number' => null,
                'witness_id_number' => $validated['lessor_witness_id'],
                'witness_signature_path' => $witnessPath,
                'witnessed_at' => now(),
                'ip_address' => $request->ip(),
                'notes' => 'Captured via landlord public approval portal.',
            ]);

            // ── Determine whether this is Route-1 lessor signing or a pre-signing pre-approval ──
            // Route 1 (lease.signing_route = 'landlord') and the lease is in PENDING_LANDLORD_PM:
            //   → landlord is signing as the executing lessor party; advance the workflow.
            // All other cases (PENDING_LANDLORD_APPROVAL or signing_route = 'manager'):
            //   → traditional pre-approval flow (LandlordApprovalService).
            $isLessorSigning = $lease->usesLandlordRoute()
                && $lease->workflow_state === LeaseWorkflowState::PENDING_LANDLORD_PM->value;

            if ($isLessorSigning) {
                // Record the landlord's digital signature
                DigitalSignature::createFromData([
                    'lease_id'           => $lease->id,
                    'tenant_id'          => null,
                    'signer_type'        => 'landlord',
                    'signed_by_user_id'  => null,
                    'signed_by_name'     => $lease->landlord?->names ?? 'Landlord',
                    'signature_data'     => $landlordPng,
                    'signature_type'     => 'drawn',
                    'ip_address'         => $request->ip(),
                    'user_agent'         => $request->userAgent(),
                    'signed_at'          => now(),
                    'metadata'           => ['source' => 'landlord_public_portal', 'approval_id' => $approval->id],
                ]);

                // Advance the workflow (state → PENDING_ADVOCATE for second cert)
                SigningWorkflowService::advanceAfterSignature($lease, 'landlord');

                // Notify internal staff
                $notifBody = sprintf(
                    'Landlord %s has signed lease %s as lessor. Second advocate certification is now required.',
                    $lease->landlord?->names ?? 'Landlord',
                    $lease->reference_number ?? ('#' . $lease->id),
                );
            } else {
                LandlordApprovalService::approveLease(
                    $lease,
                    $validated['comments'] ?? null,
                    'both',
                );
                $notifBody = sprintf(
                    'Landlord %s approved lease %s for %s. Review and proceed with digital signing / countersigning.',
                    $lease->landlord?->names ?? 'Landlord',
                    $lease->reference_number ?? ('#' . $lease->id),
                    $lease->tenant?->names ?? 'Tenant',
                );
            }

            // Notify internal staff
            $recipients = collect();
            $zoneManager = $lease->assignedZone?->zoneManager;
            if ($zoneManager instanceof User) {
                $recipients->push($zoneManager);
            } else {
                $recipients = User::whereIn('role', ['super_admin', 'admin'])->get();
            }

            if ($recipients->isNotEmpty()) {
                FilamentNotification::make()
                    ->title($isLessorSigning ? 'Landlord Signed Lease' : 'Landlord Approved Lease')
                    ->body($notifBody)
                    ->success()
                    ->sendToDatabase($recipients);
            }

            $approval->update(['token' => null, 'token_expires_at' => null]);

            return view('landlord.public.done', [
                'action'    => 'approved',
                'reference' => $lease->reference_number,
                'tenant'    => $lease->tenant?->names ?? 'the tenant',
            ]);
        }

        if ($action === 'request_changes') {
            $comments = trim($request->input('changes_comments', ''));

            if (empty($comments)) {
                return redirect()->route('landlord.public.approval', $token)
                    ->with('error', 'Please describe the changes you would like.');
            }

            LandlordApprovalService::requestChanges(
                $approval->lease,
                $comments,
                'both',
            );

            $approval->update(['token' => null, 'token_expires_at' => null]);

            return view('landlord.public.done', [
                'action'    => 'changes_requested',
                'reference' => $approval->lease->reference_number,
                'tenant'    => $approval->lease->tenant?->names ?? 'the tenant',
            ]);
        }

        if ($action === 'reject') {
            $reason = trim($request->input('rejection_reason', ''));

            if (empty($reason)) {
                return redirect()->route('landlord.public.approval', $token)
                    ->with('error', 'Please provide a reason for rejection.');
            }

            LandlordApprovalService::rejectLease(
                $approval->lease,
                $reason,
                $request->input('comments'),
                'both',
            );

            $approval->update(['token' => null, 'token_expires_at' => null]);

            return view('landlord.public.done', [
                'action'    => 'rejected',
                'reference' => $approval->lease->reference_number,
                'tenant'    => $approval->lease->tenant?->names ?? 'the tenant',
            ]);
        }

        return redirect()->route('landlord.public.approval', $token)
            ->with('error', 'Invalid action.');
    }

    /**
     * Validate a token and return the approval record, or a string status code.
     */
    private function findValidApproval(string $token): LeaseApproval|string
    {
        $approval = LeaseApproval::where('token', $token)
            ->with(['lease.tenant', 'lease.unit', 'lease.property', 'lease.landlord', 'lease.leaseTemplate', 'landlord'])
            ->first();

        if (! $approval) {
            return 'not_found';
        }
        if (! $approval->tokenIsValid()) {
            return 'expired';
        }
        if (! $approval->isPending()) {
            return 'actioned';
        }

        return $approval;
    }

}

