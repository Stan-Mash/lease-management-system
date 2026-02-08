<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\DisputeReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RejectLeaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization is handled via signed URLs in the controller
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', Rule::enum(DisputeReason::class)],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Please select a reason for your dispute.',
            'reason.enum' => 'Please select a valid dispute reason.',
            'comment.max' => 'Your comment must not exceed 1000 characters.',
        ];
    }
}
