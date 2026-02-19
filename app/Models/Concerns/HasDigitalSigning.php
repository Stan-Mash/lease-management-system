<?php

namespace App\Models\Concerns;

use App\Models\DigitalSignature;
use App\Services\DigitalSigningService;

/**
 * Trait for models that have digital signing capabilities.
 */
trait HasDigitalSigning
{
    /**
     * Send digital signing link to tenant (first send — transitions state to sent_digital).
     *
     * @param string $method 'email', 'sms', or 'both'
     */
    public function sendDigitalSigningLink(string $method = 'both'): array
    {
        return DigitalSigningService::initiate($this, $method);
    }

    /**
     * Resend digital signing link to tenant (no state transition — safe to call from any signing state).
     *
     * @param string $method 'email', 'sms', or 'both'
     */
    public function resendDigitalSigningLink(string $method = 'sms'): array
    {
        return DigitalSigningService::resendLink($this, $method);
    }

    /**
     * Check if the lease has a digital signature.
     */
    public function hasDigitalSignature(): bool
    {
        return $this->digitalSignatures()->exists();
    }

    /**
     * Get the latest digital signature.
     */
    public function getLatestDigitalSignature(): ?DigitalSignature
    {
        return $this->digitalSignatures()->orderBy('signed_at', 'desc')->first();
    }

    /**
     * Check if the lease can be signed (has verified OTP).
     */
    public function canBeSigned(): bool
    {
        return DigitalSigningService::canSign($this);
    }

    /**
     * Get the digital signing status.
     */
    public function getSigningStatus(): array
    {
        return DigitalSigningService::getSigningStatus($this);
    }

    /**
     * Check if the current state requires tenant action for signing.
     */
    public function requiresTenantSignature(): bool
    {
        return $this->getWorkflowStateEnum()->requiresTenantAction();
    }
}
