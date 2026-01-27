<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

class SMSSendingException extends Exception
{
    protected ?string $phoneNumber;

    public function __construct(string $reason = 'Unknown error', ?string $phoneNumber = null)
    {
        $this->phoneNumber = $phoneNumber;
        parent::__construct("Failed to send SMS: {$reason}");
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render()
    {
        return response()->json([
            'message' => 'Failed to send SMS notification. Please try again.',
            'error' => 'sms_sending_failed',
            'error_code' => 'SMS_SEND_FAILED',
        ], Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
