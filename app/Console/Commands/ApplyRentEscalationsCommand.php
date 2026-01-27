<?php

namespace App\Console\Commands;

use App\Mail\RentEscalationLandlordNotice;
use App\Mail\RentEscalationTenantNotice;
use App\Models\RentEscalation;
use App\Services\SMSService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ApplyRentEscalationsCommand extends Command
{
    protected $signature = 'leases:apply-rent-escalations
                            {--dry-run : Show what would be applied without actually applying}
                            {--notify-only : Only send notifications for upcoming escalations}';

    protected $description = 'Apply due rent escalations and notify about upcoming ones';

    public function handle(SMSService $smsService): int
    {
        $dryRun = $this->option('dry-run');
        $notifyOnly = $this->option('notify-only');

        if (!$notifyOnly) {
            $this->applyDueEscalations($dryRun);
        }

        $this->sendUpcomingNotifications($smsService, $dryRun);

        return self::SUCCESS;
    }

    private function applyDueEscalations(bool $dryRun): void
    {
        $this->info('Checking for due rent escalations...');

        $dueEscalations = RentEscalation::query()
            ->where('applied', false)
            ->whereDate('effective_date', '<=', now())
            ->with(['lease.tenant', 'lease.landlord'])
            ->get();

        if ($dueEscalations->isEmpty()) {
            $this->line('  No due rent escalations found.');
            return;
        }

        $this->info("Found {$dueEscalations->count()} due escalations.");

        foreach ($dueEscalations as $escalation) {
            if ($dryRun) {
                $this->line("  [DRY RUN] Would apply escalation #{$escalation->id} for lease {$escalation->lease->reference_number}");
                $this->line("    Previous: KES {$escalation->previous_rent} -> New: KES {$escalation->new_rent}");
                continue;
            }

            try {
                DB::transaction(function () use ($escalation) {
                    // Apply the escalation (updates lease rent)
                    $escalation->apply(0); // System-applied, no user ID

                    // Log the change
                    $escalation->lease->auditLogs()->create([
                        'action' => 'rent_escalation_applied',
                        'changes' => [
                            'escalation_id' => $escalation->id,
                            'previous_rent' => $escalation->previous_rent,
                            'new_rent' => $escalation->new_rent,
                            'increase_percentage' => $escalation->increase_percentage,
                        ],
                        'performed_by' => null,
                    ]);
                });

                $this->line("  Applied escalation for lease {$escalation->lease->reference_number}");

            } catch (\Exception $e) {
                $this->error("  Failed to apply escalation #{$escalation->id}: {$e->getMessage()}");
                Log::error('Rent escalation failed', [
                    'escalation_id' => $escalation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function sendUpcomingNotifications(SMSService $smsService, bool $dryRun): void
    {
        $this->info('Checking for upcoming rent escalations (30 days)...');

        $upcomingEscalations = RentEscalation::query()
            ->where('applied', false)
            ->where('tenant_notified', false)
            ->whereBetween('effective_date', [
                now()->addDays(25),
                now()->addDays(35),
            ])
            ->with(['lease.tenant', 'lease.landlord', 'lease.unit.property'])
            ->get();

        if ($upcomingEscalations->isEmpty()) {
            $this->line('  No upcoming escalations to notify.');
            return;
        }

        $this->info("Found {$upcomingEscalations->count()} escalations to notify.");

        foreach ($upcomingEscalations as $escalation) {
            $lease = $escalation->lease;

            if ($dryRun) {
                $this->line("  [DRY RUN] Would notify about escalation for {$lease->reference_number}");
                continue;
            }

            try {
                // Notify tenant by SMS
                if ($lease->tenant?->phone) {
                    $message = $this->buildTenantSMS($escalation);
                    $smsService->send($lease->tenant->phone, $message);
                }

                // Notify tenant by email if preferred
                if ($lease->tenant?->email && in_array($lease->tenant->notification_preference ?? 'sms', ['email', 'both'])) {
                    Mail::to($lease->tenant->email)
                        ->send(new RentEscalationTenantNotice($escalation));
                }

                // Notify landlord by email
                if ($lease->landlord?->email) {
                    Mail::to($lease->landlord->email)
                        ->send(new RentEscalationLandlordNotice($escalation));
                    $escalation->markLandlordNotified();
                }

                $escalation->markTenantNotified();
                $this->line("  Notified about escalation for {$lease->reference_number}");

            } catch (\Exception $e) {
                $this->error("  Failed to notify for escalation #{$escalation->id}: {$e->getMessage()}");
                Log::error('Rent escalation notification failed', [
                    'escalation_id' => $escalation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function buildTenantSMS(RentEscalation $escalation): string
    {
        $lease = $escalation->lease;
        $property = $lease->unit?->property?->name ?? 'your property';
        $effectiveDate = $escalation->effective_date->format('d M Y');

        return "Rent Adjustment Notice: From {$effectiveDate}, your rent for {$property} "
            . "will be KES {$escalation->new_rent}/month (was KES {$escalation->previous_rent}). "
            . "Ref: {$lease->reference_number}. Contact Chabrin for questions.";
    }
}
