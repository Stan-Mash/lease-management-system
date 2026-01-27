<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

class SerialNumberGenerationException extends Exception
{
    public function __construct(string $reason = 'Failed to generate serial number')
    {
        parent::__construct($reason);
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render()
    {
        return response()->json([
            'message' => $this->message,
            'error' => 'serial_number_generation_failed',
            'error_code' => 'SERIAL_GENERATION_FAILED',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
