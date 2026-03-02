<?php

namespace App\Jobs;

use App\Models\Lease;
use App\Services\DigitalSigningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendSignedConfirmationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     * Retries handle transient email/PDF failures without losing the confirmation.
     */
    public int $tries = 3;

    /**
     * Exponential backoff: wait 30s, then 60s, then 120s between retries.
     */
    public array $backoff = [30, 60, 120];

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly string $leaseId) {}

    /**
     * Execute the job.
     * Sends the tenant their final signed lease confirmation email and PDF.
     */
    public function handle(): void
    {
        $lease = Lease::find($this->leaseId);

        if (! $lease) {
            Log::warning('SendSignedConfirmationsJob: lease not found, skipping.', [
                'lease_id' => $this->leaseId,
            ]);

            return;
        }

        // Guard: only send if the lease is still active (state may have changed since dispatch)
        if ($lease->workflow_state !== 'active') {
            Log::info('SendSignedConfirmationsJob: lease no longer active, skipping.', [
                'lease_id'       => $this->leaseId,
                'workflow_state' => $lease->workflow_state,
            ]);

            return;
        }

        DigitalSigningService::sendSignedConfirmations($lease);

        Log::info('SendSignedConfirmationsJob: confirmations sent successfully.', [
            'lease_id' => $this->leaseId,
        ]);
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('SendSignedConfirmationsJob permanently failed after all retries.', [
            'lease_id' => $this->leaseId,
            'error'    => $exception?->getMessage(),
        ]);
    }
}
