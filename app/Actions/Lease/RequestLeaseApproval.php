<?php

namespace App\Actions\Lease;

use App\Enums\LeaseWorkflowState;
use App\Exceptions\LeaseApprovalException;
use App\Models\Lease;
use App\Models\LeaseApproval;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Action class for requesting landlord approval on a lease.
 */
class RequestLeaseApproval
{
    public function __construct(
        protected TransitionLeaseState $transitionAction
    ) {}

    /**
     * Execute the approval request.
     *
     * @param Lease $lease
     * @return LeaseApproval
     * @throws LeaseApprovalException
     */
    public function execute(Lease $lease): LeaseApproval
    {
        if (!$lease->landlord_id) {
            throw LeaseApprovalException::noLandlord($lease->reference_number);
        }

        if ($lease->hasPendingApproval()) {
            throw new LeaseApprovalException(
                $lease->reference_number,
                'already_pending',
                "Lease {$lease->reference_number} already has a pending approval request."
            );
        }

        return DB::transaction(function () use ($lease) {
            // Transition to pending state
            $this->transitionAction->execute($lease, LeaseWorkflowState::PENDING_LANDLORD_APPROVAL);

            // Create approval record
            $approval = $lease->approvals()->create([
                'lease_id' => $lease->id,
                'landlord_id' => $lease->landlord_id,
                'reviewed_by' => null,
                'decision' => null,
                'previous_data' => $this->capturePreviousData($lease),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Create audit log
            $lease->auditLogs()->create([
                'action' => 'approval_requested',
                'old_state' => 'draft',
                'new_state' => 'pending_landlord_approval',
                'user_id' => Auth::id(),
                'user_role_at_time' => Auth::user()?->roles?->first()?->name ?? 'system',
                'ip_address' => request()->ip(),
                'description' => 'Lease submitted for landlord approval',
            ]);

            return $approval;
        });
    }

    /**
     * Capture current lease data for audit purposes.
     */
    protected function capturePreviousData(Lease $lease): array
    {
        return [
            'workflow_state' => $lease->workflow_state,
            'monthly_rent' => $lease->monthly_rent,
            'deposit_amount' => $lease->deposit_amount,
            'start_date' => $lease->start_date?->toDateString(),
            'end_date' => $lease->end_date?->toDateString(),
        ];
    }
}
