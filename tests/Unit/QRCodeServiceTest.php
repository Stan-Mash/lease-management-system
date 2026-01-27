<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\QRCodeService;
use App\Models\Lease;

class QRCodeServiceTest extends TestCase
{
    public function test_generate_for_lease_returns_valid_structure(): void
    {
        $lease = Lease::factory()->create();
        
        $result = QRCodeService::generateForLease($lease, false);
        
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('svg', $result);
    }

    public function test_generate_verification_hash_creates_consistent_hash(): void
    {
        $lease = Lease::factory()->create();
        
        $hash1 = QRCodeService::generateVerificationHash($lease);
        $hash2 = QRCodeService::generateVerificationHash($lease);
        
        $this->assertEquals($hash1, $hash2);
    }

    public function test_verify_hash_validates_correct_hash(): void
    {
        $lease = Lease::factory()->create();
        $hash = QRCodeService::generateVerificationHash($lease);
        
        $this->assertTrue(QRCodeService::verifyHash($lease, $hash));
    }

    public function test_verify_hash_rejects_incorrect_hash(): void
    {
        $lease = Lease::factory()->create();
        
        $this->assertFalse(QRCodeService::verifyHash($lease, 'invalid_hash'));
    }

    public function test_attach_to_lease_updates_lease(): void
    {
        $lease = Lease::factory()->create();
        
        $updated = QRCodeService::attachToLease($lease);
        
        $this->assertNotNull($updated->qr_code_data);
        $this->assertNotNull($updated->verification_url);
        $this->assertNotNull($updated->qr_generated_at);
    }

    public function test_get_base64_data_uri_returns_valid_format(): void
    {
        $lease = Lease::factory()->create();
        
        $dataUri = QRCodeService::getBase64DataUri($lease);
        
        $this->assertStringStartsWith('data:image/png;base64,', $dataUri);
    }
}
