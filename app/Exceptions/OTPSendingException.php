<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

class OTPSendingException extends Exception
{
    public function __construct(string $reason = 'Unknown error', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(
            "Failed to send OTP: {$reason}",
            $code,
            $previous
        );
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render()
    {
        return response()->json([
            'message' => 'Failed to send verification code. Please try again.',
            'error' => 'otp_sending_failed',
            'error_code' => 'OTP_SEND_FAILED',
        ], Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
