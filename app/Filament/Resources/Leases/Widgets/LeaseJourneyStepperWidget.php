<?php

declare(strict_types=1);

namespace App\Filament\Resources\Leases\Widgets;

use App\Enums\LeaseWorkflowState;
use App\Models\Lease;
use App\Models\LeaseAuditLog;
use App\Services\LeaseHealthService;
use Filament\Widgets\Widget;

class LeaseJourneyStepperWidget extends Widget
{
    protected string $view = 'filament.resources.leases.widgets.lease-journey-stepper-widget';

    protected int|string|array $columnSpan = 'full';

    public ?Lease $record = null;

    public function mount(): void
    {
        if ($this->record === null) {
            $id = request()->route('record');
            if ($id) {
                $this->record = Lease::with(['tenant', 'digitalSignatures', 'approvals', 'auditLogs'])->find($id);
            }
        }
    }

    public function getViewData(): array
    {
        $lease = $this->record;

        return [
            'macroSteps'        => $this->buildMacroSteps($lease),
            'detailSteps'       => $this->buildDetailSteps($lease),
            'progress'          => $this->buildProgress($lease),
            'currentStateLabel' => $this->buildStateLabel($lease),
            'currentStateColor' => $this->buildStateColor($lease),
            'health'            => $lease ? LeaseHealthService::score($lease) : ['score' => 0, 'grade' => 'F', 'flags' => []],
        ];
    }

    private function getMacroPhase(string $state): int
    {
        return match ($state) {
            'draft', 'received'                                                        => 1,
            'pending_landlord_approval', 'approved'                                    => 2,
            'printed', 'checked_out', 'sent_digital',
            'pending_otp', 'pending_tenant_signature', 'returned_unsigned'             => 3,
            'tenant_signed', 'with_lawyer', 'pending_upload'                           => 4,
            'pending_deposit'                                                           => 5,
            'active', 'renewal_offered', 'renewal_accepted'                            => 6,
            'expired', 'terminated', 'cancelled', 'renewal_declined', 'archived'       => 7,
            'disputed'                                                                  => -1,
            default                                                                     => 1,
        };
    }

    private function buildMacroSteps(?Lease $lease): array
    {
        $labels = [1 => 'Draft', 2 => 'Landlord Approved', 3 => 'Sent to Tenant', 4 => 'Tenant Signed', 5 => 'Countersigned', 6 => 'Active', 7 => 'Closed'];

        if (! $lease) {
            return array_map(fn ($phase, $label) => ['phase' => $phase, 'label' => $label, 'completed' => false, 'current' => $phase === 1, 'disputed' => false, 'timestamp' => null], array_keys($labels), $labels);
        }

        $currentPhase = $this->getMacroPhase($lease->workflow_state);
        $isDisputed   = $lease->workflow_state === 'disputed';
        $timestamps   = $this->phaseTimestamps($lease);
        $steps        = [];

        for ($phase = 1; $phase <= 7; $phase++) {
            $steps[] = [
                'phase'     => $phase,
                'label'     => $labels[$phase],
                'completed' => ! $isDisputed && $currentPhase > $phase,
                'current'   => ! $isDisputed && $currentPhase === $phase,
                'disputed'  => $isDisputed && $phase === 3,
                'timestamp' => $timestamps[$phase] ?? null,
            ];
        }

        return $steps;
    }

    private function phaseTimestamps(Lease $lease): array
    {
        $map = [
            'draft' => 1, 'received' => 1,
            'pending_landlord_approval' => 2, 'approved' => 2,
            'printed' => 3, 'checked_out' => 3, 'sent_digital' => 3,
            'pending_otp' => 3, 'pending_tenant_signature' => 3, 'returned_unsigned' => 3,
            'tenant_signed' => 4, 'with_lawyer' => 4, 'pending_upload' => 4,
            'pending_deposit' => 5,
            'active' => 6, 'renewal_offered' => 6, 'renewal_accepted' => 6,
            'expired' => 7, 'terminated' => 7, 'cancelled' => 7, 'renewal_declined' => 7, 'archived' => 7,
        ];

        $result = [];
        foreach (LeaseAuditLog::where('lease_id', $lease->id)->whereNotNull('new_state')->orderBy('created_at')->get() as $log) {
            $phase = $map[$log->new_state] ?? null;
            if ($phase && ! isset($result[$phase])) {
                $result[$phase] = $log->created_at->format('j M Y, g:i A');
            }
        }

        return $result;
    }

    private function buildDetailSteps(?Lease $lease): array
    {
        $defs = [
            ['title' => 'Create Lease',          'description' => 'Draft created',                   'states' => ['draft', 'received']],
            ['title' => 'Register & Assign Zone', 'description' => 'Zone and field officer assigned', 'states' => ['pending_landlord_approval']],
            ['title' => 'Landlord Approval',      'description' => 'Landlord has approved',           'states' => ['approved']],
            ['title' => 'Send Signing Link',      'description' => 'Link sent to tenant',             'states' => ['sent_digital']],
            ['title' => 'OTP Verification',       'description' => 'Tenant verifies with OTP',        'states' => ['pending_otp']],
            ['title' => 'Tenant Reviews Lease',   'description' => 'Tenant reviews document',         'states' => ['pending_tenant_signature']],
            ['title' => 'Tenant Signs',           'description' => 'Tenant has signed',               'states' => ['tenant_signed']],
            ['title' => 'Manager Countersigns',   'description' => 'Manager countersigns',            'states' => ['pending_deposit']],
            ['title' => 'Deposit & Activation',   'description' => 'Lease active',                    'states' => ['active', 'renewal_offered', 'renewal_accepted']],
            ['title' => 'Closed',                 'description' => 'Lease ended or archived',         'states' => ['expired', 'terminated', 'cancelled', 'renewal_declined', 'archived']],
        ];

        $workflowState = $lease?->workflow_state ?? 'draft';
        $stateOrder    = array_flip(array_map(fn ($c) => $c->value, LeaseWorkflowState::cases()));
        $currentOrder  = $stateOrder[$workflowState] ?? 999;
        $timestamps    = $lease ? LeaseAuditLog::where('lease_id', $lease->id)->whereNotNull('new_state')->orderBy('created_at')->get()->unique('new_state')->mapWithKeys(fn ($l) => [$l->new_state => $l->created_at->format('j M Y, g:i A')])->all() : [];

        $steps = [];
        foreach ($defs as $i => $def) {
            $done = $active = $actionRequired = false;
            foreach ($def['states'] as $s) {
                if ($s === $workflowState) { $active = true; break; }
                if (($stateOrder[$s] ?? 999) < $currentOrder) { $done = true; }
            }
            if ($workflowState === 'disputed') {
                $active = in_array('pending_otp', $def['states']) || in_array('pending_tenant_signature', $def['states']);
                $actionRequired = $active;
            }
            if ($workflowState === 'returned_unsigned') {
                $actionRequired = in_array('pending_tenant_signature', $def['states']);
            }
            $ts = null;
            foreach ($def['states'] as $s) {
                if (isset($timestamps[$s])) { $ts = $timestamps[$s]; break; }
            }
            $steps[] = [
                'number'      => $i + 1,
                'title'       => $def['title'],
                'description' => $def['description'],
                'status'      => $actionRequired ? 'action_required' : ($active ? 'active' : ($done ? 'done' : 'pending')),
                'timestamp'   => $ts,
            ];
        }

        return $steps;
    }

    private function buildProgress(?Lease $lease): int
    {
        if (! $lease) return 0;
        $phase = $this->getMacroPhase($lease->workflow_state);
        return $phase <= 0 ? 0 : (int) round(($phase / 7) * 100);
    }

    private function buildStateLabel(?Lease $lease): string
    {
        if (! $lease) return 'Draft';
        try {
            return LeaseWorkflowState::from($lease->workflow_state)->label();
        } catch (\ValueError) {
            return ucwords(str_replace('_', ' ', $lease->workflow_state));
        }
    }

    private function buildStateColor(?Lease $lease): string
    {
        if (! $lease) return 'gray';
        try {
            return LeaseWorkflowState::from($lease->workflow_state)->color();
        } catch (\ValueError) {
            return 'gray';
        }
    }
}
