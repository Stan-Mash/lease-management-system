<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

class LeaseVerificationFailedException extends Exception
{
    public function __construct(string $reason = 'Unknown')
    {
        parent::__construct("Lease verification failed: {$reason}");
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render()
    {
        return response()->json([
            'verified' => false,
            'message' => $this->message,
            'error' => 'verification_failed',
            'error_code' => 'LEASE_VERIFICATION_FAILED',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
