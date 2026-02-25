<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\LeaseWorkflowState;
use App\Models\Lease;
use App\Models\LeaseAuditLog;
use App\Services\LeaseHealthService;
use Livewire\Component;

/**
 * Read-only visual lifecycle stepper for a lease.
 * Displays Tier 1 (7 macro phases) and Tier 2 (detail grid of micro-steps).
 */
class LeaseJourneyStepper extends Component
{
    public int $leaseId;

    public function mount(int $leaseId): void
    {
        $this->leaseId = $leaseId;
    }

    public function getLease(): ?Lease
    {
        return Lease::with(['tenant', 'digitalSignatures', 'approvals', 'auditLogs'])
            ->find($this->leaseId);
    }

    /**
     * Map workflow state to macro phase (1–7). -1 = disputed.
     */
    private function getMacroPhase(string $workflowState): int
    {
        return match ($workflowState) {
            'draft', 'received' => 1,
            'pending_landlord_approval', 'approved' => 2,
            'printed', 'checked_out', 'sent_digital', 'pending_otp',
            'pending_tenant_signature', 'returned_unsigned' => 3,
            'tenant_signed', 'with_lawyer', 'pending_upload' => 4,
            'pending_deposit' => 5,
            'active', 'renewal_offered', 'renewal_accepted' => 6,
            'expired', 'terminated', 'cancelled',
            'renewal_declined', 'archived' => 7,
            'disputed' => -1,
            default => 1,
        };
    }

    /**
     * Tier 1: 7 macro steps for the hero track.
     *
     * @return array<int, array{phase: int, label: string, state: string, completed: bool, current: bool, disputed: bool, timestamp: ?string}>
     */
    public function getMacroStepsProperty(): array
    {
        $lease = $this->getLease();
        if (! $lease) {
            return $this->defaultMacroSteps();
        }

        $currentPhase = $this->getMacroPhase($lease->workflow_state);
        $isDisputed = $lease->workflow_state === 'disputed';

        $labels = [
            1 => 'Draft',
            2 => 'Landlord Approved',
            3 => 'Sent to Tenant',
            4 => 'Tenant Signed',
            5 => 'Manager Countersigned',
            6 => 'Active',
            7 => 'Closed',
        ];

        $phaseToState = [
            1 => 'draft',
            2 => 'approved',
            3 => 'sent_digital',
            4 => 'tenant_signed',
            5 => 'active',
            6 => 'active',
            7 => 'archived',
        ];

        $timestamps = $this->getPhaseTimestampsFromAudit($lease);

        $steps = [];
        for ($phase = 1; $phase <= 7; $phase++) {
            $completed = ! $isDisputed && $currentPhase > $phase;
            $current = ! $isDisputed && $currentPhase === $phase;

            $steps[] = [
                'phase' => $phase,
                'label' => $labels[$phase],
                'state' => $phaseToState[$phase],
                'completed' => $completed,
                'current' => $current,
                'disputed' => $isDisputed && $phase === 3,
                'timestamp' => $timestamps[$phase] ?? null,
            ];
        }

        return $steps;
    }

    /**
     * Get when each macro phase was entered from LeaseAuditLog (new_state).
     *
     * @return array<int, string>
     */
    private function getPhaseTimestampsFromAudit(Lease $lease): array
    {
        $stateToPhase = [
            'draft' => 1, 'received' => 1,
            'pending_landlord_approval' => 2, 'approved' => 2,
            'printed' => 3, 'checked_out' => 3, 'sent_digital' => 3,
            'pending_otp' => 3, 'pending_tenant_signature' => 3, 'returned_unsigned' => 3,
            'tenant_signed' => 4, 'with_lawyer' => 4, 'pending_upload' => 4,
            'pending_deposit' => 5,
            'active' => 6, 'renewal_offered' => 6, 'renewal_accepted' => 6,
            'expired' => 7, 'terminated' => 7, 'cancelled' => 7,
            'renewal_declined' => 7, 'archived' => 7,
        ];

        $logs = LeaseAuditLog::where('lease_id', $lease->id)
            ->whereNotNull('new_state')
            ->orderBy('created_at')
            ->get();

        $result = [];
        foreach ($logs as $log) {
            $phase = $stateToPhase[$log->new_state] ?? null;
            if ($phase !== null && ! isset($result[$phase])) {
                $result[$phase] = $log->created_at->format('j M Y, g:i A');
            }
        }

        return $result;
    }

    private function defaultMacroSteps(): array
    {
        $labels = [
            1 => 'Draft', 2 => 'Landlord Approved', 3 => 'Sent to Tenant',
            4 => 'Tenant Signed', 5 => 'Manager Countersigned', 6 => 'Active', 7 => 'Closed',
        ];
        $steps = [];
        foreach ($labels as $phase => $label) {
            $steps[] = [
                'phase' => $phase,
                'label' => $label,
                'state' => 'draft',
                'completed' => false,
                'current' => $phase === 1,
                'disputed' => false,
                'timestamp' => null,
            ];
        }

        return $steps;
    }

    /**
     * Tier 2: Detail steps for the card grid.
     * Each step: number, title, description, status (done|active|pending|skipped|action_required), timestamp.
     *
     * @return array<int, array{number: int, title: string, description: string, status: string, timestamp: ?string}>
     */
    public function getDetailStepsProperty(): array
    {
        $lease = $this->getLease();
        $workflowState = $lease ? $lease->workflow_state : 'draft';

        $definitions = [
            ['title' => 'Create Lease', 'description' => 'Draft created', 'states' => ['draft', 'received']],
            ['title' => 'Register & Assign Zone', 'description' => 'Zone and field officer assigned', 'states' => ['pending_landlord_approval']],
            ['title' => 'Landlord Approval', 'description' => 'Landlord has approved', 'states' => ['approved']],
            ['title' => 'Send Signing Link', 'description' => 'Link sent to tenant', 'states' => ['sent_digital']],
            ['title' => 'OTP Verification', 'description' => 'Tenant verifies with OTP', 'states' => ['pending_otp']],
            ['title' => 'Tenant Reviews Lease', 'description' => 'Tenant reviews document', 'states' => ['pending_tenant_signature']],
            ['title' => 'Tenant Signs', 'description' => 'Tenant has signed', 'states' => ['tenant_signed']],
            ['title' => 'Manager Countersigns', 'description' => 'Manager countersigns and activates', 'states' => ['pending_deposit']],
            ['title' => 'Deposit & Activation', 'description' => 'Lease active', 'states' => ['active', 'renewal_offered', 'renewal_accepted']],
            ['title' => 'Closed', 'description' => 'Lease ended or archived', 'states' => ['expired', 'terminated', 'cancelled', 'renewal_declined', 'archived']],
        ];

        $stateOrder = array_flip(array_map(fn ($c) => $c->value, LeaseWorkflowState::cases()));
        $currentOrder = $stateOrder[$workflowState] ?? 999;
        $auditTimestamps = $lease ? $this->getDetailStepTimestamps($lease) : [];

        foreach ($definitions as $i => $def) {
            $stepNum = $i + 1;
            $done = false;
            $active = false;
            $skipped = false;
            $actionRequired = false;

            foreach ($def['states'] as $s) {
                $order = $stateOrder[$s] ?? 999;
                if ($s === $workflowState) {
                    $active = true;
                    break;
                }
                if ($order < $currentOrder) {
                    $done = true;
                }
            }

            if ($workflowState === 'disputed') {
                $active = in_array('pending_otp', $def['states']) || in_array('pending_tenant_signature', $def['states']);
                $actionRequired = $active;
            }
            if ($workflowState === 'returned_unsigned') {
                $actionRequired = in_array('pending_tenant_signature', $def['states']);
            }

            $status = $actionRequired ? 'action_required' : ($active ? 'active' : ($done ? 'done' : 'pending'));
            $timestamp = null;
            foreach ($def['states'] as $s) {
                if (isset($auditTimestamps[$s])) {
                    $timestamp = $auditTimestamps[$s];
                    break;
                }
            }

            $steps[] = [
                'number' => $stepNum,
                'title' => $def['title'],
                'description' => $def['description'],
                'status' => $status,
                'timestamp' => $timestamp,
            ];
        }

        return $steps;
    }

    /**
     * When each workflow state was entered (from audit log + model fields).
     *
     * For states that never appeared as new_state in audit logs (skipped states),
     * we fall back to: model timestamp columns → surrounding audit log entries.
     * This prevents "Not yet reached" from appearing on completed steps.
     *
     * @return array<string, string>
     */
    private function getDetailStepTimestamps(Lease $lease): array
    {
        // 1. Primary: build a map from audit log new_state entries
        $logs = LeaseAuditLog::where('lease_id', $lease->id)
            ->whereNotNull('new_state')
            ->orderBy('created_at')
            ->get();

        $auditMap = [];
        foreach ($logs as $log) {
            if (! isset($auditMap[$log->new_state])) {
                $auditMap[$log->new_state] = $log->created_at;
            }
        }

        // Also index audit log old_state entries so we know when a state was LEFT
        $leftMap = [];
        foreach ($logs as $log) {
            if ($log->old_state && ! isset($leftMap[$log->old_state])) {
                $leftMap[$log->old_state] = $log->created_at;
            }
        }

        // 2. Model-level fallbacks: explicit timestamp columns on the Lease model
        // (approved_at does not exist; countersigned_at is the only known ts column beyond created_at)
        $modelFallbacks = array_filter([
            'active'          => isset($lease->countersigned_at) ? $lease->countersigned_at : null,
            'pending_deposit' => isset($lease->countersigned_at) ? $lease->countersigned_at : null,
        ]);

        // 3. Ordered state list to find "nearest neighbour" timestamps for skipped states
        //    We use LeaseWorkflowState order + the audit map to interpolate.
        $stateOrder = [
            'draft', 'received', 'pending_landlord_approval', 'approved',
            'printed', 'checked_out', 'sent_digital', 'pending_otp',
            'pending_tenant_signature', 'returned_unsigned', 'tenant_signed',
            'with_lawyer', 'pending_upload', 'pending_deposit',
            'active', 'renewal_offered', 'renewal_accepted',
            'expired', 'terminated', 'cancelled', 'renewal_declined', 'archived',
        ];

        // Build a resolved timestamp for every state we know was "reached" in the journey
        // by filling backward: if state X has no audit entry but state X+1 does,
        // use the earliest available adjacent timestamp.
        $resolved = [];
        foreach ($stateOrder as $state) {
            if (isset($auditMap[$state])) {
                $resolved[$state] = $auditMap[$state];
            } elseif (isset($modelFallbacks[$state]) && $modelFallbacks[$state]) {
                $resolved[$state] = \Carbon\Carbon::parse($modelFallbacks[$state]);
            }
        }

        // For done states still missing a timestamp, find the closest EARLIER resolved ts
        // This handles states that were skipped (e.g. draft→approved skipping received)
        $currentWorkflowPos = array_search($lease->workflow_state, $stateOrder);
        foreach ($stateOrder as $idx => $state) {
            if (isset($resolved[$state])) {
                continue;
            }
            // Only fill in states that are "before" the current workflow position (i.e. done)
            if ($currentWorkflowPos !== false && $idx >= $currentWorkflowPos) {
                continue;
            }

            // Find the nearest earlier resolved timestamp
            $fallbackTs = null;
            for ($j = $idx - 1; $j >= 0; $j--) {
                if (isset($resolved[$stateOrder[$j]])) {
                    $fallbackTs = $resolved[$stateOrder[$j]];
                    break;
                }
            }
            // If no earlier one found, use lease created_at (very first state = draft)
            if (! $fallbackTs) {
                $fallbackTs = $lease->created_at;
            }
            $resolved[$state] = $fallbackTs;
        }

        // Also ensure 'draft' always has a timestamp (lease creation time)
        if (! isset($resolved['draft'])) {
            $resolved['draft'] = $lease->created_at;
        }

        // Format all to human-readable
        return array_map(fn ($ts) => $ts->format('j M Y, g:i A'), $resolved);
    }

    /**
     * Progress percentage (0–100) for the top progress bar.
     */
    public function getProgressProperty(): int
    {
        $lease = $this->getLease();
        if (! $lease) {
            return 0;
        }
        $phase = $this->getMacroPhase($lease->workflow_state);
        if ($phase <= 0) {
            return 0;
        }

        return (int) round(($phase / 7) * 100);
    }

    /**
     * Current state label and color for the pill badge.
     */
    public function getCurrentStateLabelProperty(): string
    {
        $lease = $this->getLease();
        if (! $lease) {
            return 'Draft';
        }
        try {
            $enum = LeaseWorkflowState::from($lease->workflow_state);

            return $enum->label();
        } catch (\ValueError $e) {
            return ucwords(str_replace('_', ' ', $lease->workflow_state));
        }
    }

    public function getCurrentStateColorProperty(): string
    {
        $lease = $this->getLease();
        if (! $lease) {
            return 'gray';
        }
        try {
            $enum = LeaseWorkflowState::from($lease->workflow_state);

            return $enum->color();
        } catch (\ValueError $e) {
            return 'gray';
        }
    }

    /** @return array{score: int, grade: string, flags: array<string>} */
    public function getHealthProperty(): array
    {
        $lease = $this->getLease();

        return $lease ? LeaseHealthService::score($lease) : ['score' => 0, 'grade' => 'F', 'flags' => []];
    }

    public function render()
    {
        return view('livewire.lease-journey-stepper');
    }
}
