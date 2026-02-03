<?php

declare(strict_types=1);

namespace App\Livewire\Pulse;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\View;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;

/**
 * Pulse dashboard card showing CHIPS database connection health.
 *
 * Displays connection status (Connected/Down), response time, and uptime graph.
 */
#[Lazy]
class ChipsDatabaseCard extends Card
{
    /**
     * Render the component.
     */
    public function render(): Renderable
    {
        [$chipsData, $time, $runAt] = $this->remember(function () {
            $chipsHealth = $this->values('chips_health', ['chips_db'])->first();

            $healthData = $chipsHealth
                ? json_decode($chipsHealth->value, true)
                : [
                    'status' => 'not_configured',
                    'message' => 'No data available',
                    'response_time_ms' => 0,
                    'connection' => 'chips_db',
                    'checked_at' => null,
                ];

            // Get response time graph data
            $responseTimeGraph = $this->graph(['chips_response_time'], 'avg');
            $responseTimeData = $responseTimeGraph->get('chips_response_time')?->get('chips_db') ?? [];

            // Get status graph data (for uptime calculation)
            $statusGraph = $this->graph(['chips_status'], 'max');
            $statusData = $statusGraph->get('chips_status')?->get('chips_db') ?? [];

            // Calculate uptime percentage
            $totalChecks = count($statusData);
            $successfulChecks = collect($statusData)->filter(fn ($v) => $v === 1)->count();
            $uptimePercentage = $totalChecks > 0
                ? round(($successfulChecks / $totalChecks) * 100, 2)
                : 100; // Show 100% if no data yet

            return [
                'status' => $healthData['status'] ?? 'not_configured',
                'message' => $healthData['message'] ?? 'Unknown',
                'responseTime' => $healthData['response_time_ms'] ?? 0,
                'connection' => $healthData['connection'] ?? 'chips_db',
                'checkedAt' => $healthData['checked_at'] ?? null,
                'uptimePercentage' => $uptimePercentage,
                'responseTimeGraph' => $responseTimeData,
            ];
        });

        return View::make('livewire.pulse.chips-database-card', [
            'status' => $chipsData['status'],
            'message' => $chipsData['message'],
            'responseTime' => $chipsData['responseTime'],
            'connection' => $chipsData['connection'],
            'checkedAt' => $chipsData['checkedAt'],
            'uptimePercentage' => $chipsData['uptimePercentage'],
            'responseTimeGraph' => $chipsData['responseTimeGraph'],
            'time' => $time,
            'runAt' => $runAt,
        ]);
    }
}
