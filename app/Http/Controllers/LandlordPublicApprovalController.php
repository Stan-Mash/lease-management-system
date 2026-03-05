<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lease;
use App\Models\LeaseApproval;
use App\Models\LeaseTemplate;
use App\Services\LandlordApprovalService;
use App\Services\TemplateRenderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

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
    public function show(string $token): View|\Illuminate\Http\RedirectResponse
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
            return view('landlord.public.already_actioned', compact('approval'));
        }

        $documentUrl = route('landlord.public.document', $token);

        return view('landlord.public.approval', compact('approval', 'documentUrl'));
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

        // Strategy 1: assigned custom template
        if ($lease->lease_template_id && $lease->leaseTemplate) {
            try {
                $html = app(TemplateRenderService::class)->render($lease->leaseTemplate, $lease);
                return $this->streamPdf($html, $filename);
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
                $html = app(TemplateRenderService::class)->render($default, $lease);
                return $this->streamPdf($html, $filename);
            } catch (\Exception) {
                // fall through
            }
        }

        // Strategy 3: hardcoded Blade views
        $viewName = match ($lease->lease_type) {
            'residential_major' => 'pdf.residential-major',
            'residential_micro' => 'pdf.residential-micro',
            'commercial'        => 'pdf.commercial',
            default             => 'pdf.residential-major',
        };

        $pdf = Pdf::loadView($viewName, [
            'lease'    => $lease,
            'tenant'   => $lease->tenant,
            'unit'     => $lease->unit,
            'landlord' => $lease->landlord,
            'property' => $lease->property,
            'today'    => now()->format('d/m/Y'),
        ]);

        return $this->streamPdf(null, $filename, $pdf);
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

    /**
     * Build a streaming PDF response from HTML or a pre-built Pdf instance.
     */
    private function streamPdf(
        ?string $html,
        string $filename,
        ?\Barryvdh\DomPDF\PDF $pdf = null,
    ): SymfonyResponse {
        if ($pdf === null) {
            $pdf = Pdf::loadHTML($html);
        }

        $output = $pdf->output();

        return response($output, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Content-Length'      => strlen($output),
            'Cache-Control'       => 'private, max-age=300',
            'X-Frame-Options'     => 'SAMEORIGIN',
        ]);
    }
}
