<?php

namespace App\Models\Concerns;

use App\Actions\Lease\ApproveLease;
use App\Actions\Lease\RejectLease;
use App\Actions\Lease\RequestLeaseApproval;
use App\Models\LeaseApproval;

/**
 * Trait for models that have approval workflow capabilities.
 */
trait HasApprovalWorkflow
{
    /**
     * Request approval from landlord.
     */
    public function requestApproval(): LeaseApproval
    {
        return app(RequestLeaseApproval::class)->execute($this);
    }

    /**
     * Approve the lease.
     *
     * @param string|null $comments Optional approval comments
     */
    public function approve(?string $comments = null): LeaseApproval
    {
        return app(ApproveLease::class)->execute($this, $comments);
    }

    /**
     * Reject the lease.
     *
     * @param string $reason Reason for rejection
     * @param string|null $comments Optional additional comments
     */
    public function reject(string $reason, ?string $comments = null): LeaseApproval
    {
        return app(RejectLease::class)->execute($this, $reason, $comments);
    }

    /**
     * Check if lease has pending approval.
     */
    public function hasPendingApproval(): bool
    {
        return $this->approvals()->pending()->exists();
    }

    /**
     * Check if lease has been approved.
     */
    public function hasBeenApproved(): bool
    {
        return $this->approvals()->approved()->exists();
    }

    /**
     * Check if lease has been rejected.
     */
    public function hasBeenRejected(): bool
    {
        return $this->approvals()->rejected()->exists();
    }

    /**
     * Get latest approval decision.
     */
    public function getLatestApproval(): ?LeaseApproval
    {
        return $this->approvals()->latest()->first();
    }

    /**
     * Check if the current state requires landlord action.
     */
    public function requiresLandlordAction(): bool
    {
        return $this->getWorkflowStateEnum()->requiresLandlordAction();
    }
}
