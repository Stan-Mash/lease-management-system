<?php

namespace App\Contracts;

use App\Models\Lease;
use App\Models\OTPVerification;

/**
 * Interface for OTP service.
 * Defines the contract for OTP generation and verification.
 */
interface OTPServiceInterface
{
    /**
     * Generate and send OTP for digital signing.
     *
     * @param Lease $lease The lease requiring signature
     * @param string $phone Phone number to send OTP to
     * @param string $purpose Purpose of OTP
     * @return OTPVerification
     * @throws \App\Exceptions\OTPRateLimitException
     * @throws \App\Exceptions\OTPSendingException
     */
    public function generateAndSend(Lease $lease, string $phone, string $purpose = 'digital_signing'): OTPVerification;

    /**
     * Verify OTP code for a lease.
     *
     * @param Lease $lease
     * @param string $code
     * @param string|null $ipAddress
     * @return bool
     */
    public function verify(Lease $lease, string $code, ?string $ipAddress = null): bool;

    /**
     * Check if a lease has a verified OTP.
     *
     * @param Lease $lease
     * @return bool
     */
    public function hasVerifiedOTP(Lease $lease): bool;

    /**
     * Get the latest OTP for a lease.
     *
     * @param Lease $lease
     * @return OTPVerification|null
     */
    public function getLatestOTP(Lease $lease): ?OTPVerification;

    /**
     * Resend OTP (generates a new one).
     *
     * @param Lease $lease
     * @param string $phone
     * @return OTPVerification
     */
    public function resend(Lease $lease, string $phone): OTPVerification;
}
