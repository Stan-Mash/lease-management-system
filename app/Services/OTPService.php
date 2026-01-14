<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\OTPVerification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OTPService
{
    /**
     * Generate and send OTP for digital signing.
     *
     * @param Lease $lease The lease requiring signature
     * @param string $phone Phone number to send OTP to
     * @param string $purpose Purpose of OTP (default: digital_signing)
     * @return OTPVerification
     * @throws \Exception If OTP generation or sending fails
     */
    public static function generateAndSend(
        Lease $lease,
        string $phone,
        string $purpose = 'digital_signing'
    ): OTPVerification {
        // Check rate limiting (max 3 OTPs per hour per lease)
        $recentOTPs = OTPVerification::forLease($lease->id)
            ->recent(1)
            ->count();

        if ($recentOTPs >= 3) {
            throw new \Exception('Too many OTP requests. Please try again later.');
        }

        // Generate 4-digit OTP code
        $code = self::generateCode();

        // Set expiry time (10 minutes from now)
        $expiresAt = now()->addMinutes(10);

        // Create OTP record
        $otp = OTPVerification::create([
            'lease_id' => $lease->id,
            'phone' => $phone,
            'code' => $code,
            'purpose' => $purpose,
            'sent_at' => now(),
            'expires_at' => $expiresAt,
        ]);

        // Send OTP via SMS
        try {
            self::sendSMS($phone, $code, $lease);

            Log::info('OTP sent successfully', [
                'lease_id' => $lease->id,
                'phone' => $phone,
                'otp_id' => $otp->id,
            ]);
        } catch (\Exception $e) {
            // Mark OTP as expired if sending failed
            $otp->markAsExpired();

            Log::error('Failed to send OTP', [
                'lease_id' => $lease->id,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to send OTP. Please try again.');
        }

        return $otp;
    }

    /**
     * Verify OTP code for a lease.
     *
     * @param Lease $lease
     * @param string $code
     * @param string|null $ipAddress
     * @return bool
     */
    public static function verify(Lease $lease, string $code, ?string $ipAddress = null): bool
    {
        // Find the most recent valid OTP for this lease
        $otp = OTPVerification::forLease($lease->id)
            ->valid()
            ->orderBy('sent_at', 'desc')
            ->first();

        if (!$otp) {
            Log::warning('No valid OTP found for lease', [
                'lease_id' => $lease->id,
                'code_attempt' => $code,
            ]);
            return false;
        }

        // Verify the code
        $verified = $otp->verify($code, $ipAddress);

        if ($verified) {
            Log::info('OTP verified successfully', [
                'lease_id' => $lease->id,
                'otp_id' => $otp->id,
            ]);
        } else {
            Log::warning('OTP verification failed', [
                'lease_id' => $lease->id,
                'otp_id' => $otp->id,
                'attempts' => $otp->attempts,
            ]);
        }

        return $verified;
    }

    /**
     * Generate a 4-digit OTP code.
     *
     * @return string
     */
    private static function generateCode(): string
    {
        return str_pad((string) rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Send OTP via SMS using Africa's Talking.
     *
     * @param string $phone
     * @param string $code
     * @param Lease $lease
     * @return void
     * @throws \Exception
     */
    private static function sendSMS(string $phone, string $code, Lease $lease): void
    {
        $apiKey = config('services.africas_talking.api_key');
        $username = config('services.africas_talking.username');

        // If no API key configured, log and return (for testing)
        if (!$apiKey || !$username) {
            Log::warning('Africa\'s Talking not configured - OTP would be: ' . $code);
            return;
        }

        // Format phone number (ensure it starts with country code)
        $formattedPhone = self::formatPhoneNumber($phone);

        // Compose SMS message
        $message = "Your Chabrin Lease verification code is: {$code}. Valid for 10 minutes. Ref: {$lease->reference_number}";

        // Send via Africa's Talking API
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

        if (!$response->successful()) {
            throw new \Exception('SMS sending failed: ' . $response->body());
        }

        $data = $response->json();

        // Check if SMS was accepted
        if (isset($data['SMSMessageData']['Recipients'][0]['status']) &&
            $data['SMSMessageData']['Recipients'][0]['status'] !== 'Success') {
            throw new \Exception('SMS not accepted: ' . ($data['SMSMessageData']['Recipients'][0]['status'] ?? 'Unknown error'));
        }
    }

    /**
     * Format phone number to international format.
     *
     * @param string $phone
     * @return string
     */
    private static function formatPhoneNumber(string $phone): string
    {
        // Remove spaces, dashes, and brackets
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);

        // If starts with 0, replace with +254 (Kenya)
        if (substr($phone, 0, 1) === '0') {
            $phone = '+254' . substr($phone, 1);
        }

        // If doesn't start with +, add +254 (Kenya)
        if (substr($phone, 0, 1) !== '+') {
            $phone = '+254' . $phone;
        }

        return $phone;
    }

    /**
     * Check if a lease has a verified OTP.
     *
     * @param Lease $lease
     * @return bool
     */
    public static function hasVerifiedOTP(Lease $lease): bool
    {
        return OTPVerification::forLease($lease->id)
            ->verified()
            ->exists();
    }

    /**
     * Get the latest OTP for a lease.
     *
     * @param Lease $lease
     * @return OTPVerification|null
     */
    public static function getLatestOTP(Lease $lease): ?OTPVerification
    {
        return OTPVerification::forLease($lease->id)
            ->orderBy('sent_at', 'desc')
            ->first();
    }

    /**
     * Resend OTP (generates a new one).
     *
     * @param Lease $lease
     * @param string $phone
     * @return OTPVerification
     * @throws \Exception
     */
    public static function resend(Lease $lease, string $phone): OTPVerification
    {
        // Mark all existing OTPs for this lease as expired
        OTPVerification::forLease($lease->id)
            ->valid()
            ->update(['is_expired' => true]);

        // Generate and send new OTP
        return self::generateAndSend($lease, $phone);
    }

    /**
     * Clean up expired OTPs (can be run via scheduled task).
     *
     * @param int $daysOld Number of days old to delete
     * @return int Number of OTPs deleted
     */
    public static function cleanup(int $daysOld = 30): int
    {
        return OTPVerification::where('created_at', '<', now()->subDays($daysOld))
            ->delete();
    }
}
