<?php

namespace App\Actions\Lease;

use App\Enums\LeaseWorkflowState;
use App\Exceptions\InvalidLeaseTransitionException;
use App\Models\Lease;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Action class for transitioning lease workflow state.
 * Encapsulates the state transition logic with validation and audit logging.
 */
class TransitionLeaseState
{
    /**
     * Execute the state transition.
     *
     * @param array|null $additionalData Extra data to log
     *
     * @throws InvalidLeaseTransitionException
     */
    public function execute(
        Lease $lease,
        string|LeaseWorkflowState $newState,
        ?array $additionalData = null,
    ): bool {
        $newStateValue = $newState instanceof LeaseWorkflowState ? $newState->value : $newState;
        $newStateEnum = $newState instanceof LeaseWorkflowState
            ? $newState
            : LeaseWorkflowState::tryFrom($newState);

        if (! $newStateEnum) {
            throw new InvalidLeaseTransitionException($lease->workflow_state, $newStateValue);
        }

        $currentState = LeaseWorkflowState::from($lease->workflow_state);

        if (! $currentState->canTransitionTo($newStateEnum)) {
            throw new InvalidLeaseTransitionException($lease->workflow_state, $newStateValue);
        }

        return DB::transaction(function () use ($lease, $newStateValue, $additionalData) {
            $oldState = $lease->workflow_state;

            $lease->workflow_state = $newStateValue;
            $lease->save();

            $this->logTransition($lease, $oldState, $newStateValue, $additionalData);

            return true;
        });
    }

    /**
     * Check if a transition is valid without executing it.
     */
    public function canTransition(Lease $lease, string|LeaseWorkflowState $newState): bool
    {
        $newStateEnum = $newState instanceof LeaseWorkflowState
            ? $newState
            : LeaseWorkflowState::tryFrom($newState);

        if (! $newStateEnum) {
            return false;
        }

        $currentState = LeaseWorkflowState::tryFrom($lease->workflow_state);

        if (! $currentState) {
            return false;
        }

        return $currentState->canTransitionTo($newStateEnum);
    }

    /**
     * Get valid next states for a lease.
     *
     * @return array<LeaseWorkflowState>
     */
    public function getValidNextStates(Lease $lease): array
    {
        $currentState = LeaseWorkflowState::tryFrom($lease->workflow_state);

        if (! $currentState) {
            return [];
        }

        return $currentState->validTransitions();
    }

    /**
     * Log the state transition to audit log.
     */
    protected function logTransition(
        Lease $lease,
        string $oldState,
        string $newState,
        ?array $additionalData = null,
    ): void {
        $lease->auditLogs()->create([
            'action' => 'state_transition',
            'old_state' => $oldState,
            'new_state' => $newState,
            'user_id' => Auth::id(),
            'user_role_at_time' => Auth::user()?->roles?->first()?->name ?? 'system',
            'ip_address' => request()->ip(),
            'additional_data' => $additionalData,
            'description' => "Transitioned from {$oldState} to {$newState}",
        ]);
    }
}
