<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lease;
use App\Models\LeaseApproval;
use App\Models\LeaseTemplate;
use App\Services\LandlordApprovalService;
use App\Services\TemplateRenderService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public landlord approval portal — no login required.
 *
 * Landlord receives a unique token link via SMS/email.
 * They open it, see the full lease document, and tap Approve, Request Changes, or Reject.
 * Token is single-use and expires after 7 days.
 */
class LandlordPublicApprovalController extends Controller
{
    /**
     * Show the public approval page with the full rendered lease document.
     */
    public function show(string $token): View|\Illuminate\Http\RedirectResponse
    {
        $approval = LeaseApproval::where('token', $token)
            ->with(['lease.tenant', 'lease.unit', 'lease.property', 'lease.landlord', 'lease.leaseTemplate', 'landlord'])
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

        $leaseHtml = $this->renderLease($approval->lease);

        return view('landlord.public.approval', compact('approval', 'leaseHtml'));
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

        $action = $request->input('action'); // 'approve', 'request_changes', or 'reject'

        if ($action === 'approve') {
            LandlordApprovalService::approveLease(
                $approval->lease,
                $request->input('comments'),
                'both',
            );

            $approval->update(['token' => null, 'token_expires_at' => null]);

            return view('landlord.public.done', [
                'action'    => 'approved',
                'reference' => $approval->lease->reference_number,
                'tenant'    => $approval->lease->tenant?->names ?? 'the tenant',
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
     * Render the lease document HTML using the same 3-strategy fallback as DownloadLeaseController.
     * Returns null if no template or rendering fails (graceful degradation).
     */
    private function renderLease(Lease $lease): ?string
    {
        $renderer = app(TemplateRenderService::class);

        // Strategy 1: assigned custom template
        if ($lease->lease_template_id && $lease->leaseTemplate) {
            try {
                return $renderer->render($lease->leaseTemplate, $lease);
            } catch (\Exception) {
                // fall through
            }
        }

        // Strategy 2: default template for this lease type
        $default = LeaseTemplate::where('template_type', $lease->lease_type)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();

        if ($default) {
            try {
                return $renderer->render($default, $lease);
            } catch (\Exception) {
                // fall through
            }
        }

        return null;
    }
}
