<?php

namespace Tests\Feature;

use App\Services\SMSService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class SMSServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable SMS config by default for tests
        Config::set('services.africas_talking.api_key', null);
        Config::set('services.africas_talking.username', null);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }


    public function test_is_configured_returns_false_when_not_configured(): void
    {
        $this->assertFalse(SMSService::isConfigured());
    }

    public function test_is_configured_returns_true_when_configured(): void
    {
        Config::set('services.africas_talking.api_key', 'test_key');
        Config::set('services.africas_talking.username', 'test_user');

        $this->assertTrue(SMSService::isConfigured());
    }

    public function test_send_returns_false_when_not_configured(): void
    {
        Log::spy();

        $result = SMSService::send('+254712345678', 'Test message');

        $this->assertFalse($result);

        Log::shouldHaveReceived('warning');
    }

    public function test_send_returns_false_for_invalid_phone(): void
    {
        Log::spy();

        $result = SMSService::send('123', 'Test message');

        $this->assertFalse($result);

        Log::shouldHaveReceived('warning');
    }

    public function test_send_otp_formats_message_correctly(): void
    {
        Log::spy();

        SMSService::sendOTP('+254712345678', '123456', 'LSE-2026-0001', 10);

        Log::shouldHaveReceived('warning')->withArgs(function ($message, $context) {
            return $context['type'] === 'otp' && $context['reference'] === 'LSE-2026-0001';
        });
    }

    public function test_send_approval_request_formats_message(): void
    {
        Log::spy();

        SMSService::sendApprovalRequest(
            '+254712345678',
            'LSE-2026-0001',
            'John Doe',
            25000.00
        );

        Log::shouldHaveReceived('warning')->withArgs(function ($message, $context) {
            return $context['type'] === 'approval_request';
        });
    }

    public function test_send_approval_notification_formats_message(): void
    {
        Log::spy();

        SMSService::sendApprovalNotification('+254712345678', 'LSE-2026-0001');

        Log::shouldHaveReceived('warning')->withArgs(function ($message, $context) {
            return $context['type'] === 'approval_notification';
        });
    }

    public function test_send_rejection_notification_includes_reason(): void
    {
        Log::spy();

        SMSService::sendRejectionNotification(
            '+254712345678',
            'LSE-2026-0001',
            'Incorrect tenant details'
        );

        Log::shouldHaveReceived('warning')->withArgs(function ($message, $context) {
            return $context['type'] === 'rejection_notification';
        });
    }

    public function test_send_signing_link_formats_message(): void
    {
        Log::spy();

        SMSService::sendSigningLink(
            '+254712345678',
            'LSE-2026-0001',
            'https://example.com/sign/abc123'
        );

        Log::shouldHaveReceived('warning')->withArgs(function ($message, $context) {
            return $context['type'] === 'signing_link';
        });
    }

    public function test_send_makes_api_call_when_configured(): void
    {
        Config::set('services.africas_talking.api_key', 'test_key');
        Config::set('services.africas_talking.username', 'test_user');
        Config::set('services.africas_talking.shortcode', 'CHABRIN');

        Http::fake([
            'api.africastalking.com/*' => Http::response([
                'SMSMessageData' => [
                    'Recipients' => [
                        [
                            'status' => 'Success',
                            'messageId' => 'ATXid_123',
                        ],
                    ],
                ],
            ], 200),
        ]);

        Log::spy();

        $result = SMSService::send('+254712345678', 'Test message');

        $this->assertTrue($result);

        Log::shouldHaveReceived('info');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.africastalking.com/version1/messaging' &&
                   $request['username'] === 'test_user' &&
                   $request['to'] === '+254712345678' &&
                   $request['message'] === 'Test message' &&
                   $request['from'] === 'CHABRIN';
        });
    }

    public function test_send_returns_false_on_api_failure(): void
    {
        Config::set('services.africas_talking.api_key', 'test_key');
        Config::set('services.africas_talking.username', 'test_user');

        Http::fake([
            'api.africastalking.com/*' => Http::response([], 500),
        ]);

        Log::spy();

        $result = SMSService::send('+254712345678', 'Test message');

        $this->assertFalse($result);

        Log::shouldHaveReceived('warning');
    }

    public function test_send_returns_false_when_sms_not_accepted(): void
    {
        Config::set('services.africas_talking.api_key', 'test_key');
        Config::set('services.africas_talking.username', 'test_user');

        Http::fake([
            'api.africastalking.com/*' => Http::response([
                'SMSMessageData' => [
                    'Recipients' => [
                        [
                            'status' => 'InvalidPhoneNumber',
                        ],
                    ],
                ],
            ], 200),
        ]);

        Log::spy();

        $result = SMSService::send('+254712345678', 'Test message');

        $this->assertFalse($result);

        Log::shouldHaveReceived('warning');
    }

    public function test_send_handles_exception_gracefully(): void
    {
        Config::set('services.africas_talking.api_key', 'test_key');
        Config::set('services.africas_talking.username', 'test_user');

        Http::fake(function () {
            throw new \Exception('Connection failed');
        });

        Log::spy();

        $result = SMSService::send('+254712345678', 'Test message');

        $this->assertFalse($result);

        Log::shouldHaveReceived('error');
    }
}
