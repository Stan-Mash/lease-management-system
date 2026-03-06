<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeaseWorkflowState;
use App\Exceptions\LeaseSigningException;
use App\Helpers\LocaleHelper;
use App\Models\DigitalSignature;
use App\Models\Lease;
use App\Models\LeaseAuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\LeaseSignedConfirmationNotification;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

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

        // Advance workflow by lease-type sequence (tenant -> next party state + notify)
        SigningWorkflowService::advanceAfterSignature($lease, SigningWorkflowService::SIGNER_TENANT);

        Log::info('Digital signature captured', [
            'lease_id'     => $lease->id,
            'signature_id' => $signature->id,
            'tenant_id'    => $lease->tenant_id,
        ]);

        // NOTE: Tenant confirmation email + PDF is intentionally NOT sent here.
        // The property manager must countersign / approve before the tenant receives
        // their copy. sendSignedConfirmations() is called separately by the admin
        // workflow when the lease reaches ACTIVE state.

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
     * Only transitions state if currently in 'approved' (first send).
     * For resends (already sent_digital / pending_otp / etc.), use resendLink().
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

        // Only transition to SENT_DIGITAL from approved (first send).
        // Resends from sent_digital/pending_otp/pending_tenant_signature skip this.
        $statesThatCanTransition = [
            LeaseWorkflowState::APPROVED->value,
        ];

        if (in_array($lease->workflow_state, $statesThatCanTransition)) {
            $lease->transitionTo(LeaseWorkflowState::SENT_DIGITAL);
        }

        $expiresAt = now()->addHours($expiryHours);
        $lease->update(['signing_link_expires_at' => $expiresAt]);

        return [
            'success' => $sent,
            'expires_at' => $expiresAt,
            'sent_via' => $method,
            'lease_reference' => $lease->reference_number,
        ];
    }

    /**
     * Resend signing link to tenant without changing workflow state.
     * Safe to call from any post-approval signing state.
     */
    public static function resendLink(Lease $lease, ?string $method = null): array
    {
        $method = $method ?? config('lease.signing.default_notification_method', 'both');
        $expiryHours = config('lease.signing.link_expiry_hours', 72);

        // Just send the link — do NOT attempt a state transition
        $sent = self::sendSigningLink($lease, $method);

        $expiresAt = now()->addHours($expiryHours);
        $lease->update(['signing_link_expires_at' => $expiresAt]);

        return [
            'success' => $sent,
            'expires_at' => $expiresAt,
            'sent_via' => $method,
            'lease_reference' => $lease->reference_number,
        ];
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
     * Send signing link via SMS (tenant locale from LocaleHelper).
     */
    private static function sendSMS(Lease $lease, Tenant $tenant, string $link): void
    {
        $shortLink = self::shortenUrl($link);
        $message = LocaleHelper::forTenant($tenant, 'sms_signing_link', [
            'name' => $tenant->names ?? '',
            'url' => $shortLink,
        ]);
        SMSService::send($tenant->mobile_number, $message, ['type' => 'signing_link', 'reference' => $lease->reference_number]);
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

    /**
     * Stamp manager's saved signature on the lease and create DigitalSignature record.
     * Uses the manager's uploaded signature_image_encrypted (decrypted to temp file, then deleted).
     * Call this from the countersign action instead of canvas-drawn signature.
     *
     * @throws LeaseSigningException When manager has no signature on file
     */
    public static function stampManagerSignature(Lease $lease, User $manager): DigitalSignature
    {
        if (empty($manager->signature_image_encrypted)) {
            throw LeaseSigningException::managerSignatureRequired();
        }

        $tmpPath = null;

        try {
            $pngBytes = $manager->signature_image;
            if ($pngBytes === null) {
                throw LeaseSigningException::managerSignatureRequired();
            }

            $tmpPath = sys_get_temp_dir() . '/sig_' . Str::uuid() . '.png';
            file_put_contents($tmpPath, $pngBytes);
            @chmod($tmpPath, 0600);

            $verificationHash = hash('sha256', $lease->id . $manager->id . now()->timestamp . $pngBytes);

            $dataUri = 'data:image/png;base64,' . base64_encode($pngBytes);

            $signature = DigitalSignature::create([
                'lease_id' => $lease->id,
                'tenant_id' => null,
                'signer_type' => 'manager',
                'signed_by_user_id' => $manager->id,
                'signed_by_name' => $manager->name ?? 'Property Manager',
                'signature_data' => $dataUri,
                'signature_type' => 'uploaded',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'signed_at' => now(),
                'is_verified' => true,
                'verification_hash' => $verificationHash,
            ]);

            $lease->auditLogs()->create([
                'action' => 'manager_countersigned',
                'old_state' => $lease->workflow_state,
                'new_state' => 'active',
                'user_id' => $manager->id,
                'user_role_at_time' => $manager->role ?? 'manager',
                'ip_address' => request()->ip(),
                'additional_data' => ['verification_hash_prefix' => substr($verificationHash, 0, 16)],
                'description' => 'Manager countersigned with saved signature',
            ]);

            return $signature;
        } finally {
            if ($tmpPath !== null && file_exists($tmpPath)) {
                @unlink($tmpPath);
            }
        }
    }

    /**
     * Stamp manager's drawn signature (canvas PNG) on the lease and create DigitalSignature record.
     * Use this when the manager draws on the pad instead of using a saved signature.
     *
     * @param string $pngBase64 Raw base64-encoded PNG (no data URI prefix)
     * @param bool   $saveToProfile If true, save the PNG to the manager's signature_image_encrypted for future use
     */
    public static function stampManagerSignatureFromPng(Lease $lease, User $manager, string $pngBase64, bool $saveToProfile = false): DigitalSignature
    {
        $pngBase64 = trim($pngBase64);
        if ($pngBase64 === '') {
            throw LeaseSigningException::managerSignatureRequired();
        }

        $pngBytes = base64_decode($pngBase64, true);
        if ($pngBytes === false || $pngBytes === '') {
            throw LeaseSigningException::managerSignatureRequired();
        }

        if ($saveToProfile) {
            $manager->setSignatureImageAttribute($pngBytes);
            $manager->save();
        }

        $verificationHash = hash('sha256', (string) $lease->id . (string) $manager->id . now()->timestamp . $pngBytes);
        $dataUri = 'data:image/png;base64,' . base64_encode($pngBytes);

        $signature = DigitalSignature::create([
            'lease_id' => $lease->id,
            'tenant_id' => null,
            'signer_type' => 'manager',
            'signed_by_user_id' => $manager->id,
            'signed_by_name' => $manager->name ?? 'Property Manager',
            'signature_data' => $dataUri,
            'signature_type' => 'canvas',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'signed_at' => now(),
            'is_verified' => true,
            'verification_hash' => $verificationHash,
        ]);

        $lease->auditLogs()->create([
            'action' => 'manager_countersigned',
            'old_state' => $lease->workflow_state,
            'new_state' => 'active',
            'user_id' => $manager->id,
            'user_role_at_time' => $manager->role ?? 'manager',
            'ip_address' => request()->ip(),
            'additional_data' => ['verification_hash_prefix' => substr($verificationHash, 0, 16)],
            'description' => $saveToProfile
                ? 'Manager countersigned with drawn signature (saved to profile)'
                : 'Manager countersigned with drawn signature',
        ]);

        return $signature;
    }

    /**
     * Send the finalised lease confirmation to the tenant (email + SMS).
     * Called by the admin workflow when the property manager has countersigned
     * and the lease transitions to ACTIVE — NOT immediately after tenant signs.
     * Failures are logged but do not throw.
     */
    public static function sendSignedConfirmations(Lease $lease): void
    {
        $tenant = $lease->tenant;

        if (! $tenant) {
            return;
        }

        // SMS confirmation
        if ($tenant->mobile_number) {
            try {
                SMSService::sendLeaseSigned(
                    $tenant,
                    $lease->reference_number,
                    $lease->start_date?->format('d M Y') ?? 'N/A',
                );
            } catch (Exception $e) {
                Log::warning('Failed to send lease-signed SMS', [
                    'lease_id' => $lease->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Email confirmation
        if ($tenant->email_address) {
            try {
                $tenant->notify(new LeaseSignedConfirmationNotification($lease));
            } catch (Exception $e) {
                Log::warning('Failed to send lease-signed confirmation email', [
                    'lease_id' => $lease->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Post-signing confirmations dispatched', [
            'lease_id' => $lease->id,
            'sms' => (bool) $tenant->mobile_number,
            'email' => (bool) $tenant->email_address,
        ]);
    }
}
