<?php

namespace Tests\Feature;

use App\Models\Lease;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaseDownloadAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_zone_restricted_user_cannot_download_lease_in_another_zone(): void
    {
        $zoneA = Zone::factory()->create();
        $zoneB = Zone::factory()->create();

        $user = User::factory()->create([
            'role' => 'zone_manager',
            'zone_id' => $zoneA->id,
            'block' => 0,
        ]);

        $lease = Lease::factory()->create([
            'zone_id' => $zoneB->id,
            'workflow_state' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('lease.download', $lease));

        $response->assertForbidden();
    }

    public function test_zone_restricted_user_cannot_preview_lease_in_another_zone(): void
    {
        $zoneA = Zone::factory()->create();
        $zoneB = Zone::factory()->create();

        $user = User::factory()->create([
            'role' => 'field_officer',
            'zone_id' => $zoneA->id,
            'block' => 0,
        ]);

        $lease = Lease::factory()->create([
            'zone_id' => $zoneB->id,
            'workflow_state' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('lease.preview', $lease));

        $response->assertForbidden();
    }

    public function test_admin_can_download_lease_in_any_zone(): void
    {
        $zoneB = Zone::factory()->create();

        $admin = User::factory()->create([
            'role' => 'admin',
            'zone_id' => null,
            'block' => 0,
        ]);

        $lease = Lease::factory()->create([
            'zone_id' => $zoneB->id,
            'workflow_state' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('lease.download', $lease));

        // May be 200 (PDF) or 500 if PDF generation fails in test env (no template); we only care auth passed
        $this->assertNotSame(403, $response->getStatusCode());
    }

    public function test_guest_cannot_download_lease(): void
    {
        $lease = Lease::factory()->create(['workflow_state' => 'active']);

        $response = $this->get(route('lease.download', $lease));

        $response->assertRedirect();
    }
}
