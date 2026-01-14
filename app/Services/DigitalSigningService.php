<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\DigitalSignature;
use App\Models\Tenant;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DigitalSigningService
{
    /**
     * Generate a secure signing link for a tenant.
     *
     * @param Lease $lease
     * @param int $expiresInHours Number of hours until link expires
     * @return string Secure signing URL
     */
    public static function generateSigningLink(Lease $lease, int $expiresInHours = 72): string
    {
        // Generate a temporary signed URL that expires
        $url = URL::temporarySignedRoute(
            'tenant.sign-lease',
            now()->addHours($expiresInHours),
            [
                'lease' => $lease->id,
                'tenant' => $lease->tenant_id,
            ]
        );

        Log::info('Signing link generated', [
            'lease_id' => $lease->id,
            'tenant_id' => $lease->tenant_id,
            'expires_at' => now()->addHours($expiresInHours),
        ]);

        return $url;
    }

    /**
     * Send signing link to tenant via email or SMS.
     *
     * @param Lease $lease
     * @param string $method 'email', 'sms', or 'both'
     * @return bool Success status
     */
    public static function sendSigningLink(Lease $lease, string $method = 'both'): bool
    {
        $link = self::generateSigningLink($lease);
        $tenant = $lease->tenant;

        try {
            if (in_array($method, ['email', 'both']) && $tenant->email) {
                self::sendEmail($lease, $tenant, $link);
            }

            if (in_array($method, ['sms', 'both']) && $tenant->phone) {
                self::sendSMS($lease, $tenant, $link);
            }

            Log::info('Signing link sent', [
                'lease_id' => $lease->id,
                'method' => $method,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send signing link', [
                'lease_id' => $lease->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send signing link via email.
     *
     * @param Lease $lease
     * @param Tenant $tenant
     * @param string $link
     * @return void
     */
    private static function sendEmail(Lease $lease, Tenant $tenant, string $link): void
    {
        // For now, just log - implement actual email in production
        Log::info('Email would be sent', [
            'to' => $tenant->email,
            'lease' => $lease->reference_number,
            'link' => $link,
        ]);

        // TODO: Implement actual email sending
        // Mail::to($tenant->email)->send(new LeaseSigningLinkMail($lease, $link));
    }

    /**
     * Send signing link via SMS.
     *
     * @param Lease $lease
     * @param Tenant $tenant
     * @param string $link
     * @return void
     */
    private static function sendSMS(Lease $lease, Tenant $tenant, string $link): void
    {
        $shortLink = self::shortenUrl($link);
        $message = "Please sign your lease ({$lease->reference_number}). Click: {$shortLink}. Link expires in 72 hours. - Chabrin Agencies";

        // Use Africa's Talking if configured
        $apiKey = config('services.africas_talking.api_key');

        if (!$apiKey) {
            Log::warning('Africa\'s Talking not configured - SMS would contain: ' . $message);
            return;
        }

        // Send via SMS service (simplified - use OTPService pattern)
        Log::info('SMS would be sent', [
            'to' => $tenant->phone,
            'message' => $message,
        ]);

        // TODO: Implement via Africa's Talking
    }

    /**
     * Capture and store digital signature.
     *
     * @param Lease $lease
     * @param array $signatureData
     * @return DigitalSignature
     */
    public static function captureSignature(Lease $lease, array $signatureData): DigitalSignature
    {
        // Prepare signature data
        $data = [
            'lease_id' => $lease->id,
            'tenant_id' => $lease->tenant_id,
            'signature_data' => $signatureData['signature_data'],
            'signature_type' => $signatureData['signature_type'] ?? 'canvas',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'signature_latitude' => $signatureData['latitude'] ?? null,
            'signature_longitude' => $signatureData['longitude'] ?? null,
            'otp_verification_id' => $signatureData['otp_verification_id'] ?? null,
            'metadata' => $signatureData['metadata'] ?? null,
        ];

        // Create signature with automatic hash generation
        $signature = DigitalSignature::createFromData($data);

        // Update lease workflow state
        $lease->transitionTo('tenant_signed');

        Log::info('Digital signature captured', [
            'lease_id' => $lease->id,
            'signature_id' => $signature->id,
            'tenant_id' => $lease->tenant_id,
        ]);

        return $signature;
    }

    /**
     * Check if tenant can sign (has verified OTP).
     *
     * @param Lease $lease
     * @return bool
     */
    public static function canSign(Lease $lease): bool
    {
        // Check if lease has verified OTP
        return OTPService::hasVerifiedOTP($lease);
    }

    /**
     * Get signing status for a lease.
     *
     * @param Lease $lease
     * @return array Status information
     */
    public static function getSigningStatus(Lease $lease): array
    {
        $hasSignature = DigitalSignature::forLease($lease->id)->exists();
        $hasVerifiedOTP = OTPService::hasVerifiedOTP($lease);
        $latestOTP = OTPService::getLatestOTP($lease);

        return [
            'has_signature' => $hasSignature,
            'has_verified_otp' => $hasVerifiedOTP,
            'can_sign' => $hasVerifiedOTP && !$hasSignature,
            'workflow_state' => $lease->workflow_state,
            'otp_status' => $latestOTP ? [
                'is_valid' => $latestOTP->isValid(),
                'is_expired' => $latestOTP->hasExpired(),
                'attempts' => $latestOTP->attempts,
                'minutes_until_expiry' => $latestOTP->getMinutesUntilExpiry(),
            ] : null,
        ];
    }

    /**
     * Initiate digital signing process for a lease.
     *
     * @param Lease $lease
     * @param string $method 'email', 'sms', or 'both'
     * @return array Result with link and status
     */
    public static function initiate(Lease $lease, string $method = 'both'): array
    {
        // Generate signing link
        $link = self::generateSigningLink($lease);

        // Send link to tenant
        $sent = self::sendSigningLink($lease, $method);

        // Update lease state
        $lease->transitionTo('sent_digital');

        return [
            'success' => $sent,
            'link' => $link,
            'expires_at' => now()->addHours(72),
            'sent_via' => $method,
        ];
    }

    /**
     * Simple URL shortener (for SMS).
     *
     * @param string $url
     * @return string
     */
    private static function shortenUrl(string $url): string
    {
        // For now, just return original URL
        // In production, integrate with bit.ly or similar
        return $url;
    }

    /**
     * Resend signing link to tenant.
     *
     * @param Lease $lease
     * @param string $method
     * @return array
     */
    public static function resendLink(Lease $lease, string $method = 'both'): array
    {
        return self::initiate($lease, $method);
    }
}
