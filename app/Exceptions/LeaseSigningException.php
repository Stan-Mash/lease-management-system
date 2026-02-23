<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * Domain exception for lease digital signing failures.
 *
 * Use static factory methods instead of generic Exception('message') to ensure
 * consistent error codes and messages across the signing workflow.
 * Each case has a machine-readable $code for API clients.
 */
class LeaseSigningException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $errorCode = 'signing_error',
        int $httpStatus = 422,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $httpStatus, $previous);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getHttpStatus(): int
    {
        return $this->getCode();
    }

    public function toJsonResponse(): JsonResponse
    {
        return response()->json([
            'success'    => false,
            'error'      => $this->errorCode,
            'message'    => $this->getMessage(),
        ], $this->getCode());
    }

    // ── Factory methods ─────────────────────────────────────────────────────

    public static function alreadySigned(int $leaseId): self
    {
        return new self(
            "Lease #{$leaseId} has already been signed.",
            'already_signed',
            409,
        );
    }

    public static function otpNotVerified(): self
    {
        return new self(
            'Please verify your OTP before signing the lease.',
            'otp_not_verified',
            422,
        );
    }

    public static function invalidState(string $state): self
    {
        return new self(
            "Cannot sign a lease in state [{$state}]. The lease must be in a signable state.",
            'invalid_state',
            422,
        );
    }

    public static function signatureDataMissing(): self
    {
        return new self(
            'Signature data is required.',
            'signature_missing',
            422,
        );
    }

    public static function concurrentSubmission(): self
    {
        return new self(
            'A concurrent signing attempt was detected. Please refresh and try again.',
            'concurrent_submission',
            409,
        );
    }
}
