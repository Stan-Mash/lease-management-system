<?php

namespace App\Actions\Lease;

use App\Enums\LeaseWorkflowState;
use App\Exceptions\LeaseApprovalException;
use App\Models\Lease;
use App\Models\LeaseApproval;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Action class for rejecting a lease.
 */
class RejectLease
{
    public function __construct(
        protected TransitionLeaseState $transitionAction
    ) {}

    /**
     * Execute the rejection.
     *
     * @param Lease $lease
     * @param string $reason Reason for rejection
     * @param string|null $comments Optional additional comments
     * @return LeaseApproval
     * @throws LeaseApprovalException
     */
    public function execute(Lease $lease, string $reason, ?string $comments = null): LeaseApproval
    {
        if ($lease->hasBeenRejected()) {
            throw LeaseApprovalException::alreadyRejected($lease->reference_number);
        }

        return DB::transaction(function () use ($lease, $reason, $comments) {
            // Get or create approval record
            $approval = $lease->approvals()->whereNull('decision')->latest()->first();

            if (!$approval) {
                $approval = $lease->approvals()->create([
                    'lease_id' => $lease->id,
                    'landlord_id' => $lease->landlord_id,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            }

            // Update approval decision
            $approval->update([
                'decision' => 'rejected',
                'rejection_reason' => $reason,
                'comments' => $comments,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Transition to cancelled state
            $this->transitionAction->execute($lease, LeaseWorkflowState::CANCELLED);

            // Create audit log
            $lease->auditLogs()->create([
                'action' => 'rejected',
                'old_state' => 'pending_landlord_approval',
                'new_state' => 'cancelled',
                'user_id' => Auth::id(),
                'user_role_at_time' => Auth::user()?->roles?->first()?->name ?? 'system',
                'ip_address' => request()->ip(),
                'additional_data' => [
                    'rejection_reason' => $reason,
                    'comments' => $comments,
                ],
                'description' => "Lease rejected by landlord: {$reason}",
            ]);

            return $approval;
        });
    }
}
