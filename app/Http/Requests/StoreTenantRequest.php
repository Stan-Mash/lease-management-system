<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->canManageLeases();
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'id_number' => ['required', 'string', 'regex:/^[0-9]{8}$/', 'unique:tenants,id_number'],
            'phone_number' => ['required', 'string', 'regex:/^(?:\+254|0)[0-9]{9}$/'],
            'email' => ['nullable', 'email', 'unique:tenants,email'],
            'kra_pin' => ['nullable', 'string', 'regex:/^[A-Z]{1}[0-9]{9}[A-Z]{1}$/'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'employer_name' => ['nullable', 'string', 'max:255'],
            'next_of_kin_name' => ['nullable', 'string', 'max:255'],
            'next_of_kin_phone' => ['nullable', 'string', 'regex:/^(?:\+254|0)[0-9]{9}$/'],
            'notification_preference' => ['nullable', 'in:email,sms,both,none'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_number.required' => 'National ID number is required',
            'id_number.regex' => 'ID number must be exactly 8 digits',
            'id_number.unique' => 'A tenant with this ID number already exists',
            'phone_number.regex' => 'Phone number must be a valid Kenyan number',
            'kra_pin.regex' => 'KRA PIN format is invalid (e.g., A123456789B)',
            'email.unique' => 'A tenant with this email already exists',
        ];
    }
}
