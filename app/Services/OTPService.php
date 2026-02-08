<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\OTPRateLimitException;
use App\Exceptions\OTPSendingException;
use App\Models\Lease;
use App\Models\OTPVerification;
use App\Support\PhoneFormatter;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class OTPService
{
    /**
     * The time window (in minutes) within which a verified OTP remains valid
     * for signing. After this window, a new OTP must be requested.
     */
    private const VERIFIED_OTP_VALIDITY_MINUTES = 30;

    /**
     * Generate and send OTP for digital signing.
     *
     * The OTP code is hashed before storage to prevent plaintext exposure
     * in case of database compromise. The plaintext code is only sent via SMS.
     *
     * @param Lease $lease The lease requiring signature
     * @param string $phone Phone number to send OTP to
     * @param string $purpose Purpose of OTP (default: digital_signing)
     * @param bool $checkFingerprint Whether to check device fingerprint for suspicious activity
     *
     * @throws OTPRateLimitException If too many OTP requests
     * @throws OTPSendingException If OTP sending fails
     */
    public static function generateAndSend(
        Lease $lease,
        string $phone,
        string $purpose = 'digital_signing',
        bool $checkFingerprint = true,
    ): OTPVerification {
        // Check rate limiting (max attempts per hour per lease)
        $maxAttempts = (int) config('lease.otp.max_attempts_per_hour', 3);
        $recentOTPs = OTPVerification::forLease($lease->id)
            ->recent(1)
            ->count();

        if ($recentOTPs >= $maxAttempts) {
            throw new OTPRateLimitException($maxAttempts);
        }

        // Collect device fingerprint for security analysis
        $fingerprint = null;
        $suspiciousActivity = null;

        if ($checkFingerprint) {
            $fingerprint = DeviceFingerprintService::generate();
            $suspiciousActivity = DeviceFingerprintService::isSuspicious($fingerprint, 'otp');

            // Log suspicious activity but don't block (for monitoring)
            if ($suspiciousActivity['is_suspicious']) {
                Log::warning('Suspicious OTP request detected', [
                    'lease_id' => $lease->id,
                    'phone_masked' => PhoneFormatter::mask($phone),
                    'risk_score' => $suspiciousActivity['risk_score'],
                    'reasons' => $suspiciousActivity['reasons'],
                    'ip' => $fingerprint['ip_address'] ?? 'unknown',
                ]);
            }
        }

        // Generate cryptographically secure OTP code
        $plaintextCode = self::generateCode();

        // Set expiry time from config
        $expiryMinutes = (int) config('lease.otp.expiry_minutes', 10);
        $expiresAt = now()->addMinutes($expiryMinutes);

        // Create OTP record with HASHED code for security
        $otp = OTPVerification::create([
            'lease_id' => $lease->id,
            'phone' => $phone,
            'code' => Hash::make($plaintextCode),
            'purpose' => $purpose,
            'sent_at' => now(),
            'expires_at' => $expiresAt,
            'device_fingerprint' => $fingerprint ? json_encode([
                'hash' => $fingerprint['hash'] ?? null,
                'device_info' => $fingerprint['device_info'] ?? null,
                'ip_address' => $fingerprint['ip_address'] ?? null,
                'user_agent' => $fingerprint['user_agent'] ?? null,
            ]) : null,
            'risk_score' => $suspiciousActivity['risk_score'] ?? null,
        ]);

        // Store fingerprint for later comparison during verification
        if ($fingerprint) {
            DeviceFingerprintService::store('otp', $otp->id, $fingerprint);
        }

        // Send OTP via SMS (plaintext code sent to tenant, NOT stored)
        try {
            $sent = SMSService::sendOTP(
                $phone,
                $plaintextCode,
                $lease->reference_number,
                $expiryMinutes,
            );

            if (! $sent && SMSService::isConfigured()) {
                throw new Exception('SMS service returned failure');
            }

            Log::info('OTP generated and sent', [
                'lease_id' => $lease->id,
                'phone_masked' => PhoneFormatter::mask($phone),
                'otp_id' => $otp->id,
                'risk_score' => $suspiciousActivity['risk_score'] ?? 0,
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
     *
     * Uses Hash::check() to compare the provided plaintext code against
     * the stored hash, preventing timing attacks and plaintext exposure.
     *
     * @param Lease $lease The lease to verify
     * @param string $code The plaintext OTP code to verify
     * @param string|null $ipAddress IP address of the verifier
     * @param bool $checkFingerprint Whether to compare device fingerprints
     */
    public static function verify(
        Lease $lease,
        string $code,
        ?string $ipAddress = null,
        bool $checkFingerprint = true,
    ): bool {
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

        // Check device fingerprint similarity
        $fingerprintMatch = true;
        $similarityScore = 100;

        if ($checkFingerprint) {
            $currentFingerprint = DeviceFingerprintService::generate();
            $originalFingerprint = DeviceFingerprintService::retrieve('otp', $otp->id);

            if ($originalFingerprint) {
                $similarityScore = DeviceFingerprintService::compare($originalFingerprint, $currentFingerprint);
                $fingerprintMatch = $similarityScore >= 50;

                if (! $fingerprintMatch) {
                    Log::warning('OTP verification from different device', [
                        'lease_id' => $lease->id,
                        'otp_id' => $otp->id,
                        'similarity_score' => $similarityScore,
                        'original_ip' => $originalFingerprint['ip_address'] ?? 'unknown',
                        'current_ip' => $currentFingerprint['ip_address'] ?? 'unknown',
                    ]);
                }
            }
        }

        // Verify the code using hash comparison
        $verified = $otp->verifyHashed($code, $ipAddress);

        if ($verified) {
            Log::info('OTP verified successfully', [
                'lease_id' => $lease->id,
                'otp_id' => $otp->id,
                'fingerprint_match' => $fingerprintMatch,
                'similarity_score' => $similarityScore,
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
     * Check if a lease has a recently verified OTP.
     *
     * Enforces a time window to prevent replay attacks where a previously
     * verified OTP could be used to sign much later. The OTP must have been
     * verified within the configured validity window (default: 30 minutes).
     */
    public static function hasVerifiedOTP(Lease $lease): bool
    {
        $validityMinutes = self::VERIFIED_OTP_VALIDITY_MINUTES;

        return OTPVerification::forLease($lease->id)
            ->verified()
            ->where('verified_at', '>=', now()->subMinutes($validityMinutes))
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
     * Generate a cryptographically secure OTP code.
     */
    private static function generateCode(): string
    {
        $length = (int) config('lease.otp.code_length', 6);
        $max = (int) str_repeat('9', $length);

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }
}
