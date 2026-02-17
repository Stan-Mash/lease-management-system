<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Exception;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Filament dashboard widget showing Africa's Talking SMS balance.
 */
class SmsBalanceWidget extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $data = $this->getSmsBalanceData();

        $stat = Stat::make('SMS Balance', $data['currency'] . ' ' . number_format($data['balance'], 2))
            ->description($data['message'])
            ->descriptionIcon($this->getStatusIcon($data['status']))
            ->color($this->getStatusColor($data['status']));

        // Add trend chart if we have historical data
        if (! empty($data['chart'])) {
            $stat->chart($data['chart']);
        }

        return [$stat];
    }

    protected function getSmsBalanceData(): array
    {
        // Try to get from Pulse storage first
        $pulseData = $this->getPulseData();
        if ($pulseData) {
            return $pulseData;
        }

        // Fallback: fetch directly with caching
        return Cache::remember('sms_balance_widget', 60, function () {
            return $this->fetchBalanceDirectly();
        });
    }

    protected function getPulseData(): ?array
    {
        try {
            $record = DB::table('pulse_values')
                ->where('type', 'sms_balance_status')
                ->where('key', 'africas_talking')
                ->orderByDesc('timestamp')
                ->first();

            if ($record) {
                $data = json_decode($record->value, true);

                // Get chart data from pulse_aggregates
                $chartData = DB::table('pulse_aggregates')
                    ->where('type', 'sms_balance')
                    ->where('key', 'africas_talking')
                    ->where('period', 60) // hourly buckets
                    ->orderBy('bucket')
                    ->limit(24)
                    ->pluck('value')
                    ->map(fn ($v) => round($v / 100, 2))
                    ->toArray();

                return [
                    'status' => $data['status'] ?? 'unknown',
                    'balance' => $data['balance'] ?? 0,
                    'currency' => $data['currency'] ?? 'KES',
                    'message' => $data['message'] ?? 'Unknown',
                    'threshold' => $data['threshold'] ?? 1000,
                    'chart' => $chartData,
                ];
            }
        } catch (Exception $e) {
            Log::debug('Could not fetch Pulse SMS data: ' . $e->getMessage());
        }

        return null;
    }

    protected function fetchBalanceDirectly(): array
    {
        $apiKey = config('services.africas_talking.api_key');
        $username = config('services.africas_talking.username');

        if (empty($apiKey) || empty($username)) {
            return [
                'status' => 'not_configured',
                'balance' => 0,
                'currency' => 'KES',
                'message' => 'API not configured',
                'threshold' => 1000,
                'chart' => [],
            ];
        }

        try {
            $response = Http::withHeaders([
                'apiKey' => $apiKey,
                'Accept' => 'application/json',
            ])->timeout(10)->get('https://api.africastalking.com/version1/user', [
                'username' => $username,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $balanceString = $data['UserData']['balance'] ?? '0';
                preg_match('/[\d,]+\.?\d*/', $balanceString, $matches);
                $balance = (float) str_replace(',', '', $matches[0] ?? '0');
                $threshold = 1000;

                return [
                    'status' => $balance < $threshold ? 'low' : 'healthy',
                    'balance' => $balance,
                    'currency' => 'KES',
                    'message' => $balance < $threshold ? 'Low balance - top up soon' : 'Balance healthy',
                    'threshold' => $threshold,
                    'chart' => [],
                ];
            }
        } catch (Exception $e) {
            Log::error('SMS Balance fetch failed: ' . $e->getMessage());
        }

        return [
            'status' => 'error',
            'balance' => 0,
            'currency' => 'KES',
            'message' => 'Failed to fetch balance',
            'threshold' => 1000,
            'chart' => [],
        ];
    }

    protected function getStatusColor(string $status): string
    {
        return match ($status) {
            'healthy' => 'success',
            'low' => 'warning',
            'error' => 'danger',
            'not_configured' => 'gray',
            default => 'gray',
        };
    }

    protected function getStatusIcon(string $status): string
    {
        return match ($status) {
            'healthy' => 'heroicon-m-check-circle',
            'low' => 'heroicon-m-exclamation-triangle',
            'error' => 'heroicon-m-x-circle',
            'not_configured' => 'heroicon-m-cog-6-tooth',
            default => 'heroicon-m-question-mark-circle',
        };
    }
}
