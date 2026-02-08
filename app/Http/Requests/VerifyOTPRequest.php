<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOTPRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization is handled via signed URLs in the controller
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $codeLength = (int) config('lease.otp.code_length', 6);

        return [
            'code' => ['required', 'string', "size:{$codeLength}"],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Please enter the OTP code.',
            'code.size' => 'The OTP code must be exactly :size digits.',
        ];
    }
}
