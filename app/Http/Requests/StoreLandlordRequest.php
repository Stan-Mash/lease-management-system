<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLandlordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'landlord_code' => ['nullable', 'string', 'max:50', 'unique:landlords,landlord_code'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^(?:\+254|0)[0-9]{9}$/'],
            'email' => ['nullable', 'email', 'unique:landlords,email'],
            'id_number' => ['required', 'string', 'regex:/^[0-9]{8}$/', 'unique:landlords,id_number'],
            'kra_pin' => ['nullable', 'string', 'regex:/^[A-Z]{1}[0-9]{9}[A-Z]{1}$/'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Landlord name is required',
            'phone.required' => 'Phone number is required',
            'phone.regex' => 'Phone number must be a valid Kenyan number',
            'id_number.regex' => 'ID number must be exactly 8 digits',
            'id_number.unique' => 'A landlord with this ID number already exists',
            'kra_pin.regex' => 'KRA PIN format is invalid (e.g., A123456789B)',
            'email.unique' => 'A landlord with this email already exists',
        ];
    }
}
