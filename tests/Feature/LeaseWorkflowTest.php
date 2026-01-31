<?php

namespace Tests\Feature;

use App\Exceptions\InvalidLeaseTransitionException;
use App\Models\Lease;
use Tests\TestCase;

class LeaseWorkflowTest extends TestCase
{
    public function test_lease_can_transition_from_draft_to_approved(): void
    {
        $lease = Lease::factory()->create(['workflow_state' => 'draft']);

        $this->assertTrue($lease->canTransitionTo('approved'));
        $lease->transitionTo('approved');

        $this->assertEquals('approved', $lease->fresh()->workflow_state);
    }

    public function test_lease_cannot_make_invalid_transition(): void
    {
        $lease = Lease::factory()->create(['workflow_state' => 'draft']);

        $this->expectException(InvalidLeaseTransitionException::class);
        $lease->transitionTo('active'); // Invalid from draft
    }

    public function test_lease_transitions_are_logged(): void
    {
        $lease = Lease::factory()->create(['workflow_state' => 'draft']);

        $lease->transitionTo('approved');

        $this->assertTrue($lease->auditLogs()->exists());
        $auditLog = $lease->auditLogs()->first();
        $this->assertEquals('state_transition', $auditLog->action);
        $this->assertEquals('draft', $auditLog->old_state);
        $this->assertEquals('approved', $auditLog->new_state);
    }

    public function test_lease_active_scope(): void
    {
        Lease::factory()->create(['workflow_state' => 'draft']);
        Lease::factory()->create(['workflow_state' => 'active']);
        Lease::factory()->create(['workflow_state' => 'active']);

        $activeCount = Lease::active()->count();

        $this->assertEquals(2, $activeCount);
    }

    public function test_lease_expiring_soon_scope(): void
    {
        Lease::factory()->create([
            'workflow_state' => 'active',
            'end_date' => now()->addDays(10),
        ]);
        Lease::factory()->create([
            'workflow_state' => 'active',
            'end_date' => now()->addDays(90),
        ]);

        $expiringSoon = Lease::expiringSoon()->count();

        $this->assertEquals(1, $expiringSoon);
    }

    public function test_lease_pending_scope(): void
    {
        Lease::factory()->create(['workflow_state' => 'draft']);
        Lease::factory()->create(['workflow_state' => 'pending_deposit']);
        Lease::factory()->create(['workflow_state' => 'active']);

        $pending = Lease::pending()->count();

        $this->assertGreaterThanOrEqual(2, $pending);
    }

    public function test_cannot_transition_to_same_state(): void
    {
        $lease = Lease::factory()->create(['workflow_state' => 'draft']);

        $this->assertFalse($lease->canTransitionTo('draft'));
    }

    public function test_valid_transitions_from_approved(): void
    {
        $lease = Lease::factory()->create(['workflow_state' => 'approved']);

        $this->assertTrue($lease->canTransitionTo('printed'));
        $this->assertTrue($lease->canTransitionTo('sent_digital'));
        $this->assertTrue($lease->canTransitionTo('cancelled'));
        $this->assertFalse($lease->canTransitionTo('draft'));
    }
}
