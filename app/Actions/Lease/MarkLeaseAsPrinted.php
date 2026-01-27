<?php

namespace App\Actions\Lease;

use App\Enums\LeaseWorkflowState;
use App\Models\Lease;
use App\Models\LeasePrintLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Action class for marking a lease as printed.
 */
class MarkLeaseAsPrinted
{
    public function __construct(
        protected TransitionLeaseState $transitionAction
    ) {}

    /**
     * Execute the print action.
     *
     * @param Lease $lease
     * @param string|null $workstation Optional workstation identifier
     * @param int $copies Number of copies printed
     * @param string|null $reason Reason for printing
     * @return LeasePrintLog
     */
    public function execute(
        Lease $lease,
        ?string $workstation = null,
        int $copies = 1,
        ?string $reason = null
    ): LeasePrintLog {
        return DB::transaction(function () use ($lease, $workstation, $copies, $reason) {
            $oldState = $lease->workflow_state;

            // Transition to printed state
            $this->transitionAction->execute($lease, LeaseWorkflowState::PRINTED);

            // Create print log entry
            $printLog = LeasePrintLog::logPrint(
                leaseId: $lease->id,
                userId: Auth::id() ?? 0,
                copies: $copies,
                workstation: $workstation,
                ipAddress: request()->ip(),
                reason: $reason
            );

            // Log to audit trail
            $lease->auditLogs()->create([
                'action' => 'printed',
                'old_state' => $oldState,
                'new_state' => LeaseWorkflowState::PRINTED->value,
                'user_id' => Auth::id(),
                'user_role_at_time' => Auth::user()?->role ?? 'unknown',
                'ip_address' => request()->ip(),
                'additional_data' => [
                    'print_log_id' => $printLog->id,
                    'workstation' => $workstation ?? gethostname(),
                    'copies' => $copies,
                    'reason' => $reason,
                    'printed_at' => now()->toIso8601String(),
                ],
                'description' => 'Lease printed at workstation',
            ]);

            return $printLog;
        });
    }
}
