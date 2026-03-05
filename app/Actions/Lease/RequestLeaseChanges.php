<?php

namespace App\Actions\Lease;

use App\Enums\LeaseWorkflowState;
use App\Models\Lease;
use App\Models\LeaseApproval;
use Illuminate\Support\Facades\DB;

/**
 * Action class for recording a landlord's request for lease changes.
 * The lease returns to "changes_requested" state so the Chabrin agent
 * can edit it and re-send for approval.
 */
class RequestLeaseChanges
{
    public function __construct(
        protected TransitionLeaseState $transitionAction,
    ) {}

    /**
     * Execute the changes request.
     *
     * @param string $comments What the landlord wants changed
     */
    public function execute(Lease $lease, string $comments): LeaseApproval
    {
        return DB::transaction(function () use ($lease, $comments) {
            // Get the pending approval record
            $approval = $lease->approvals()->whereNull('decision')->latest()->first();

            if (! $approval) {
                $approval = $lease->approvals()->create([
                    'lease_id'   => $lease->id,
                    'landlord_id' => $lease->landlord_id,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            }

            // Record the decision
            $approval->update([
                'decision'    => 'changes_requested',
                'comments'    => $comments,
                'reviewed_at' => now(),
                'ip_address'  => request()->ip(),
                'user_agent'  => request()->userAgent(),
            ]);

            // Transition lease to changes_requested state
            $this->transitionAction->execute($lease, LeaseWorkflowState::CHANGES_REQUESTED);

            // Create audit log
            $lease->auditLogs()->create([
                'action'             => 'changes_requested',
                'old_state'          => 'pending_landlord_approval',
                'new_state'          => 'changes_requested',
                'user_id'            => null, // landlord, not an app user
                'user_role_at_time'  => 'landlord',
                'ip_address'         => request()->ip(),
                'additional_data'    => ['comments' => $comments],
                'description'        => 'Landlord requested changes: ' . $comments,
            ]);

            return $approval;
        });
    }
}
