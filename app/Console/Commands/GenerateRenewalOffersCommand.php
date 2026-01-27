<?php

namespace App\Console\Commands;

use App\Services\LeaseRenewalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateRenewalOffersCommand extends Command
{
    protected $signature = 'leases:generate-renewal-offers
                            {--days=60 : Days before expiry to generate offer}
                            {--dry-run : Show what would be generated without actually creating}';

    protected $description = 'Generate renewal offers for expiring leases';

    public function handle(LeaseRenewalService $renewalService): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("Checking for leases expiring in {$days} days...");

        $eligibleLeases = $renewalService->getEligibleLeases($days);

        if ($eligibleLeases->isEmpty()) {
            $this->line('No eligible leases found for renewal offers.');
            return self::SUCCESS;
        }

        $this->info("Found {$eligibleLeases->count()} eligible leases.");

        $created = 0;

        foreach ($eligibleLeases as $lease) {
            if ($dryRun) {
                $this->line("  [DRY RUN] Would create renewal offer for {$lease->reference_number}");
                $this->line("    New rent: KES " . number_format($renewalService->calculateRenewalRent($lease), 2));
                $created++;
                continue;
            }

            try {
                $renewalLease = $renewalService->createRenewalOffer($lease);
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
}
