<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitSignatureRequest extends FormRequest
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
        return [
            'signature_data' => ['required', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'screen_resolution' => ['nullable', 'string', 'max:20'],
            'lessee_witness_name' => ['required', 'string', 'max:255'],
            'lessee_witness_id' => ['required', 'string', 'max:100'],
            'witness_signature_data' => ['required', 'string'],
            'advocate_selection' => ['required', 'in:chabrin_advocate,own_advocate'],
            'tenant_advocate_name' => ['required_if:advocate_selection,own_advocate', 'nullable', 'string', 'max:255'],
            'tenant_advocate_email' => ['required_if:advocate_selection,own_advocate', 'nullable', 'email', 'max:255'],
            'tenant_advocate_phone' => ['required_if:advocate_selection,own_advocate', 'nullable', 'string', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'signature_data.required' => 'Please draw your signature before submitting.',
        ];
    }
}
