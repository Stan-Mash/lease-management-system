<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

class InvalidLeaseTransitionException extends Exception
{
    public function __construct(string $from, string $to)
    {
        parent::__construct(
            "Cannot transition lease from '{$from}' to '{$to}'. This transition is not allowed.",
        );
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render()
    {
        return response()->json([
            'message' => $this->message,
            'error' => 'invalid_transition',
            'error_code' => 'LEASE_INVALID_TRANSITION',
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
