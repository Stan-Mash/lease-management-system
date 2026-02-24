<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\LeaseWorkflowState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaseTransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'new_state' => ['required', 'string', Rule::enum(LeaseWorkflowState::class)],
        ];
    }
}
