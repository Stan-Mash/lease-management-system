<?php

namespace App\Console\Commands;

use App\Enums\LeaseWorkflowState;
use App\Models\Lease;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateRenewalOffersCommand extends Command
{
    protected $signature = 'leases:generate-renewal-offers
                            {--days=60 : Days before expiry to generate offer}
                            {--dry-run : Show what would be generated without actually creating}';

    protected $description = 'Generate renewal offers for expiring leases';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("Checking for leases expiring in {$days} days...");

        $targetDate = now()->addDays($days)->format('Y-m-d');

        $eligibleLeases = Lease::query()
            ->where('status', 'active')
            ->whereDate('end_date', $targetDate)
            ->whereDoesntHave('renewalLease') // No renewal already created
            ->with(['tenant', 'landlord', 'unit.property', 'zone'])
            ->get();

        if ($eligibleLeases->isEmpty()) {
            $this->line('No eligible leases found for renewal offers.');
            return self::SUCCESS;
        }

        $this->info("Found {$eligibleLeases->count()} eligible leases.");

        $created = 0;

        foreach ($eligibleLeases as $lease) {
            if ($dryRun) {
                $this->line("  [DRY RUN] Would create renewal offer for {$lease->reference_number}");
                $created++;
                continue;
            }

            try {
                $renewalLease = $this->createRenewalOffer($lease);
                $this->line("  Created renewal offer {$renewalLease->reference_number} for {$lease->reference_number}");
                $created++;
            } catch (\Exception $e) {
                $this->error("  Failed to create renewal for {$lease->reference_number}: {$e->getMessage()}");
                Log::error('Renewal offer generation failed', [
                    'lease_id' => $lease->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Generated {$created} renewal offers.");

        return self::SUCCESS;
    }

    private function createRenewalOffer(Lease $originalLease): Lease
    {
        return DB::transaction(function () use ($originalLease) {
            // Calculate new dates (1 year from original end date)
            $newStartDate = $originalLease->end_date->addDay();
            $newEndDate = $newStartDate->copy()->addYear()->subDay();

            // Calculate new rent with escalation if configured
            $escalationRate = config('lease.default_escalation_rate', 0.10); // 10% default
            $newRent = $originalLease->monthly_rent * (1 + $escalationRate);

            // Create the renewal lease
            $renewalLease = Lease::create([
                'tenant_id' => $originalLease->tenant_id,
                'landlord_id' => $originalLease->landlord_id,
                'unit_id' => $originalLease->unit_id,
                'zone_id' => $originalLease->zone_id,
                'template_id' => $originalLease->template_id,
                'lease_type' => $originalLease->lease_type,
                'source' => $originalLease->source,
                'signing_mode' => $originalLease->signing_mode,
                'start_date' => $newStartDate,
                'end_date' => $newEndDate,
                'monthly_rent' => round($newRent, 2),
                'deposit_amount' => $originalLease->deposit_amount,
                'requires_guarantor' => $originalLease->requires_guarantor,
                'requires_lawyer' => $originalLease->requires_lawyer,
                'status' => LeaseWorkflowState::RENEWAL_OFFERED->value,
                'renewal_of_lease_id' => $originalLease->id,
                'created_by' => null, // System generated
            ]);

            // Update original lease status
            $originalLease->update([
                'status' => LeaseWorkflowState::RENEWAL_OFFERED->value,
            ]);

            // Log the action
            $originalLease->auditLogs()->create([
                'action' => 'renewal_offer_generated',
                'changes' => [
                    'renewal_lease_id' => $renewalLease->id,
                    'renewal_reference' => $renewalLease->reference_number,
                    'new_rent' => $renewalLease->monthly_rent,
                    'escalation_rate' => $escalationRate,
                ],
                'performed_by' => null,
            ]);

            return $renewalLease;
        });
    }
}
