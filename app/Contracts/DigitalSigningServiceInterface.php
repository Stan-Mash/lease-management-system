<?php

namespace App\Contracts;

use App\Models\DigitalSignature;
use App\Models\Lease;

/**
 * Interface for digital signing service.
 * Defines the contract for lease digital signing operations.
 */
interface DigitalSigningServiceInterface
{
    /**
     * Generate a secure signing link for a tenant.
     *
     * @param Lease $lease
     * @param int|null $expiresInHours
     * @return string Secure signing URL
     */
    public function generateSigningLink(Lease $lease, ?int $expiresInHours = null): string;

    /**
     * Send signing link to tenant via email or SMS.
     *
     * @param Lease $lease
     * @param string|null $method 'email', 'sms', or 'both'
     * @return bool Success status
     */
    public function sendSigningLink(Lease $lease, ?string $method = null): bool;

    /**
     * Capture and store digital signature.
     *
     * @param Lease $lease
     * @param array $signatureData
     * @return DigitalSignature
     */
    public function captureSignature(Lease $lease, array $signatureData): DigitalSignature;

    /**
     * Check if tenant can sign (has verified OTP).
     *
     * @param Lease $lease
     * @return bool
     */
    public function canSign(Lease $lease): bool;

    /**
     * Get signing status for a lease.
     *
     * @param Lease $lease
     * @return array
     */
    public function getSigningStatus(Lease $lease): array;

    /**
     * Initiate digital signing process for a lease.
     *
     * @param Lease $lease
     * @param string|null $method
     * @return array
     */
    public function initiate(Lease $lease, ?string $method = null): array;
}
