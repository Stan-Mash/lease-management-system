<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Exception;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Filament dashboard widget showing CHIPS database connection health.
 */
class ChipsDatabaseWidget extends BaseWidget
{
    protected ?string $pollingInterval = '15s';

    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $data = $this->getChipsHealthData();

        $statusLabel = match ($data['status']) {
            'connected' => 'Connected',
            'down' => 'Down',
            'not_configured' => 'Not Configured',
            default => 'Unknown',
        };

        $stat = Stat::make('CHIPS Database', $statusLabel)
            ->description($data['message'])
            ->descriptionIcon($this->getStatusIcon($data['status']))
            ->color($this->getStatusColor($data['status']));

        // Add response time chart if we have historical data
        if (! empty($data['chart'])) {
            $stat->chart($data['chart']);
        }

        $responseTimeStat = Stat::make('Response Time', $data['responseTime'] . 'ms')
            ->description('Uptime: ' . $data['uptimePercentage'] . '%')
            ->descriptionIcon($data['responseTime'] > 500 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-clock')
            ->color($data['responseTime'] > 500 ? 'warning' : 'success');

        return [$stat, $responseTimeStat];
    }

    protected function getChipsHealthData(): array
    {
        // Try to get from Pulse storage first
        $pulseData = $this->getPulseData();
        if ($pulseData) {
            return $pulseData;
        }

        // Fallback: check directly with caching
        return Cache::remember('chips_health_widget', 15, function () {
            return $this->checkConnectionDirectly();
        });
    }

    protected function getPulseData(): ?array
    {
        try {
            $record = DB::table('pulse_values')
                ->where('type', 'chips_health')
                ->where('key', 'chips_db')
                ->orderByDesc('timestamp')
                ->first();

            if ($record) {
                $data = json_decode($record->value, true);

                // Get response time chart data
                $chartData = DB::table('pulse_aggregates')
                    ->where('type', 'chips_response_time')
                    ->where('key', 'chips_db')
                    ->where('period', 60)
                    ->orderBy('bucket')
                    ->limit(24)
                    ->pluck('value')
                    ->toArray();

                // Calculate uptime from status records
                $statusRecords = DB::table('pulse_aggregates')
                    ->where('type', 'chips_status')
                    ->where('key', 'chips_db')
                    ->where('period', 60)
                    ->orderByDesc('bucket')
                    ->limit(24)
                    ->pluck('value')
                    ->toArray();

                $totalChecks = count($statusRecords);
                $successfulChecks = collect($statusRecords)->filter(fn ($v) => $v == 1)->count();
                $uptimePercentage = $totalChecks > 0
                    ? round(($successfulChecks / $totalChecks) * 100, 1)
                    : 100;

                return [
                    'status' => $data['status'] ?? 'unknown',
                    'message' => $data['message'] ?? 'Unknown',
                    'responseTime' => round($data['response_time_ms'] ?? 0),
                    'connection' => $data['connection'] ?? 'chips_db',
                    'uptimePercentage' => $uptimePercentage,
                    'chart' => $chartData,
                ];
            }
        } catch (Exception $e) {
            Log::debug('Could not fetch Pulse CHIPS data: ' . $e->getMessage());
        }

        return null;
    }

    protected function checkConnectionDirectly(): array
    {
        $connectionName = config('pulse.recorders.' . \App\Pulse\Recorders\ChipsDatabaseRecorder::class . '.connection', 'chips_db');

        // Check if connection is configured
        if (! config("database.connections.{$connectionName}")) {
            return [
                'status' => 'not_configured',
                'message' => "Connection '{$connectionName}' not configured",
                'responseTime' => 0,
                'connection' => $connectionName,
                'uptimePercentage' => 0,
                'chart' => [],
            ];
        }

        $startTime = microtime(true);

        try {
            $connection = DB::connection($connectionName);
            $result = $connection->selectOne('SELECT 1 as health_check');
            $responseTime = (microtime(true) - $startTime) * 1000;

            if ($result && property_exists($result, 'health_check') && $result->health_check === 1) {
                return [
                    'status' => 'connected',
                    'message' => 'CHIPS database is connected',
                    'responseTime' => round($responseTime),
                    'connection' => $connectionName,
                    'uptimePercentage' => 100,
                    'chart' => [],
                ];
            }
        } catch (Exception $e) {
            $responseTime = (microtime(true) - $startTime) * 1000;
            Log::error('CHIPS health check failed: ' . $e->getMessage());

            return [
                'status' => 'down',
                'message' => 'Connection failed',
                'responseTime' => round($responseTime),
                'connection' => $connectionName,
                'uptimePercentage' => 0,
                'chart' => [],
            ];
        }

        return [
            'status' => 'unknown',
            'message' => 'Unexpected result',
            'responseTime' => 0,
            'connection' => $connectionName,
            'uptimePercentage' => 0,
            'chart' => [],
        ];
    }

    protected function getStatusColor(string $status): string
    {
        return match ($status) {
            'connected' => 'success',
            'down' => 'danger',
            'not_configured' => 'gray',
            default => 'warning',
        };
    }

    protected function getStatusIcon(string $status): string
    {
        return match ($status) {
            'connected' => 'heroicon-m-check-circle',
            'down' => 'heroicon-m-x-circle',
            'not_configured' => 'heroicon-m-cog-6-tooth',
            default => 'heroicon-m-question-mark-circle',
        };
    }
}
