<?php

namespace App\Actions\Lease;

use App\Enums\LeaseWorkflowState;
use App\Exceptions\LeaseApprovalException;
use App\Models\Lease;
use App\Models\LeaseApproval;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Action class for approving a lease.
 */
class ApproveLease
{
    public function __construct(
        protected TransitionLeaseState $transitionAction,
    ) {}

    /**
     * Execute the approval.
     *
     * @param string|null $comments Optional approval comments
     *
     * @throws LeaseApprovalException
     */
    public function execute(Lease $lease, ?string $comments = null): LeaseApproval
    {
        if ($lease->hasBeenApproved()) {
            throw LeaseApprovalException::alreadyApproved($lease->reference_number);
        }

        return DB::transaction(function () use ($lease, $comments) {
            // Get or create approval record
            $approval = $lease->approvals()->whereNull('decision')->latest()->first();

            if (! $approval) {
                $approval = $lease->approvals()->create([
                    'lease_id' => $lease->id,
                    'landlord_id' => $lease->landlord_id,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            }

            // Update approval decision
            $approval->update([
                'decision' => 'approved',
                'comments' => $comments,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Transition to approved state
            $this->transitionAction->execute($lease, LeaseWorkflowState::APPROVED);

            // Create audit log
            $lease->auditLogs()->create([
                'action' => 'approved',
                'old_state' => 'pending_landlord_approval',
                'new_state' => 'approved',
                'user_id' => Auth::id(),
                'user_role_at_time' => Auth::user()?->roles?->first()?->name ?? 'system',
                'ip_address' => request()->ip(),
                'additional_data' => ['comments' => $comments],
                'description' => 'Lease approved by landlord',
            ]);

            return $approval;
        });
    }
}
