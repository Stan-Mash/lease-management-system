<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\LeaseApproval;
use App\Services\LandlordApprovalService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public landlord approval portal — no login required.
 *
 * Landlord receives a unique token link via SMS/email.
 * They open it, see the lease summary, and tap Approve or Reject.
 * Token is single-use and expires after 7 days.
 */
class LandlordPublicApprovalController extends Controller
{
    /**
     * Show the public approval page.
     */
    public function show(string $token): View|\Illuminate\Http\RedirectResponse
    {
        $approval = LeaseApproval::where('token', $token)
            ->with(['lease.tenant', 'lease.unit', 'lease.property', 'landlord'])
            ->first();

        if (! $approval) {
            return view('landlord.public.invalid', ['reason' => 'not_found']);
        }

        if (! $approval->tokenIsValid()) {
            return view('landlord.public.invalid', ['reason' => 'expired']);
        }

        if (! $approval->isPending()) {
            return view('landlord.public.already_actioned', compact('approval'));
        }

        return view('landlord.public.approval', compact('approval'));
    }

    /**
     * Process an approval action (approve or reject).
     */
    public function action(Request $request, string $token): \Illuminate\Http\RedirectResponse
    {
        $approval = LeaseApproval::where('token', $token)
            ->with(['lease.tenant', 'lease.unit', 'lease.property', 'landlord'])
            ->first();

        if (! $approval || ! $approval->tokenIsValid()) {
            return redirect()->route('landlord.public.approval', $token)
                ->with('error', 'This approval link is no longer valid.');
        }

        if (! $approval->isPending()) {
            return redirect()->route('landlord.public.approval', $token)
                ->with('error', 'This lease has already been actioned.');
        }

        $action = $request->input('action'); // 'approve' or 'reject'

        if ($action === 'approve') {
            LandlordApprovalService::approveLease(
                $approval->lease,
                $request->input('comments'),
                'both',
            );

            // Invalidate token after use so it cannot be replayed
            $approval->update(['token' => null, 'token_expires_at' => null]);

            return view('landlord.public.done', [
                'action'    => 'approved',
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

            // Invalidate token after use so it cannot be replayed
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
}
