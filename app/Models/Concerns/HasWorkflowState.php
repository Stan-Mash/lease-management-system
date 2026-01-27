<?php

namespace App\Models\Concerns;

use App\Actions\Lease\TransitionLeaseState;
use App\Enums\LeaseWorkflowState;
use App\Exceptions\InvalidLeaseTransitionException;

/**
 * Trait for models that have workflow state management.
 */
trait HasWorkflowState
{
    /**
     * Get the current workflow state as an enum.
     */
    public function getWorkflowStateEnum(): LeaseWorkflowState
    {
        return LeaseWorkflowState::from($this->workflow_state);
    }

    /**
     * Check if transition to a new state is valid.
     *
     * @param string|LeaseWorkflowState $newState
     * @return bool
     */
    public function canTransitionTo(string|LeaseWorkflowState $newState): bool
    {
        if (is_string($newState)) {
            $newState = LeaseWorkflowState::tryFrom($newState);
            if (!$newState) {
                return false;
            }
        }

        return $this->getWorkflowStateEnum()->canTransitionTo($newState);
    }

    /**
     * Transition to a new workflow state.
     *
     * @param string|LeaseWorkflowState $newState
     * @return bool
     * @throws InvalidLeaseTransitionException
     */
    public function transitionTo(string|LeaseWorkflowState $newState): bool
    {
        return app(TransitionLeaseState::class)->execute($this, $newState);
    }

    /**
     * Get valid next states for this model.
     *
     * @return array<LeaseWorkflowState>
     */
    public function getValidNextStates(): array
    {
        return $this->getWorkflowStateEnum()->validTransitions();
    }

    /**
     * Check if the current state is active.
     */
    public function isInActiveState(): bool
    {
        return $this->getWorkflowStateEnum()->isActive();
    }

    /**
     * Check if the current state is terminal.
     */
    public function isInTerminalState(): bool
    {
        return $this->getWorkflowStateEnum()->isTerminal();
    }

    /**
     * Get the workflow state label for display.
     */
    public function getWorkflowStateLabel(): string
    {
        return $this->getWorkflowStateEnum()->label();
    }

    /**
     * Get the workflow state color for UI.
     */
    public function getWorkflowStateColor(): string
    {
        return $this->getWorkflowStateEnum()->color();
    }

    /**
     * Get the workflow state icon.
     */
    public function getWorkflowStateIcon(): string
    {
        return $this->getWorkflowStateEnum()->icon();
    }
}
