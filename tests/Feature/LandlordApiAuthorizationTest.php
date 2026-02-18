<?php

namespace Tests\Feature;

use App\Models\Landlord;
use App\Models\Lease;
use App\Models\Zone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LandlordApiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_landlord_access_cannot_view_landlord_lease_via_api(): void
    {
        $zoneA = Zone::factory()->create();
        $zoneB = Zone::factory()->create();

        $landlord = Landlord::factory()->create(['zone_id' => $zoneB->id]);

        $lease = Lease::factory()->create([
            'landlord_id' => $landlord->id,
            'zone_id' => $zoneB->id,
            'workflow_state' => 'pending_landlord_approval',
        ]);

        // User in zone A cannot access landlord in zone B
        $user = User::factory()->create([
            'role' => 'zone_manager',
            'zone_id' => $zoneA->id,
            'block' => 0,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(route('api.landlord.approvals.show', [
            'landlordId' => $landlord->id,
            'leaseId' => $lease->id,
        ]));

        $response->assertForbidden();
    }

    public function test_user_without_landlord_access_cannot_approve_lease_via_api(): void
    {
        $zoneA = Zone::factory()->create();
        $zoneB = Zone::factory()->create();

        $landlord = Landlord::factory()->create(['zone_id' => $zoneB->id]);

        $lease = Lease::factory()->create([
            'landlord_id' => $landlord->id,
            'zone_id' => $zoneB->id,
            'workflow_state' => 'pending_landlord_approval',
        ]);

        $user = User::factory()->create([
            'role' => 'zone_manager',
            'zone_id' => $zoneA->id,
            'block' => 0,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.landlord.approvals.approve', [
            'landlordId' => $landlord->id,
            'leaseId' => $lease->id,
        ]), ['comments' => 'Approved']);

        $response->assertForbidden();
    }

    public function test_user_without_landlord_access_cannot_reject_lease_via_api(): void
    {
        $zoneA = Zone::factory()->create();
        $zoneB = Zone::factory()->create();

        $landlord = Landlord::factory()->create(['zone_id' => $zoneB->id]);

        $lease = Lease::factory()->create([
            'landlord_id' => $landlord->id,
            'zone_id' => $zoneB->id,
            'workflow_state' => 'pending_landlord_approval',
        ]);

        $user = User::factory()->create([
            'role' => 'zone_manager',
            'zone_id' => $zoneA->id,
            'block' => 0,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.landlord.approvals.reject', [
            'landlordId' => $landlord->id,
            'leaseId' => $lease->id,
        ]), [
            'rejection_reason' => 'Test rejection',
            'comments' => 'Optional',
        ]);

        $response->assertForbidden();
    }

    public function test_zone_manager_can_access_landlord_in_same_zone_via_api(): void
    {
        $zone = Zone::factory()->create();

        $landlord = Landlord::factory()->create(['zone_id' => $zone->id]);

        $lease = Lease::factory()->create([
            'landlord_id' => $landlord->id,
            'zone_id' => $zone->id,
            'workflow_state' => 'pending_landlord_approval',
        ]);

        $user = User::factory()->create([
            'role' => 'zone_manager',
            'zone_id' => $zone->id,
            'block' => 0,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(route('api.landlord.approvals.show', [
            'landlordId' => $landlord->id,
            'leaseId' => $lease->id,
        ]));

        $response->assertOk();
        $response->assertJsonPath('success', true);
    }
}
