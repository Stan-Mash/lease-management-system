<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DisputeReason;
use App\Enums\LeaseWorkflowState;
use App\Models\Lease;
use App\Models\User;
use App\Notifications\LeaseDisputedNotification;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service responsible for all lease dispute business logic.
 *
 * Extracted from TenantSigningController to maintain single responsibility.
 */
class LeaseDisputeService
{
    private const DISPUTABLE_STATES = [
        LeaseWorkflowState::SENT_DIGITAL,
        LeaseWorkflowState::PENDING_OTP,
        LeaseWorkflowState::PENDING_TENANT_SIGNATURE,
    ];

    private const FOLLOW_UP_DAYS = 2;

    /**
     * Process a lease dispute raised by a tenant.
     *
     * @throws Exception If the lease cannot be disputed or processing fails
     */
    public function dispute(
        Lease $lease,
        DisputeReason $reason,
        ?string $comment = null,
        ?string $ipAddress = null,
    ): void {
        $this->validateCanDispute($lease);

        DB::transaction(function () use ($lease, $reason, $comment, $ipAddress): void {
            $previousState = $lease->workflow_state;

            $this->appendDisputeNotes($lease, $reason, $comment);
            $lease->transitionTo(LeaseWorkflowState::DISPUTED);

            $this->logDisputeEvents($lease, $reason, $comment, $previousState);
            $this->notifyResponsibleParties($lease, $reason, $comment);

            Log::info('Lease disputed by tenant', [
                'lease_id' => $lease->id,
                'tenant_id' => $lease->tenant_id,
                'reference_number' => $lease->reference_number,
                'reason' => $reason->value,
                'previous_state' => $previousState,
            ]);
        });
    }

    /**
     * Validate that the lease is in a state where it can be disputed.
     *
     * @throws Exception
     */
    private function validateCanDispute(Lease $lease): void
    {
        $allowedValues = array_map(
            fn (LeaseWorkflowState $state): string => $state->value,
            self::DISPUTABLE_STATES,
        );

        if (! in_array($lease->workflow_state, $allowedValues, true)) {
            throw new Exception('This lease cannot be disputed at this stage.');
        }

        if ($lease->hasDigitalSignature()) {
            throw new Exception('This lease has already been signed and cannot be disputed.');
        }
    }

    /**
     * Append dispute information to lease notes.
     */
    private function appendDisputeNotes(Lease $lease, DisputeReason $reason, ?string $comment): void
    {
        $existingNotes = $lease->notes ?? '';
        $disputeNote = sprintf(
            "\n\n--- DISPUTE RAISED [%s] ---\nReason: %s\nComment: %s\n---",
            now()->format('Y-m-d H:i:s'),
            $reason->label(),
            $comment ?? 'No comment provided',
        );

        $lease->update([
            'notes' => $existingNotes . $disputeNote,
        ]);
    }

    /**
     * Log dispute events to the tenant timeline.
     */
    private function logDisputeEvents(
        Lease $lease,
        DisputeReason $reason,
        ?string $comment,
        string $previousState,
    ): void {
        TenantEventService::logDispute(
            tenant: $lease->tenant,
            title: 'Lease Disputed',
            description: sprintf(
                'Tenant disputed lease %s. Reason: %s. %s',
                $lease->reference_number,
                $reason->label(),
                $comment ?? '',
            ),
            category: 'lease_dispute',
            followUpAt: now()->addDays(self::FOLLOW_UP_DAYS),
        );

        TenantEventService::logLeaseEvent(
            tenant: $lease->tenant,
            action: 'Disputed',
            lease: $lease,
            details: [
                'reason' => $reason->value,
                'reason_label' => $reason->label(),
                'comment' => $comment,
                'previous_state' => $previousState,
            ],
        );
    }

    /**
     * Notify the Zone Manager or fallback to admins.
     */
    private function notifyResponsibleParties(Lease $lease, DisputeReason $reason, ?string $comment): void
    {
        $zoneManager = $lease->assignedZone?->zoneManager;

        if ($zoneManager) {
            $zoneManager->notify(new LeaseDisputedNotification($lease, $reason->value, $comment));

            Log::info('Zone Manager notified of lease dispute', [
                'lease_id' => $lease->id,
                'zone_manager_id' => $zoneManager->id,
            ]);

            return;
        }

        // Fallback: notify all admins if no zone manager assigned
        $admins = User::whereHas('roles', function ($query): void {
            $query->whereIn('name', ['super_admin', 'admin']);
        })->get();

        foreach ($admins as $admin) {
            $admin->notify(new LeaseDisputedNotification($lease, $reason->value, $comment));
        }

        Log::warning('No Zone Manager found for lease dispute, notified admins instead', [
            'lease_id' => $lease->id,
            'zone_id' => $lease->zone_id,
            'admin_count' => $admins->count(),
        ]);
    }
}
