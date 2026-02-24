<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for full lease details in landlord approval show (apiShow).
 * Keeps response structure DRY and consistent with apiIndex.
 */
class LandlordLeaseDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tenant = $this->tenant;
        $approval = $this->relationLoaded('approvals')
            ? $this->approvals->sortByDesc('created_at')->first()
            : $this->getLatestApproval();

        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'workflow_state' => $this->workflow_state,
            'lease_type' => ucfirst($this->lease_type ?? ''),
            'lease_source' => $this->lease_source ?? null,
            'monthly_rent' => $this->monthly_rent,
            'currency' => $this->currency ?? 'KES',
            'security_deposit' => $this->security_deposit ?? $this->deposit_amount,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'property_address' => $this->property_address ?? null,
            'special_terms' => $this->special_terms ?? null,
            'tenant' => [
                'name' => $tenant?->names ?? $tenant?->full_name,
                'phone' => $tenant?->mobile_number ?? $tenant?->phone_number,
                'email' => $tenant?->email_address ?? $tenant?->email,
            ],
            'guarantors' => $this->when($this->relationLoaded('guarantors'), function () {
                return $this->guarantors->map(fn ($g) => [
                    'name' => $g->name,
                    'phone' => $g->phone,
                    'relationship' => $g->relationship ?? null,
                    'guarantee_amount' => $g->guarantee_amount ?? null,
                ]);
            }),
            'approval' => $approval ? [
                'status' => $approval->decision ?? 'pending',
                'comments' => $approval->comments,
                'rejection_reason' => $approval->rejection_reason,
                'reviewed_at' => $approval->reviewed_at?->toISOString(),
            ] : null,
        ];
    }
}
