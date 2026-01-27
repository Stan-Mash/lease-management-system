<?php

namespace App\Services;

use App\Contracts\SMSProviderInterface;
use App\Support\PhoneFormatter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Africa's Talking SMS provider implementation.
 * Implements SMSProviderInterface for dependency injection.
 */
class AfricasTalkingSMSProvider implements SMSProviderInterface
{
    protected ?string $apiKey;
    protected ?string $username;
    protected string $shortcode;
    protected string $apiUrl = 'https://api.africastalking.com/version1/messaging';

    public function __construct()
    {
        $this->apiKey = config('services.africas_talking.api_key');
        $this->username = config('services.africas_talking.username');
        $this->shortcode = config('services.africas_talking.shortcode', 'CHABRIN');
    }

    /**
     * Send an SMS message.
     */
    public function send(string $phone, string $message, array $context = []): bool
    {
        // Validate phone number
        if (!PhoneFormatter::isValid($phone)) {
            Log::warning('Invalid phone number for SMS', [
                'phone_masked' => PhoneFormatter::mask($phone),
                ...$context,
            ]);
            return false;
        }

        // If not configured, log and return (development mode)
        if (!$this->isConfigured()) {
            Log::warning('Africa\'s Talking not configured - SMS not sent', [
                'phone_masked' => PhoneFormatter::mask($phone),
                'message_length' => strlen($message),
                ...$context,
            ]);
            return false;
        }

        try {
            $formattedPhone = PhoneFormatter::toInternational($phone);

            $response = Http::withHeaders([
                'apiKey' => $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->asForm()->post($this->apiUrl, [
                'username' => $this->username,
                'to' => $formattedPhone,
                'message' => $message,
                'from' => $this->shortcode,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $status = $data['SMSMessageData']['Recipients'][0]['status'] ?? null;

                if ($status === 'Success') {
                    Log::info('SMS sent successfully', [
                        'phone_masked' => PhoneFormatter::mask($phone),
                        'message_id' => $data['SMSMessageData']['Recipients'][0]['messageId'] ?? null,
                        ...$context,
                    ]);
                    return true;
                }

                Log::warning('SMS not accepted', [
                    'phone_masked' => PhoneFormatter::mask($phone),
                    'status' => $status,
                    ...$context,
                ]);
                return false;
            }

            Log::warning('SMS API request failed', [
                'phone_masked' => PhoneFormatter::mask($phone),
                'status_code' => $response->status(),
                ...$context,
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('SMS sending exception', [
                'phone_masked' => PhoneFormatter::mask($phone),
                'error' => $e->getMessage(),
                ...$context,
            ]);
            return false;
        }
    }

    /**
     * Check if the SMS provider is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->username);
    }

    /**
     * Send an OTP code via SMS.
     */
    public function sendOTP(string $phone, string $code, string $reference, int $expiryMinutes = 10): bool
    {
        $message = "Your Chabrin Lease verification code is: {$code}. Valid for {$expiryMinutes} minutes. Ref: {$reference}";
        return $this->send($phone, $message, ['type' => 'otp', 'reference' => $reference]);
    }

    /**
     * Send a lease approval request notification.
     */
    public function sendApprovalRequest(string $phone, string $reference, string $tenantName, float $monthlyRent): bool
    {
        $message = "New lease {$reference} awaits your approval. " .
            "Tenant: {$tenantName}. " .
            "Rent: " . number_format($monthlyRent) . " KES/month. " .
            "Login to approve or reject.";
        return $this->send($phone, $message, ['type' => 'approval_request', 'reference' => $reference]);
    }

    /**
     * Send a lease approval notification to tenant.
     */
    public function sendApprovalNotification(string $phone, string $reference): bool
    {
        $message = "Good news! Your lease {$reference} has been APPROVED by the landlord. " .
            "You will receive the digital signing link shortly.";
        return $this->send($phone, $message, ['type' => 'approval_notification', 'reference' => $reference]);
    }

    /**
     * Send a lease rejection notification to tenant.
     */
    public function sendRejectionNotification(string $phone, string $reference, string $reason): bool
    {
        $message = "Your lease {$reference} needs revision. " .
            "Reason: {$reason}. " .
            "Contact Chabrin support for details.";
        return $this->send($phone, $message, ['type' => 'rejection_notification', 'reference' => $reference]);
    }

    /**
     * Send a digital signing link via SMS.
     */
    public function sendSigningLink(string $phone, string $reference, string $link): bool
    {
        $message = "Please sign your lease ({$reference}). Click: {$link}. Link expires in 72 hours. - Chabrin Agencies";
        return $this->send($phone, $message, ['type' => 'signing_link', 'reference' => $reference]);
    }
}
