<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\SMSService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Queued job for sending SMS messages via Africa's Talking API.
 *
 * Prevents SMS API latency from blocking user requests.
 * Automatically retries failed sends up to 3 times with exponential backoff.
 */
class SendSMSJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly string $phone,
        private readonly string $message,
        private readonly array $context = [],
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $sent = SMSService::send($this->phone, $this->message, $this->context);

        if (! $sent && SMSService::isConfigured()) {
            Log::warning('Queued SMS failed to send', [
                'attempt' => $this->attempts(),
                ...$this->context,
            ]);

            // Release back to queue for retry
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1] ?? 60);
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('SMS job permanently failed after all retries', [
            'error' => $exception?->getMessage(),
            ...$this->context,
        ]);
    }
}
