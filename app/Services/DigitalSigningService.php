<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeaseWorkflowState;
use App\Models\DigitalSignature;
use App\Models\Lease;
use App\Models\Tenant;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class DigitalSigningService
{
    /**
     * Generate a secure signing link for a tenant.
     *
     * @param int|null $expiresInHours Number of hours until link expires (default from config)
     *
     * @return string Secure signing URL
     */
    public static function generateSigningLink(Lease $lease, ?int $expiresInHours = null): string
    {
        $expiresInHours = $expiresInHours ?? config('lease.signing.link_expiry_hours', 72);

        // Generate a temporary signed URL that expires
        $url = URL::temporarySignedRoute(
            'tenant.sign-lease',
            now()->addHours($expiresInHours),
            [
                'lease' => $lease->id,
                'tenant' => $lease->tenant_id,
            ],
        );

        Log::info('Signing link generated', [
            'lease_id' => $lease->id,
            'tenant_id' => $lease->tenant_id,
            'expires_in_hours' => $expiresInHours,
        ]);

        return $url;
    }

    /**
     * Send signing link to tenant via email or SMS.
     *
     * @param string|null $method 'email', 'sms', or 'both' (default from config)
     *
     * @return bool Success status
     */
    public static function sendSigningLink(Lease $lease, ?string $method = null): bool
    {
        $method = $method ?? config('lease.signing.default_notification_method', 'both');
        $link = self::generateSigningLink($lease);
        $tenant = $lease->tenant;

        try {
            if (in_array($method, ['email', 'both']) && $tenant->email_address) {
                self::sendEmail($lease, $tenant, $link);
            }

            if (in_array($method, ['sms', 'both']) && $tenant->mobile_number) {
                self::sendSMS($lease, $tenant, $link);
            }

            Log::info('Signing link sent', [
                'lease_id' => $lease->id,
                'lease_reference' => $lease->reference_number,
                'method' => $method,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to send signing link', [
                'lease_id' => $lease->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Capture and store digital signature.
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
        $lease->transitionTo(LeaseWorkflowState::TENANT_SIGNED);

        Log::info('Digital signature captured', [
            'lease_id' => $lease->id,
            'signature_id' => $signature->id,
            'tenant_id' => $lease->tenant_id,
        ]);

        return $signature;
    }

    /**
     * Check if tenant can sign (has verified OTP).
     */
    public static function canSign(Lease $lease): bool
    {
        // Check if lease has verified OTP
        return OTPService::hasVerifiedOTP($lease);
    }

    /**
     * Get signing status for a lease.
     *
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
            'can_sign' => $hasVerifiedOTP && ! $hasSignature,
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
     * @param string|null $method 'email', 'sms', or 'both' (default from config)
     *
     * @return array Result with status (link not returned for security)
     */
    public static function initiate(Lease $lease, ?string $method = null): array
    {
        $method = $method ?? config('lease.signing.default_notification_method', 'both');
        $expiryHours = config('lease.signing.link_expiry_hours', 72);

        // Generate and send signing link
        $sent = self::sendSigningLink($lease, $method);

        // Update lease state
        $lease->transitionTo(LeaseWorkflowState::SENT_DIGITAL);

        return [
            'success' => $sent,
            'expires_at' => now()->addHours($expiryHours),
            'sent_via' => $method,
            'lease_reference' => $lease->reference_number,
        ];
    }

    /**
     * Resend signing link to tenant.
     */
    public static function resendLink(Lease $lease, ?string $method = null): array
    {
        return self::initiate($lease, $method);
    }

    /**
     * Send signing link via email.
     */
    private static function sendEmail(Lease $lease, Tenant $tenant, string $link): void
    {
        Log::info('Sending signing link email', [
            'tenant_id' => $tenant->id,
            'lease_reference' => $lease->reference_number,
        ]);

        $tenant->notify(new \App\Notifications\LeaseSigningLinkNotification($lease, $link));
    }

    /**
     * Send signing link via SMS.
     */
    private static function sendSMS(Lease $lease, Tenant $tenant, string $link): void
    {
        $shortLink = self::shortenUrl($link);

        // Use centralized SMS service
        SMSService::sendSigningLink(
            $tenant->mobile_number,
            $lease->reference_number,
            $shortLink,
        );
    }

    /**
     * Simple URL shortener (for SMS).
     */
    private static function shortenUrl(string $url): string
    {
        // For now, just return original URL
        // In production, integrate with bit.ly or similar
        return $url;
    }
}
