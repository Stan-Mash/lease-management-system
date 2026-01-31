<?php

namespace App\Services;

use App\Exceptions\OTPRateLimitException;
use App\Exceptions\OTPSendingException;
use App\Models\Lease;
use App\Models\OTPVerification;
use App\Support\PhoneFormatter;
use Exception;
use Illuminate\Support\Facades\Log;

class OTPService
{
    /**
     * Generate and send OTP for digital signing.
     *
     * @param Lease $lease The lease requiring signature
     * @param string $phone Phone number to send OTP to
     * @param string $purpose Purpose of OTP (default: digital_signing)
     *
     * @throws OTPRateLimitException If too many OTP requests
     * @throws OTPSendingException If OTP sending fails
     */
    public static function generateAndSend(
        Lease $lease,
        string $phone,
        string $purpose = 'digital_signing',
    ): OTPVerification {
        // Check rate limiting (max attempts per hour per lease)
        $maxAttempts = config('lease.otp.max_attempts_per_hour', 3);
        $recentOTPs = OTPVerification::forLease($lease->id)
            ->recent(1)
            ->count();

        if ($recentOTPs >= $maxAttempts) {
            throw new OTPRateLimitException($maxAttempts);
        }

        // Generate cryptographically secure OTP code
        $code = self::generateCode();

        // Set expiry time from config
        $expiryMinutes = config('lease.otp.expiry_minutes', 10);
        $expiresAt = now()->addMinutes($expiryMinutes);

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
            $sent = SMSService::sendOTP(
                $phone,
                $code,
                $lease->reference_number,
                $expiryMinutes,
            );

            if (! $sent && SMSService::isConfigured()) {
                throw new Exception('SMS service returned failure');
            }

            Log::info('OTP generated', [
                'lease_id' => $lease->id,
                'phone_masked' => PhoneFormatter::mask($phone),
                'otp_id' => $otp->id,
            ]);
        } catch (Exception $e) {
            // Mark OTP as expired if sending failed
            $otp->markAsExpired();

            Log::error('Failed to send OTP', [
                'lease_id' => $lease->id,
                'phone_masked' => PhoneFormatter::mask($phone),
                'error' => $e->getMessage(),
            ]);

            throw new OTPSendingException($e->getMessage(), 0, $e);
        }

        return $otp;
    }

    /**
     * Verify OTP code for a lease.
     */
    public static function verify(Lease $lease, string $code, ?string $ipAddress = null): bool
    {
        // Find the most recent valid OTP for this lease
        $otp = OTPVerification::forLease($lease->id)
            ->valid()
            ->orderBy('sent_at', 'desc')
            ->first();

        if (! $otp) {
            Log::warning('No valid OTP found for lease', [
                'lease_id' => $lease->id,
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
     * Check if a lease has a verified OTP.
     */
    public static function hasVerifiedOTP(Lease $lease): bool
    {
        return OTPVerification::forLease($lease->id)
            ->verified()
            ->exists();
    }

    /**
     * Get the latest OTP for a lease.
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
     * @throws OTPRateLimitException
     * @throws OTPSendingException
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
     *
     * @return int Number of OTPs deleted
     */
    public static function cleanup(int $daysOld = 30): int
    {
        return OTPVerification::where('created_at', '<', now()->subDays($daysOld))
            ->delete();
    }

    /**
     * Generate a cryptographically secure 6-digit OTP code.
     */
    private static function generateCode(): string
    {
        $length = config('lease.otp.code_length', 6);
        $max = (int) str_repeat('9', $length);

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }
}
