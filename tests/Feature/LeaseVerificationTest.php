<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Lease;

class LeaseVerificationTest extends TestCase
{
    public function test_verify_lease_with_valid_serial_number(): void
    {
        $lease = Lease::factory()->create(['serial_number' => 'LSE-2026-0001']);
        
        $response = $this->get(route('lease.verify', [
            'serial' => 'LSE-2026-0001',
        ]));
        
        $response->assertStatus(200);
        $response->assertViewHas('lease');
    }

    public function test_verify_lease_with_invalid_serial_number(): void
    {
        $response = $this->get(route('lease.verify', [
            'serial' => 'INVALID-2026-0001',
        ]));
        
        $response->assertStatus(200);
        $response->assertViewHas('error');
    }

    public function test_api_verify_endpoint_returns_json(): void
    {
        $lease = Lease::factory()->create(['serial_number' => 'LSE-2026-0001']);
        
        $response = $this->postJson(route('lease.verify.api'), [
            'serial' => 'LSE-2026-0001',
            'hash' => 'some_hash',
        ]);
        
        $response->assertStatus(401); // Invalid hash
        $response->assertJson(['verified' => false]);
    }

    public function test_lease_verification_requires_both_serial_and_hash(): void
    {
        $response = $this->postJson(route('lease.verify.api'), [
            'serial' => 'LSE-2026-0001',
        ]);
        
        $response->assertStatus(422); // Missing hash
    }
}
