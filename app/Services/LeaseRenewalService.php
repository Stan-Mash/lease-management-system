<?php

namespace App\Services;

use App\Enums\LeaseWorkflowState;
use App\Models\Lease;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaseRenewalService
{
    /**
     * Create a renewal offer for an existing lease.
     * Callable from Artisan commands, Filament UI, or API.
     */
    public function createRenewalOffer(Lease $lease, ?User $performedBy = null): Lease
    {
        $this->validateEligibility($lease);

        return DB::transaction(function () use ($lease, $performedBy) {
            $config = $this->getRenewalConfig($lease);

            $renewalLease = Lease::create($this->buildRenewalAttributes($lease, $config));

            // Update original lease status
            $lease->update([
                'status' => LeaseWorkflowState::RENEWAL_OFFERED->value,
            ]);

            // Log the action
            $lease->auditLogs()->create([
                'action' => 'renewal_offer_generated',
                'changes' => [
                    'renewal_lease_id' => $renewalLease->id,
                    'renewal_reference' => $renewalLease->reference_number,
                    'new_rent' => $renewalLease->monthly_rent,
                    'escalation_rate' => $config['escalation_rate'],
                ],
                'performed_by' => $performedBy?->id,
            ]);

            return $renewalLease;
        });
    }

    /**
     * Calculate the renewal rent based on escalation rate.
     */
    public function calculateRenewalRent(Lease $lease, ?float $customRate = null): float
    {
        $rate = $customRate ?? config('lease.renewal.default_escalation_rate', 0.10);
        return round($lease->monthly_rent * (1 + $rate), 2);
    }

    /**
     * Validate that a lease is eligible for renewal.
     *
     * @throws \InvalidArgumentException
     */
    public function validateEligibility(Lease $lease): void
    {
        if (!$lease->tenant || !$lease->landlord) {
            throw new \InvalidArgumentException(
                'Lease must have both a tenant and landlord to be eligible for renewal.'
            );
        }

        if ($lease->renewalLease && $lease->renewalLease()->exists()) {
            throw new \InvalidArgumentException(
                'A renewal offer already exists for this lease.'
            );
        }

        if (!in_array($lease->status, ['active', LeaseWorkflowState::ACTIVE->value])) {
            throw new \InvalidArgumentException(
                'Only active leases can be renewed. Current status: ' . $lease->status
            );
        }
    }

    /**
     * Get eligible leases for renewal offers.
     */
    public function getEligibleLeases(int $daysBeforeExpiry = null): \Illuminate\Database\Eloquent\Collection
    {
        $days = $daysBeforeExpiry ?? config('lease.renewal.offer_days_before_expiry', 60);
        $targetDate = now()->addDays($days)->format('Y-m-d');

        return Lease::query()
            ->where('status', 'active')
            ->whereDate('end_date', $targetDate)
            ->whereDoesntHave('renewalLease')
            ->with(['tenant', 'landlord', 'unit.property', 'zone'])
            ->get();
    }

    /**
     * Get renewal configuration for a lease.
     */
    protected function getRenewalConfig(Lease $lease): array
    {
        $escalationRate = config('lease.renewal.default_escalation_rate', 0.10);
        $termMonths = config('lease.renewal.default_term_months', 12);

        return [
            'escalation_rate' => $escalationRate,
            'term_months' => $termMonths,
            'new_rent' => $this->calculateRenewalRent($lease, $escalationRate),
            'new_start_date' => $lease->end_date->addDay(),
            'new_end_date' => $lease->end_date->addDay()->addMonths($termMonths)->subDay(),
        ];
    }

    /**
     * Build the attributes array for a renewal lease.
     */
    protected function buildRenewalAttributes(Lease $lease, array $config): array
    {
        return [
            'tenant_id' => $lease->tenant_id,
            'landlord_id' => $lease->landlord_id,
            'unit_id' => $lease->unit_id,
            'zone_id' => $lease->zone_id,
            'template_id' => $lease->template_id,
            'lease_type' => $lease->lease_type,
            'source' => $lease->source,
            'signing_mode' => $lease->signing_mode,
            'start_date' => $config['new_start_date'],
            'end_date' => $config['new_end_date'],
            'monthly_rent' => $config['new_rent'],
            'deposit_amount' => $lease->deposit_amount,
            'requires_guarantor' => $lease->requires_guarantor,
            'requires_lawyer' => $lease->requires_lawyer,
            'status' => LeaseWorkflowState::RENEWAL_OFFERED->value,
            'renewal_of_lease_id' => $lease->id,
            'created_by' => null,
        ];
    }
}
