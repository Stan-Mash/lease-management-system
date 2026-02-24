<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for a lease in the landlord approval pending list.
 * Uses CHIPS column names for tenant (names, mobile_number, email_address).
 */
class LandlordPendingLeaseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tenant = $this->tenant;

        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'tenant' => [
                'name' => $tenant?->names ?? $tenant?->full_name,
                'phone' => $tenant?->mobile_number ?? $tenant?->phone_number,
                'email' => $tenant?->email_address ?? $tenant?->email,
            ],
            'lease_type' => ucfirst($this->lease_type ?? ''),
            'monthly_rent' => $this->monthly_rent,
            'currency' => $this->currency ?? 'KES',
            'security_deposit' => $this->security_deposit ?? $this->deposit_amount,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
