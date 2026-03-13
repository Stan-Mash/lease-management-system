<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\LeaseWorkflowState;
use App\Models\Lease;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Activate fully-executed leases whose start_date has arrived.
 *
 * Run daily via the scheduler. Covers the gap where a lease was fully executed
 * before its start_date (e.g. signed two weeks before the tenancy begins). The
 * LeaseObserver handles the same logic synchronously at the moment of final
 * signing, but if start_date was set in the future at that point, this command
 * picks it up the morning it becomes due.
 */
class ActivateMatureLeases extends Command
{
    protected $signature   = 'leases:activate-mature';
    protected $description = 'Transition fully_executed leases to active when their start_date has arrived';

    public function handle(): int
    {
        $today = now(config('app.timezone'))->startOfDay();

        $leases = Lease::where('workflow_state', LeaseWorkflowState::FULLY_EXECUTED->value)
            ->where(function ($q) use ($today) {
                $q->whereNull('start_date')           // no date set → activate immediately
                  ->orWhere('start_date', '<=', $today);
            })
            ->get();

        if ($leases->isEmpty()) {
            $this->info('No fully-executed leases are due for activation today.');
            return self::SUCCESS;
        }

        $activated = 0;
        foreach ($leases as $lease) {
            try {
                if ($lease->canTransitionTo(LeaseWorkflowState::ACTIVE)) {
                    $lease->transitionTo(LeaseWorkflowState::ACTIVE);
                    $activated++;
                    Log::info('ActivateMatureLeases: lease activated', [
                        'lease_id'   => $lease->id,
                        'reference'  => $lease->reference_number,
                        'start_date' => $lease->start_date?->format('Y-m-d'),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('ActivateMatureLeases: failed to activate lease', [
                    'lease_id' => $lease->id,
                    'error'    => $e->getMessage(),
                ]);
                $this->warn("  Failed: lease #{$lease->id} — {$e->getMessage()}");
            }
        }

        $this->info("Activated {$activated} / {$leases->count()} fully-executed lease(s).");

        return self::SUCCESS;
    }
}
