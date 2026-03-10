<?php

namespace Tests\Feature;

use App\Enums\LeaseWorkflowState;
use App\Exceptions\InvalidLeaseTransitionException;
use App\Models\Landlord;
use App\Models\Lease;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * End-to-end test for the full lease workflow lifecycle.
 *
 * Covers: draft → approved → printed → checked_out → tenant_signed → pending_deposit → active → expired → archived
 *
 * Uses RefreshDatabase to run against the dedicated chabrin_leases_testing database.
 * Mail, Notification, and Event facades are faked to prevent network calls.
 */
class EndToEndLeaseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Prevent any real outbound network calls during tests.
        Mail::fake();
        Notification::fake();
        Event::fake();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createAdminUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'block' => 0,
        ]);
    }

    private function createFullLease(array $overrides = []): Lease
    {
        $zone = Zone::factory()->create();
        $landlord = Landlord::factory()->create();
        $property = Property::factory()->create(['landlord_id' => $landlord->id]);
        $unit = Unit::factory()->create(['property_id' => $property->id]);
        $tenant = Tenant::factory()->create();

        return Lease::factory()->create(array_merge([
            'workflow_state' => LeaseWorkflowState::DRAFT->value,
            'tenant_id' => $tenant->id,
            'landlord_id' => $landlord->id,
            'property_id' => $property->id,
            'unit_id' => $unit->id,
            'zone_id' => $zone->id,
            'monthly_rent' => '45000.00',
            'deposit_amount' => '90000.00',
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // 1. Lease Creation
    // -------------------------------------------------------------------------

    public function test_lease_can_be_created_in_draft_state(): void
    {
        $lease = $this->createFullLease();

        $this->assertDatabaseHas('leases', ['id' => $lease->id, 'workflow_state' => 'draft']);
        $this->assertEquals(LeaseWorkflowState::DRAFT, $lease->getWorkflowStateEnum());
        $this->assertFalse($lease->isInTerminalState());
    }

    public function test_new_lease_is_not_in_terminal_state(): void
    {
        $lease = $this->createFullLease();

        $this->assertFalse($lease->isInTerminalState());
        $this->assertFalse($lease->isInActiveState());
    }

    // -------------------------------------------------------------------------
    // 2. Draft → Approved (skipping landlord approval)
    // -------------------------------------------------------------------------

    public function test_draft_lease_can_transition_to_approved(): void
    {
        $lease = $this->createFullLease(['workflow_state' => LeaseWorkflowState::DRAFT->value]);

        $this->assertTrue($lease->canTransitionTo('approved'));

        $lease->transitionTo('approved');

        $this->assertEquals('approved', $lease->fresh()->workflow_state);
    }

    public function test_draft_to_approved_creates_audit_log(): void
    {
        $lease = $this->createFullLease(['workflow_state' => LeaseWorkflowState::DRAFT->value]);

        $lease->transitionTo('approved');

        $this->assertDatabaseHas('lease_audit_logs', [
            'lease_id' => $lease->id,
            'action' => 'state_transition',
            'old_state' => 'draft',
            'new_state' => 'approved',
        ]);
    }

    // -------------------------------------------------------------------------
    // 3. Draft → Pending Landlord Approval → Approved
    // -------------------------------------------------------------------------

    public function test_draft_can_go_through_landlord_approval_path(): void
    {
        $lease = $this->createFullLease(['workflow_state' => LeaseWorkflowState::DRAFT->value]);

        $lease->transitionTo('pending_landlord_approval');
        $this->assertEquals('pending_landlord_approval', $lease->fresh()->workflow_state);

        $lease->transitionTo('approved');
        $this->assertEquals('approved', $lease->fresh()->workflow_state);
    }

    public function test_pending_landlord_approval_state_requires_landlord_action(): void
    {
        $state = LeaseWorkflowState::PENDING_LANDLORD_APPROVAL;

        $this->assertTrue($state->requiresLandlordAction());
    }

    // -------------------------------------------------------------------------
    // 4. Approved → Printed → Checked Out → Tenant Signed
    // -------------------------------------------------------------------------

    public function test_approved_lease_can_be_printed(): void
    {
        $lease = $this->createFullLease(['workflow_state' => LeaseWorkflowState::APPROVED->value]);

        $lease->transitionTo('printed');

        $this->assertEquals('printed', $lease->fresh()->workflow_state);
    }

    public function test_printed_lease_can_be_checked_out(): void
    {
        $lease = $this->createFullLease(['workflow_state' => LeaseWorkflowState::PRINTED->value]);

        $lease->transitionTo('checked_out');

        $this->assertEquals('checked_out', $lease->fresh()->workflow_state);
    }

    public function test_checked_out_lease_can_be_tenant_signed(): void
    {
        $lease = $this->createFullLease(['workflow_state' => LeaseWorkflowState::CHECKED_OUT->value]);

        $lease->transitionTo('pending_tenant_signature');
        $lease->transitionTo('tenant_signed');

        $this->assertEquals('tenant_signed', $lease->fresh()->workflow_state);
    }

    // -------------------------------------------------------------------------
    // 5. Digital Signing Path: Approved → Sent Digital → Pending OTP → Tenant Signed
    // -------------------------------------------------------------------------

    public function test_approved_lease_can_be_sent_digitally(): void
    {
        $lease = $this->createFullLease(['workflow_state' => LeaseWorkflowState::APPROVED->value]);

        $lease->transitionTo('sent_digital');

        $this->assertEquals('sent_digital', $lease->fresh()->workflow_state);
    }

    public function test_digital_lease_transitions_through_otp_to_signed(): void
    {
        $lease = $this->createFullLease(['workflow_state' => LeaseWorkflowState::APPROVED->value]);

        $lease->transitionTo('sent_digital');
        $lease->transitionTo('pending_otp');
        $lease->transitionTo('tenant_signed');

        $this->assertEquals('tenant_signed', $lease->fresh()->workflow_state);
    }

    // -------------------------------------------------------------------------
    // 6. Tenant Signed → Pending Deposit → Active
    // -------------------------------------------------------------------------

    public function test_tenant_signed_lease_can_go_to_pending_deposit(): void
    {
        $lease = $this->createFullLease(['workflow_state' => LeaseWorkflowState::TENANT_SIGNED->value]);

        $lease->transitionTo('pending_deposit');

        $this->assertEquals('pending_deposit', $lease->fresh()->workflow_state);
    }

    public function test_pending_deposit_lease_can_become_active(): void
    {
        $lease = $this->createFullLease(['workflow_state' => LeaseWorkflowState::PENDING_DEPOSIT->value]);

        $lease->transitionTo('active');

        $this->assertEquals('active', $lease->fresh()->workflow_state);
        $this->assertTrue($lease->fresh()->isInActiveState());
    }

    public function test_full_physical_signing_path_draft_to_active(): void
    {
        $lease = $this->createFullLease(['workflow_state' => LeaseWorkflowState::DRAFT->value]);

        $transitions = [
            'approved',
            'printed',
            'checked_out',
            'pending_tenant_signature',
            'tenant_signed',
            'pending_deposit',
            'active',
        ];

        foreach ($transitions as $state) {
            $lease->transitionTo($state);
            $this->assertEquals($state, $lease->fresh()->workflow_state, "Failed at transition to: {$state}");
        }

        $this->assertTrue($lease->fresh()->isInActiveState());
    }

    public function test_full_digital_signing_path_draft_to_active(): void
    {
        $lease = $this->createFullLease(['workflow_state' => LeaseWorkflowState::DRAFT->value]);

        $transitions = [
            'approved',
            'sent_digital',
            'pending_otp',
            'tenant_signed',
            'pending_deposit',
            'active',
        ];

        foreach ($transitions as $state) {
            $lease->transitionTo($state);
            $this->assertEquals($state, $lease->fresh()->workflow_state, "Failed at transition to: {$state}");
        }

        $this->assertTrue($lease->fresh()->isInActiveState());
    }

    // -------------------------------------------------------------------------
    // 7. Active → Expired → Archived
    // -------------------------------------------------------------------------

    public function test_active_lease_can_expire(): void
    {
        $lease = $this->createFullLease(['workflow_state' => LeaseWorkflowState::ACTIVE->value]);

        $lease->transitionTo('expired');

        $this->assertEquals('expired', $lease->fresh()->workflow_state);
    }

    public function test_expired_lease_can_be_archived(): void
    {
        $lease = $this->createFullLease(['workflow_state' => LeaseWorkflowState::EXPIRED->value]);

        $lease->transitionTo('archived');

        $this->assertEquals('archived', $lease->fresh()->workflow_state);
        $this->assertTrue($lease->fresh()->isInTerminalState());
    }

    // -------------------------------------------------------------------------
    // 8. Cancellation at various stages
    // -------------------------------------------------------------------------

    public function test_draft_lease_can_be_cancelled(): void
    {
        $lease = $this->createFullLease(['workflow_state' => LeaseWorkflowState::DRAFT->value]);

        $lease->transitionTo('cancelled');

        $this->assertEquals('cancelled', $lease->fresh()->workflow_state);
        $this->assertTrue($lease->fresh()->isInTerminalState());
    }

    public function test_approved_lease_can_be_cancelled(): void
    {
        $lease = $this->createFullLease(['workflow_state' => LeaseWorkflowState::APPROVED->value]);

        $lease->transitionTo('cancelled');

        $this->assertEquals('cancelled', $lease->fresh()->workflow_state);
    }

    public function test_cancelled_lease_can_be_archived(): void
    {
        $lease = $this->createFullLease(['workflow_state' => LeaseWorkflowState::CANCELLED->value]);

        $lease->transitionTo('archived');

        $this->assertEquals('archived', $lease->fresh()->workflow_state);
    }

    // -------------------------------------------------------------------------
    // 9. Invalid transition protection
    // -------------------------------------------------------------------------

    public function test_draft_cannot_jump_directly_to_active(): void
    {
        $lease = $this->createFullLease(['workflow_state' => LeaseWorkflowState::DRAFT->value]);

        $this->expectException(InvalidLeaseTransitionException::class);

        $lease->transitionTo('active');
    }

    public function test_active_lease_cannot_go_back_to_draft(): void
    {
        $lease = $this->createFullLease(['workflow_state' => LeaseWorkflowState::ACTIVE->value]);

        $this->expectException(InvalidLeaseTransitionException::class);

        $lease->transitionTo('draft');
    }

    public function test_archived_lease_cannot_transition_anywhere(): void
    {
        $lease = $this->createFullLease(['workflow_state' => LeaseWorkflowState::ARCHIVED->value]);

        $this->assertFalse($lease->canTransitionTo('active'));
        $this->assertFalse($lease->canTransitionTo('draft'));
        $this->assertFalse($lease->canTransitionTo('expired'));
        $this->assertEmpty($lease->getValidNextStates());
    }

    public function test_canTransitionTo_returns_false_for_invalid_state_string(): void
    {
        $lease = $this->createFullLease(['workflow_state' => LeaseWorkflowState::DRAFT->value]);

        $this->assertFalse($lease->canTransitionTo('nonexistent_state'));
    }

    // -------------------------------------------------------------------------
    // 10. Audit trail accumulates across full workflow
    // -------------------------------------------------------------------------

    public function test_audit_log_accumulates_across_workflow(): void
    {
        $lease = $this->createFullLease(['workflow_state' => LeaseWorkflowState::DRAFT->value]);

        $lease->transitionTo('approved');
        $lease->transitionTo('printed');
        $lease->transitionTo('checked_out');

        $logs = $lease->auditLogs()->where('action', 'state_transition')->get();

        $this->assertCount(3, $logs);
        $this->assertEquals('draft', $logs[0]->old_state);
        $this->assertEquals('approved', $logs[0]->new_state);
        $this->assertEquals('approved', $logs[1]->old_state);
        $this->assertEquals('printed', $logs[1]->new_state);
        $this->assertEquals('printed', $logs[2]->old_state);
        $this->assertEquals('checked_out', $logs[2]->new_state);
    }

    // -------------------------------------------------------------------------
    // 11. Query scopes work correctly
    // -------------------------------------------------------------------------

    public function test_active_scope_only_returns_active_leases(): void
    {
        $this->createFullLease(['workflow_state' => 'draft']);
        $this->createFullLease(['workflow_state' => 'active']);
        $this->createFullLease(['workflow_state' => 'active']);
        $this->createFullLease(['workflow_state' => 'expired']);

        $this->assertEquals(2, Lease::active()->count());
    }

    public function test_pending_scope_covers_expected_states(): void
    {
        $this->createFullLease(['workflow_state' => 'draft']);
        $this->createFullLease(['workflow_state' => 'pending_deposit']);
        $this->createFullLease(['workflow_state' => 'active']);

        // draft and pending_deposit are both "pending"
        $this->assertGreaterThanOrEqual(2, Lease::pending()->count());
    }

    public function test_expiring_soon_scope_respects_date_threshold(): void
    {
        $this->createFullLease([
            'workflow_state' => 'active',
            'end_date' => now()->addDays(10),
        ]);
        $this->createFullLease([
            'workflow_state' => 'active',
            'end_date' => now()->addDays(90),
        ]);

        $this->assertEquals(1, Lease::expiringSoon(30)->count());
    }

    public function test_not_terminal_scope_excludes_archived_expired_cancelled(): void
    {
        $this->createFullLease(['workflow_state' => 'active']);
        $this->createFullLease(['workflow_state' => 'draft']);
        $this->createFullLease(['workflow_state' => 'archived']);
        $this->createFullLease(['workflow_state' => 'expired']);
        $this->createFullLease(['workflow_state' => 'cancelled']);

        $results = Lease::notTerminal()->get();

        $this->assertEquals(2, $results->count());
        $results->each(function ($lease) {
            $this->assertFalse(
                LeaseWorkflowState::from($lease->workflow_state)->isTerminal(),
                "Expected non-terminal, got: {$lease->workflow_state}"
            );
        });
    }

    // -------------------------------------------------------------------------
    // 12. Renewal path
    // -------------------------------------------------------------------------

    public function test_active_lease_can_enter_renewal_path(): void
    {
        $lease = $this->createFullLease(['workflow_state' => LeaseWorkflowState::ACTIVE->value]);

        $lease->transitionTo('renewal_offered');
        $this->assertEquals('renewal_offered', $lease->fresh()->workflow_state);
        $this->assertTrue($lease->fresh()->isInActiveState());

        $lease->transitionTo('renewal_accepted');
        $this->assertEquals('renewal_accepted', $lease->fresh()->workflow_state);

        $lease->transitionTo('active');
        $this->assertEquals('active', $lease->fresh()->workflow_state);
    }

    public function test_declined_renewal_leads_to_expiry(): void
    {
        $lease = $this->createFullLease(['workflow_state' => LeaseWorkflowState::ACTIVE->value]);

        $lease->transitionTo('renewal_offered');
        $lease->transitionTo('renewal_declined');
        $lease->transitionTo('expired');

        $this->assertEquals('expired', $lease->fresh()->workflow_state);
    }

    // -------------------------------------------------------------------------
    // 13. Dispute path
    // -------------------------------------------------------------------------

    public function test_sent_digital_lease_can_enter_dispute(): void
    {
        $lease = $this->createFullLease(['workflow_state' => LeaseWorkflowState::SENT_DIGITAL->value]);

        $lease->transitionTo('disputed');
        $this->assertEquals('disputed', $lease->fresh()->workflow_state);

        // Can be re-sent or cancelled from disputed
        $this->assertTrue($lease->canTransitionTo('sent_digital'));
        $this->assertTrue($lease->canTransitionTo('cancelled'));
    }

    // -------------------------------------------------------------------------
    // 14. Financial data integrity
    // -------------------------------------------------------------------------

    public function test_lease_stores_monetary_values_as_decimal(): void
    {
        $lease = $this->createFullLease([
            'monthly_rent' => '45000.00',
            'deposit_amount' => '90000.00',
        ]);

        $fresh = $lease->fresh();

        $this->assertEquals('45000.00', $fresh->monthly_rent);
        $this->assertEquals('90000.00', $fresh->deposit_amount);
    }

    // -------------------------------------------------------------------------
    // 15. No mail/notification/events fired during workflow (all faked)
    // -------------------------------------------------------------------------

    public function test_no_real_mail_is_sent_during_workflow_transitions(): void
    {
        $lease = $this->createFullLease(['workflow_state' => LeaseWorkflowState::DRAFT->value]);

        $lease->transitionTo('approved');
        $lease->transitionTo('sent_digital');
        $lease->transitionTo('pending_otp');

        // If any real mail had been attempted, it would have thrown.
        // Mail::fake() ensures this is a clean no-op.
        Mail::assertNothingSent();
    }
}
