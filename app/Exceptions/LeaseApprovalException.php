<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

class LeaseApprovalException extends Exception
{
    protected string $leaseReference;

    protected string $errorType;

    public function __construct(string $leaseReference, string $errorType, string $message)
    {
        $this->leaseReference = $leaseReference;
        $this->errorType = $errorType;
        parent::__construct($message);
    }

    /**
     * Get the lease reference number.
     */
    public function getLeaseReference(): string
    {
        return $this->leaseReference;
    }

    /**
     * Get the error type.
     */
    public function getErrorType(): string
    {
        return $this->errorType;
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render()
    {
        return response()->json([
            'message' => $this->message,
            'error' => $this->errorType,
            'error_code' => 'LEASE_APPROVAL_ERROR',
            'lease_reference' => $this->leaseReference,
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Create exception for already approved lease.
     */
    public static function alreadyApproved(string $reference): self
    {
        return new self($reference, 'already_approved', "Lease {$reference} has already been approved.");
    }

    /**
     * Create exception for already rejected lease.
     */
    public static function alreadyRejected(string $reference): self
    {
        return new self($reference, 'already_rejected', "Lease {$reference} has already been rejected.");
    }

    /**
     * Create exception for no pending approval.
     */
    public static function noPendingApproval(string $reference): self
    {
        return new self($reference, 'no_pending_approval', "Lease {$reference} does not have a pending approval request.");
    }

    /**
     * Create exception for missing landlord.
     */
    public static function noLandlord(string $reference): self
    {
        return new self($reference, 'no_landlord', "Lease {$reference} does not have an associated landlord.");
    }
}
