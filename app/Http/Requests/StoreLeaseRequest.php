<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->canManageLeases();
    }

    public function rules(): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:30', 'unique:leases'],
            'source' => ['required', 'in:chabrin,landlord'],
            'lease_type' => ['required', 'in:commercial,residential_micro,residential_major'],
            'signing_mode' => ['required', 'in:digital,physical'],
            'tenant_id' => ['required', 'exists:tenants,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'property_id' => ['required', 'exists:properties,id'],
            'landlord_id' => ['required', 'exists:landlords,id'],
            'zone' => ['required', 'regex:/^[A-G]$/'],
            'monthly_rent' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'deposit_amount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'lease_term_months' => ['required', 'integer', 'min:1'],
            'is_periodic' => ['nullable', 'boolean'],
            'requires_lawyer' => ['nullable', 'boolean'],
            'requires_guarantor' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reference_number.unique' => 'A lease with this reference number already exists',
            'unit_id.exists' => 'The selected unit does not exist',
            'tenant_id.exists' => 'The selected tenant does not exist',
            'property_id.exists' => 'The selected property does not exist',
            'landlord_id.exists' => 'The selected landlord does not exist',
            'zone.regex' => 'Zone must be a single letter (A-G)',
            'monthly_rent.numeric' => 'Monthly rent must be a valid number',
            'end_date.after' => 'End date must be after start date',
        ];
    }
}
