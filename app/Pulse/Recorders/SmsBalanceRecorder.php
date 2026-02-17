<?php

declare(strict_types=1);

namespace App\Pulse\Recorders;

use Exception;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Pulse\Events\SharedBeat;
use Laravel\Pulse\Pulse;
use RuntimeException;

/**
 * Records Africa's Talking SMS balance for Pulse monitoring.
 *
 * Checks the balance every minute and records it for dashboard display.
 * Alerts when balance falls below configured threshold (default: KES 1,000).
 */
class SmsBalanceRecorder
{
    /**
     * The events to listen for.
     *
     * @var class-string
     */
    public string $listen = SharedBeat::class;

    /**
     * Minimum balance threshold in KES for alerts.
     */
    protected float $alertThreshold;

    /**
     * Africa's Talking API credentials.
     */
    protected ?string $apiKey;

    protected ?string $username;

    public function __construct(
        protected Pulse $pulse,
        protected Repository $config,
    ) {
        $this->apiKey = $config->get('services.africas_talking.api_key');
        $this->username = $config->get('services.africas_talking.username');
        $this->alertThreshold = (float) $config->get('pulse.recorders.' . self::class . '.alert_threshold', 1000);
    }

    /**
     * Record the SMS balance on each shared beat (every ~15 seconds by default).
     */
    public function record(SharedBeat $event): void
    {
        // Only check once per minute to avoid excessive API calls
        if ($event->time->second !== 0) {
            return;
        }

        // Skip if not configured
        if (! $this->isConfigured()) {
            $this->pulse->record(
                type: 'sms_balance',
                key: 'africas_talking',
                value: 0,
                timestamp: $event->time,
            )->max()->onlyBuckets();

            $this->pulse->set(
                type: 'sms_balance_status',
                key: 'africas_talking',
                value: json_encode([
                    'status' => 'not_configured',
                    'balance' => 0,
                    'currency' => 'KES',
                    'message' => 'Africa\'s Talking API not configured',
                    'checked_at' => $event->time->toIso8601String(),
                ]),
                timestamp: $event->time,
            );

            return;
        }

        try {
            $balance = $this->fetchBalance();

            // Record the balance value
            $this->pulse->record(
                type: 'sms_balance',
                key: 'africas_talking',
                value: (int) ($balance * 100), // Store as cents/smallest unit
                timestamp: $event->time,
            )->max()->onlyBuckets();

            // Determine status
            $status = $balance < $this->alertThreshold ? 'low' : 'healthy';
            $message = $balance < $this->alertThreshold
                ? 'Low balance! KES ' . number_format($balance, 2) . ' (threshold: KES ' . number_format($this->alertThreshold, 2) . ')'
                : 'Balance: KES ' . number_format($balance, 2);

            // Store status for card display
            $this->pulse->set(
                type: 'sms_balance_status',
                key: 'africas_talking',
                value: json_encode([
                    'status' => $status,
                    'balance' => $balance,
                    'currency' => 'KES',
                    'threshold' => $this->alertThreshold,
                    'message' => $message,
                    'checked_at' => $event->time->toIso8601String(),
                ]),
                timestamp: $event->time,
            );

            // Log warning if balance is low
            if ($balance < $this->alertThreshold) {
                Log::warning('SMS balance is low', [
                    'balance' => $balance,
                    'threshold' => $this->alertThreshold,
                    'currency' => 'KES',
                ]);
            }

        } catch (Exception $e) {
            Log::error('Failed to fetch SMS balance', [
                'error' => $e->getMessage(),
            ]);

            $this->pulse->set(
                type: 'sms_balance_status',
                key: 'africas_talking',
                value: json_encode([
                    'status' => 'error',
                    'balance' => 0,
                    'currency' => 'KES',
                    'message' => 'Failed to fetch balance: ' . $e->getMessage(),
                    'checked_at' => $event->time->toIso8601String(),
                ]),
                timestamp: $event->time,
            );
        }
    }

    /**
     * Fetch balance from Africa's Talking API.
     */
    protected function fetchBalance(): float
    {
        $response = Http::withHeaders([
            'apiKey' => $this->apiKey,
            'Accept' => 'application/json',
        ])->get('https://api.africastalking.com/version1/user', [
            'username' => $this->username,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('API request failed with status: ' . $response->status());
        }

        $data = $response->json();

        // Africa's Talking returns balance in format like "KES 1234.56"
        $balanceString = $data['UserData']['balance'] ?? '0';

        // Extract numeric value
        preg_match('/[\d,]+\.?\d*/', $balanceString, $matches);
        $balance = (float) str_replace(',', '', $matches[0] ?? '0');

        return $balance;
    }

    /**
     * Check if Africa's Talking is configured.
     */
    protected function isConfigured(): bool
    {
        return ! empty($this->apiKey) && ! empty($this->username);
    }
}
