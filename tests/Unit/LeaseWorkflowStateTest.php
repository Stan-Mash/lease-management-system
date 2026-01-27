<?php

namespace Tests\Unit;

use App\Enums\LeaseWorkflowState;
use PHPUnit\Framework\TestCase;

class LeaseWorkflowStateTest extends TestCase
{
    public function test_draft_can_transition_to_pending_landlord_approval(): void
    {
        $state = LeaseWorkflowState::DRAFT;

        $this->assertTrue($state->canTransitionTo(LeaseWorkflowState::PENDING_LANDLORD_APPROVAL));
    }

    public function test_draft_can_transition_to_approved(): void
    {
        $state = LeaseWorkflowState::DRAFT;

        $this->assertTrue($state->canTransitionTo(LeaseWorkflowState::APPROVED));
    }

    public function test_draft_can_transition_to_cancelled(): void
    {
        $state = LeaseWorkflowState::DRAFT;

        $this->assertTrue($state->canTransitionTo(LeaseWorkflowState::CANCELLED));
    }

    public function test_draft_cannot_transition_to_active(): void
    {
        $state = LeaseWorkflowState::DRAFT;

        $this->assertFalse($state->canTransitionTo(LeaseWorkflowState::ACTIVE));
    }

    public function test_pending_landlord_approval_can_transition_to_approved(): void
    {
        $state = LeaseWorkflowState::PENDING_LANDLORD_APPROVAL;

        $this->assertTrue($state->canTransitionTo(LeaseWorkflowState::APPROVED));
    }

    public function test_approved_can_transition_to_printed_or_sent_digital(): void
    {
        $state = LeaseWorkflowState::APPROVED;

        $this->assertTrue($state->canTransitionTo(LeaseWorkflowState::PRINTED));
        $this->assertTrue($state->canTransitionTo(LeaseWorkflowState::SENT_DIGITAL));
    }

    public function test_active_can_transition_to_expired_or_terminated(): void
    {
        $state = LeaseWorkflowState::ACTIVE;

        $this->assertTrue($state->canTransitionTo(LeaseWorkflowState::EXPIRED));
        $this->assertTrue($state->canTransitionTo(LeaseWorkflowState::TERMINATED));
        $this->assertTrue($state->canTransitionTo(LeaseWorkflowState::RENEWAL_OFFERED));
    }

    public function test_archived_has_no_valid_transitions(): void
    {
        $state = LeaseWorkflowState::ARCHIVED;

        $this->assertEmpty($state->validTransitions());
    }

    public function test_label_returns_human_readable_string(): void
    {
        $this->assertEquals('Draft', LeaseWorkflowState::DRAFT->label());
        $this->assertEquals('Pending Landlord Approval', LeaseWorkflowState::PENDING_LANDLORD_APPROVAL->label());
        $this->assertEquals('Active', LeaseWorkflowState::ACTIVE->label());
    }

    public function test_color_returns_valid_color_string(): void
    {
        $this->assertEquals('gray', LeaseWorkflowState::DRAFT->color());
        $this->assertEquals('warning', LeaseWorkflowState::PENDING_LANDLORD_APPROVAL->color());
        $this->assertEquals('success', LeaseWorkflowState::ACTIVE->color());
        $this->assertEquals('danger', LeaseWorkflowState::CANCELLED->color());
    }

    public function test_icon_returns_valid_icon_string(): void
    {
        $this->assertStringStartsWith('heroicon-o-', LeaseWorkflowState::DRAFT->icon());
        $this->assertStringStartsWith('heroicon-o-', LeaseWorkflowState::ACTIVE->icon());
    }

    public function test_is_active_returns_true_for_active_states(): void
    {
        $this->assertTrue(LeaseWorkflowState::ACTIVE->isActive());
        $this->assertTrue(LeaseWorkflowState::RENEWAL_OFFERED->isActive());
        $this->assertFalse(LeaseWorkflowState::DRAFT->isActive());
    }

    public function test_is_terminal_returns_true_for_terminal_states(): void
    {
        $this->assertTrue(LeaseWorkflowState::EXPIRED->isTerminal());
        $this->assertTrue(LeaseWorkflowState::TERMINATED->isTerminal());
        $this->assertTrue(LeaseWorkflowState::CANCELLED->isTerminal());
        $this->assertTrue(LeaseWorkflowState::ARCHIVED->isTerminal());
        $this->assertFalse(LeaseWorkflowState::ACTIVE->isTerminal());
    }

    public function test_requires_landlord_action(): void
    {
        $this->assertTrue(LeaseWorkflowState::PENDING_LANDLORD_APPROVAL->requiresLandlordAction());
        $this->assertFalse(LeaseWorkflowState::DRAFT->requiresLandlordAction());
    }

    public function test_requires_tenant_action(): void
    {
        $this->assertTrue(LeaseWorkflowState::PENDING_OTP->requiresTenantAction());
        $this->assertTrue(LeaseWorkflowState::PENDING_TENANT_SIGNATURE->requiresTenantAction());
        $this->assertTrue(LeaseWorkflowState::PENDING_DEPOSIT->requiresTenantAction());
        $this->assertFalse(LeaseWorkflowState::DRAFT->requiresTenantAction());
    }

    public function test_options_returns_all_states_as_array(): void
    {
        $options = LeaseWorkflowState::options();

        $this->assertIsArray($options);
        $this->assertArrayHasKey('draft', $options);
        $this->assertArrayHasKey('active', $options);
        $this->assertEquals('Draft', $options['draft']);
        $this->assertEquals('Active', $options['active']);
    }

    public function test_active_states_excludes_terminal_states(): void
    {
        $activeStates = LeaseWorkflowState::activeStates();

        foreach ($activeStates as $state) {
            $this->assertFalse($state->isTerminal());
        }
    }

    public function test_all_states_have_labels(): void
    {
        foreach (LeaseWorkflowState::cases() as $state) {
            $this->assertNotEmpty($state->label());
        }
    }

    public function test_all_states_have_colors(): void
    {
        foreach (LeaseWorkflowState::cases() as $state) {
            $this->assertNotEmpty($state->color());
        }
    }

    public function test_all_states_have_icons(): void
    {
        foreach (LeaseWorkflowState::cases() as $state) {
            $this->assertNotEmpty($state->icon());
        }
    }

    public function test_from_string_creates_enum(): void
    {
        $state = LeaseWorkflowState::from('draft');

        $this->assertEquals(LeaseWorkflowState::DRAFT, $state);
    }

    public function test_try_from_returns_null_for_invalid_string(): void
    {
        $state = LeaseWorkflowState::tryFrom('invalid_state');

        $this->assertNull($state);
    }
}
