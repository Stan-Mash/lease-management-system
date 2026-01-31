<?php

namespace App\Services;

use App\Support\PhoneFormatter;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Centralized SMS service using Africa's Talking API.
 * Consolidates all SMS sending logic in one place.
 */
class SMSService
{
    /**
     * Send an SMS message.
     *
     * @param string $phone The recipient phone number
     * @param string $message The message to send
     * @param array $context Additional context for logging (no sensitive data)
     *
     * @return bool True if sent successfully
     */
    public static function send(string $phone, string $message, array $context = []): bool
    {
        $apiKey = config('services.africas_talking.api_key');
        $username = config('services.africas_talking.username');

        // Validate phone number
        if (! PhoneFormatter::isValid($phone)) {
            Log::warning('Invalid phone number for SMS', [
                'phone_masked' => PhoneFormatter::mask($phone),
                ...$context,
            ]);

            return false;
        }

        // If not configured, log and return (development mode)
        if (! $apiKey || ! $username) {
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
                'apiKey' => $apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->asForm()->post('https://api.africastalking.com/version1/messaging', [
                'username' => $username,
                'to' => $formattedPhone,
                'message' => $message,
                'from' => config('services.africas_talking.shortcode', 'CHABRIN'),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Check if SMS was accepted
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
        } catch (Exception $e) {
            Log::error('SMS sending exception', [
                'phone_masked' => PhoneFormatter::mask($phone),
                'error' => $e->getMessage(),
                ...$context,
            ]);

            return false;
        }
    }

    /**
     * Send an OTP code via SMS.
     *
     * @param string $phone The recipient phone number
     * @param string $code The OTP code
     * @param string $reference A reference number (e.g., lease reference)
     * @param int $expiryMinutes How long the OTP is valid
     *
     * @return bool True if sent successfully
     */
    public static function sendOTP(string $phone, string $code, string $reference, int $expiryMinutes = 10): bool
    {
        $message = "Your Chabrin Lease verification code is: {$code}. Valid for {$expiryMinutes} minutes. Ref: {$reference}";

        return self::send($phone, $message, ['type' => 'otp', 'reference' => $reference]);
    }

    /**
     * Send a lease approval request notification.
     *
     * @param string $phone The landlord's phone number
     * @param string $reference The lease reference number
     * @param string $tenantName The tenant's name
     * @param float $monthlyRent The monthly rent amount
     *
     * @return bool True if sent successfully
     */
    public static function sendApprovalRequest(
        string $phone,
        string $reference,
        string $tenantName,
        float $monthlyRent,
    ): bool {
        $message = "New lease {$reference} awaits your approval. " .
            "Tenant: {$tenantName}. " .
            'Rent: Ksh ' . number_format($monthlyRent) . '/month. ' .
            'Login to approve or reject.';

        return self::send($phone, $message, ['type' => 'approval_request', 'reference' => $reference]);
    }

    /**
     * Send a lease approval notification to tenant.
     *
     * @param string $phone The tenant's phone number
     * @param string $reference The lease reference number
     *
     * @return bool True if sent successfully
     */
    public static function sendApprovalNotification(string $phone, string $reference): bool
    {
        $message = "Good news! Your lease {$reference} has been APPROVED by the landlord. " .
            'You will receive the digital signing link shortly.';

        return self::send($phone, $message, ['type' => 'approval_notification', 'reference' => $reference]);
    }

    /**
     * Send a lease rejection notification to tenant.
     *
     * @param string $phone The tenant's phone number
     * @param string $reference The lease reference number
     * @param string $reason The rejection reason
     *
     * @return bool True if sent successfully
     */
    public static function sendRejectionNotification(string $phone, string $reference, string $reason): bool
    {
        $message = "Your lease {$reference} needs revision. " .
            "Reason: {$reason}. " .
            'Contact Chabrin support for details.';

        return self::send($phone, $message, ['type' => 'rejection_notification', 'reference' => $reference]);
    }

    /**
     * Send a digital signing link via SMS.
     *
     * @param string $phone The tenant's phone number
     * @param string $reference The lease reference number
     * @param string $link The signing link (should be shortened)
     *
     * @return bool True if sent successfully
     */
    public static function sendSigningLink(string $phone, string $reference, string $link): bool
    {
        $message = "Please sign your lease ({$reference}). Click: {$link}. Link expires in 72 hours. - Chabrin Agencies";

        return self::send($phone, $message, ['type' => 'signing_link', 'reference' => $reference]);
    }

    /**
     * Check if SMS service is configured.
     *
     * @return bool True if API credentials are configured
     */
    public static function isConfigured(): bool
    {
        return ! empty(config('services.africas_talking.api_key')) &&
               ! empty(config('services.africas_talking.username'));
    }
}
