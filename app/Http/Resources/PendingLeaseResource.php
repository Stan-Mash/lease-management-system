<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for pending lease approval data.
 *
 * Replaces inline ->map() closures across FieldOfficerController
 * and LandlordApprovalController with a consistent, reusable transformer.
 */
class PendingLeaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'landlord' => $this->when($this->relationLoaded('landlord') && $this->landlord, [
                'id' => $this->landlord?->id,
                'name' => $this->landlord?->names ?? $this->landlord?->name,
                'phone' => $this->landlord?->mobile_number ?? $this->landlord?->phone ?? null,
                'email' => $this->landlord?->email_address ?? $this->landlord?->email ?? null,
            ]),
            'tenant' => $this->when($this->relationLoaded('tenant') && $this->tenant, [
                'name' => $this->tenant?->names ?? $this->tenant?->full_name,
                'phone' => $this->tenant?->mobile_number ?? $this->tenant?->phone_number ?? null,
                'email' => $this->tenant?->email_address ?? $this->tenant?->email ?? null,
            ]),
            'lease_type' => ucfirst(str_replace('_', ' ', $this->lease_type ?? '')),
            'monthly_rent' => $this->monthly_rent,
            'currency' => $this->currency ?? 'KES',
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'submitted_at' => $this->created_at?->toISOString(),
            'pending_hours' => $this->created_at?->diffInHours(now()),
            'is_overdue' => $this->created_at ? $this->created_at->lt(now()->subHours(24)) : false,
        ];
    }
}
