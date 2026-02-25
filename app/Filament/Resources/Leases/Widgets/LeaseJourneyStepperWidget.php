<?php

declare(strict_types=1);

namespace App\Filament\Resources\Leases\Widgets;

use App\Enums\LeaseWorkflowState;
use App\Models\Lease;
use App\Models\LeaseAuditLog;
use App\Services\LeaseHealthService;
use Filament\Widgets\Widget;

/**
 * Header widget for ViewLease: renders the Lease Journey stepper (read-only).
 *
 * Previously delegated to a nested @livewire(LeaseJourneyStepper) component,
 * which caused Alpine x-data scopes to break after parent Livewire re-renders
 * (Filament widgets cannot safely nest other Livewire components in their views).
 * All stepper logic is now inlined here so this is the single Livewire component.
 */
class LeaseJourneyStepperWidget extends Widget
{
    protected string $view = 'filament.resources.leases.widgets.lease-journey-stepper-widget';

    protected int|string|array $columnSpan = 'full';

    /** Set by Filament when rendered on a ViewRecord page, or resolved from parent. */
    public ?Lease $record = null;

    public function mount(): void
    {
        if ($this->record === null) {
            $owner = $this->getOwner();
            if ($owner !== null && method_exists($owner, 'getRecord')) {
                $this->record = $owner->getRecord();
            }
        }
    }

    // ─── Data accessors used by the blade view ────────────────────────────────

    public function getLease(): ?Lease
    {
        if ($this->record !== null) {
            return $this->record->load(['tenant', 'digitalSignatures', 'approvals', 'auditLogs']);
        }

        return null;
    }

    /**
     * Map workflow state to macro phase (1–7). -1 = disputed.
     */
    private function getMacroPhase(string $workflowState): int
    {
        return match ($workflowState) {
            'draft', 'received'                                                 => 1,
            'pending_landlord_approval', 'approved'                             => 2,
            'printed', 'checked_out', 'sent_digital', 'pending_otp',
            'pending_tenant_signature', 'returned_unsigned'                     => 3,
            'tenant_signed', 'with_lawyer', 'pending_upload'                   => 4,
            'pending_deposit'                                                   => 5,
            'active', 'renewal_offered', 'renewal_accepted'                    => 6,
            'expired', 'terminated', 'cancelled',
            'renewal_declined', 'archived'                                      => 7,
            'disputed'                                                          => -1,
            default                                                             => 1,
        };
    }

    /**
     * Tier 1: 7 macro steps for the hero track.
     */
    public function getMacroStepsProperty(): array
    {
        $lease = $this->getLease();
        if (! $lease) {
            return $this->defaultMacroSteps();
        }

        $currentPhase = $this->getMacroPhase($lease->workflow_state);
        $isDisputed   = $lease->workflow_state === 'disputed';

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
            $current   = ! $isDisputed && $currentPhase === $phase;

            $steps[] = [
                'phase'     => $phase,
                'label'     => $labels[$phase],
                'state'     => $phaseToState[$phase],
                'completed' => $completed,
                'current'   => $current,
                'disputed'  => $isDisputed && $phase === 3,
                'timestamp' => $timestamps[$phase] ?? null,
            ];
        }

        return $steps;
    }

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
                'phase'     => $phase,
                'label'     => $label,
                'state'     => 'draft',
                'completed' => false,
                'current'   => $phase === 1,
                'disputed'  => false,
                'timestamp' => null,
            ];
        }

        return $steps;
    }

    /**
     * Tier 2: Detail steps for the card grid.
     */
    public function getDetailStepsProperty(): array
    {
        $lease         = $this->getLease();
        $workflowState = $lease ? $lease->workflow_state : 'draft';

        $definitions = [
            ['title' => 'Create Lease',            'description' => 'Draft created',                         'states' => ['draft', 'received']],
            ['title' => 'Register & Assign Zone',  'description' => 'Zone and field officer assigned',       'states' => ['pending_landlord_approval']],
            ['title' => 'Landlord Approval',        'description' => 'Landlord has approved',                 'states' => ['approved']],
            ['title' => 'Send Signing Link',        'description' => 'Link sent to tenant',                   'states' => ['sent_digital']],
            ['title' => 'OTP Verification',         'description' => 'Tenant verifies with OTP',              'states' => ['pending_otp']],
            ['title' => 'Tenant Reviews Lease',     'description' => 'Tenant reviews document',               'states' => ['pending_tenant_signature']],
            ['title' => 'Tenant Signs',             'description' => 'Tenant has signed',                     'states' => ['tenant_signed']],
            ['title' => 'Manager Countersigns',     'description' => 'Manager countersigns and activates',    'states' => ['pending_deposit']],
            ['title' => 'Deposit & Activation',     'description' => 'Lease active',                          'states' => ['active', 'renewal_offered', 'renewal_accepted']],
            ['title' => 'Closed',                   'description' => 'Lease ended or archived',               'states' => ['expired', 'terminated', 'cancelled', 'renewal_declined', 'archived']],
        ];

        $stateOrder   = array_flip(array_map(fn ($c) => $c->value, LeaseWorkflowState::cases()));
        $currentOrder = $stateOrder[$workflowState] ?? 999;
        $auditTs      = $lease ? $this->getDetailStepTimestamps($lease) : [];

        $steps = [];
        foreach ($definitions as $i => $def) {
            $stepNum        = $i + 1;
            $done           = false;
            $active         = false;
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
                $active         = in_array('pending_otp', $def['states']) || in_array('pending_tenant_signature', $def['states']);
                $actionRequired = $active;
            }
            if ($workflowState === 'returned_unsigned') {
                $actionRequired = in_array('pending_tenant_signature', $def['states']);
            }

            $status    = $actionRequired ? 'action_required' : ($active ? 'active' : ($done ? 'done' : 'pending'));
            $timestamp = null;
            foreach ($def['states'] as $s) {
                if (isset($auditTs[$s])) {
                    $timestamp = $auditTs[$s];
                    break;
                }
            }

            $steps[] = [
                'number'      => $stepNum,
                'title'       => $def['title'],
                'description' => $def['description'],
                'status'      => $status,
                'timestamp'   => $timestamp,
            ];
        }

        return $steps;
    }

    private function getDetailStepTimestamps(Lease $lease): array
    {
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

        $leftMap = [];
        foreach ($logs as $log) {
            if ($log->old_state && ! isset($leftMap[$log->old_state])) {
                $leftMap[$log->old_state] = $log->created_at;
            }
        }

        $modelFallbacks = array_filter([
            'active'          => isset($lease->countersigned_at) ? $lease->countersigned_at : null,
            'pending_deposit' => isset($lease->countersigned_at) ? $lease->countersigned_at : null,
        ]);

        $stateOrder = [
            'draft', 'received', 'pending_landlord_approval', 'approved',
            'printed', 'checked_out', 'sent_digital', 'pending_otp',
            'pending_tenant_signature', 'returned_unsigned', 'tenant_signed',
            'with_lawyer', 'pending_upload', 'pending_deposit',
            'active', 'renewal_offered', 'renewal_accepted',
            'expired', 'terminated', 'cancelled', 'renewal_declined', 'archived',
        ];

        $resolved = [];
        foreach ($stateOrder as $state) {
            if (isset($auditMap[$state])) {
                $resolved[$state] = $auditMap[$state];
            } elseif (isset($modelFallbacks[$state]) && $modelFallbacks[$state]) {
                $resolved[$state] = \Carbon\Carbon::parse($modelFallbacks[$state]);
            }
        }

        $currentWorkflowPos = array_search($lease->workflow_state, $stateOrder);
        foreach ($stateOrder as $idx => $state) {
            if (isset($resolved[$state])) {
                continue;
            }
            if ($currentWorkflowPos !== false && $idx >= $currentWorkflowPos) {
                continue;
            }
            $fallbackTs = null;
            for ($j = $idx - 1; $j >= 0; $j--) {
                if (isset($resolved[$stateOrder[$j]])) {
                    $fallbackTs = $resolved[$stateOrder[$j]];
                    break;
                }
            }
            if (! $fallbackTs) {
                $fallbackTs = $lease->created_at;
            }
            $resolved[$state] = $fallbackTs;
        }

        if (! isset($resolved['draft'])) {
            $resolved['draft'] = $lease->created_at;
        }

        return array_map(fn ($ts) => $ts->format('j M Y, g:i A'), $resolved);
    }

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

    public function getCurrentStateLabelProperty(): string
    {
        $lease = $this->getLease();
        if (! $lease) {
            return 'Draft';
        }
        try {
            return LeaseWorkflowState::from($lease->workflow_state)->label();
        } catch (\ValueError) {
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
            return LeaseWorkflowState::from($lease->workflow_state)->color();
        } catch (\ValueError) {
            return 'gray';
        }
    }

    /** @return array{score: int, grade: string, flags: array<string>} */
    public function getHealthProperty(): array
    {
        $lease = $this->getLease();

        return $lease ? LeaseHealthService::score($lease) : ['score' => 0, 'grade' => 'F', 'flags' => []];
    }

    public function getViewData(): array
    {
        return [
            'macroSteps'        => $this->macroSteps,
            'detailSteps'       => $this->detailSteps,
            'progress'          => $this->progress,
            'currentStateLabel' => $this->currentStateLabel,
            'currentStateColor' => $this->currentStateColor,
            'health'            => $this->health,
        ];
    }
}
