<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

class OTPRateLimitException extends Exception
{
    protected int $maxAttempts;

    public function __construct(int $maxAttempts)
    {
        $this->maxAttempts = $maxAttempts;
        parent::__construct(
            "Too many OTP requests. Maximum {$maxAttempts} attempts per hour allowed. Please try again later.",
        );
    }

    /**
     * Get the maximum attempts allowed.
     */
    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render()
    {
        return response()->json([
            'message' => $this->message,
            'error' => 'rate_limit_exceeded',
            'error_code' => 'OTP_RATE_LIMIT',
            'max_attempts' => $this->maxAttempts,
        ], Response::HTTP_TOO_MANY_REQUESTS);
    }
}
