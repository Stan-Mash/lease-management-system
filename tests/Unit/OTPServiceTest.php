<?php

namespace Tests\Unit;

use App\Enums\LeaseWorkflowState;
use App\Exceptions\OTPRateLimitException;
use App\Exceptions\OTPSendingException;
use App\Models\Lease;
use App\Models\OTPVerification;
use App\Models\Tenant;
use App\Services\OTPService;
use App\Services\SMSService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class OTPServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Lease $lease;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create([
            'phone_number' => '+254712345678',
        ]);

        $this->lease = Lease::factory()->create([
            'tenant_id' => $this->tenant->id,
            'workflow_state' => LeaseWorkflowState::APPROVED->value,
        ]);

        // Mock the SMS service
        $smsServiceMock = \Mockery::mock('overload:App\Services\SMSService');
        $smsServiceMock->shouldReceive('sendOTP')->andReturn(true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_generate_code_is_6_digits(): void
    {
        // Use reflection to test private method
        $reflection = new \ReflectionClass(OTPService::class);
        $method = $reflection->getMethod('generateCode');
        $method->setAccessible(true);

        $code = $method->invoke(null);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    public function test_generate_code_is_cryptographically_random(): void
    {
        $reflection = new \ReflectionClass(OTPService::class);
        $method = $reflection->getMethod('generateCode');
        $method->setAccessible(true);

        $codes = [];
        for ($i = 0; $i < 100; $i++) {
            $codes[] = $method->invoke(null);
        }

        // Check that we have some variety (not all same)
        $uniqueCodes = array_unique($codes);
        $this->assertGreaterThan(90, count($uniqueCodes), 'OTP codes should have high entropy');
    }

    public function test_generate_and_send_creates_otp_record(): void
    {
        // SMS service not configured, so it will just create the record
        $otp = OTPService::generateAndSend($this->lease, '+254712345678');

        $this->assertInstanceOf(OTPVerification::class, $otp);
        $this->assertEquals($this->lease->id, $otp->lease_id);
        $this->assertEquals('+254712345678', $otp->phone);
        $this->assertNotNull($otp->code);
        $this->assertEquals(6, strlen($otp->code));
    }

    public function test_rate_limiting_throws_exception(): void
    {
        // Create 3 OTPs in the last hour
        for ($i = 0; $i < 3; $i++) {
            OTPVerification::create([
                'lease_id' => $this->lease->id,
                'phone' => '+254712345678',
                'code' => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
                'purpose' => 'digital_signing',
                'sent_at' => now(),
                'expires_at' => now()->addMinutes(10),
            ]);
        }

        $this->expectException(OTPRateLimitException::class);

        OTPService::generateAndSend($this->lease, '+254712345678');
    }

    public function test_verify_returns_false_when_no_otp_exists(): void
    {
        $result = OTPService::verify($this->lease, '123456');

        $this->assertFalse($result);
    }

    public function test_verify_returns_true_for_correct_code(): void
    {
        $otp = OTPVerification::create([
            'lease_id' => $this->lease->id,
            'phone' => '+254712345678',
            'code' => '123456',
            'purpose' => 'digital_signing',
            'sent_at' => now(),
            'expires_at' => now()->addMinutes(10),
        ]);

        $result = OTPService::verify($this->lease, '123456');

        $this->assertTrue($result);
        $this->assertTrue($otp->fresh()->is_verified);
    }

    public function test_verify_returns_false_for_wrong_code(): void
    {
        OTPVerification::create([
            'lease_id' => $this->lease->id,
            'phone' => '+254712345678',
            'code' => '123456',
            'purpose' => 'digital_signing',
            'sent_at' => now(),
            'expires_at' => now()->addMinutes(10),
        ]);

        $result = OTPService::verify($this->lease, '654321');

        $this->assertFalse($result);
    }

    public function test_verify_returns_false_for_expired_otp(): void
    {
        OTPVerification::create([
            'lease_id' => $this->lease->id,
            'phone' => '+254712345678',
            'code' => '123456',
            'purpose' => 'digital_signing',
            'sent_at' => now()->subMinutes(15),
            'expires_at' => now()->subMinutes(5),
        ]);

        $result = OTPService::verify($this->lease, '123456');

        $this->assertFalse($result);
    }

    public function test_has_verified_otp_returns_correct_status(): void
    {
        $this->assertFalse(OTPService::hasVerifiedOTP($this->lease));

        OTPVerification::create([
            'lease_id' => $this->lease->id,
            'phone' => '+254712345678',
            'code' => '123456',
            'purpose' => 'digital_signing',
            'sent_at' => now(),
            'expires_at' => now()->addMinutes(10),
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        $this->assertTrue(OTPService::hasVerifiedOTP($this->lease));
    }

    public function test_resend_invalidates_previous_otps(): void
    {
        $oldOtp = OTPVerification::create([
            'lease_id' => $this->lease->id,
            'phone' => '+254712345678',
            'code' => '111111',
            'purpose' => 'digital_signing',
            'sent_at' => now(),
            'expires_at' => now()->addMinutes(10),
        ]);

        $newOtp = OTPService::resend($this->lease, '+254712345678');

        $this->assertTrue($oldOtp->fresh()->is_expired);
        $this->assertFalse($newOtp->is_expired);
        $this->assertNotEquals($oldOtp->code, $newOtp->code);
    }

    public function test_get_latest_otp_returns_most_recent(): void
    {
        OTPVerification::create([
            'lease_id' => $this->lease->id,
            'phone' => '+254712345678',
            'code' => '111111',
            'purpose' => 'digital_signing',
            'sent_at' => now()->subMinutes(5),
            'expires_at' => now()->addMinutes(5),
        ]);

        $latestOtp = OTPVerification::create([
            'lease_id' => $this->lease->id,
            'phone' => '+254712345678',
            'code' => '222222',
            'purpose' => 'digital_signing',
            'sent_at' => now(),
            'expires_at' => now()->addMinutes(10),
        ]);

        $result = OTPService::getLatestOTP($this->lease);

        $this->assertEquals($latestOtp->id, $result->id);
        $this->assertEquals('222222', $result->code);
    }

    public function test_cleanup_removes_old_otps(): void
    {
        // Create old OTP
        OTPVerification::create([
            'lease_id' => $this->lease->id,
            'phone' => '+254712345678',
            'code' => '111111',
            'purpose' => 'digital_signing',
            'sent_at' => now()->subDays(60),
            'expires_at' => now()->subDays(60),
            'created_at' => now()->subDays(60),
            'updated_at' => now()->subDays(60),
        ]);

        // Create recent OTP
        OTPVerification::create([
            'lease_id' => $this->lease->id,
            'phone' => '+254712345678',
            'code' => '222222',
            'purpose' => 'digital_signing',
            'sent_at' => now(),
            'expires_at' => now()->addMinutes(10),
        ]);

        $deleted = OTPService::cleanup(30);

        $this->assertEquals(1, $deleted);
        $this->assertEquals(1, OTPVerification::count());
    }
}
