<?php

declare(strict_types=1);

namespace App\Livewire\Pulse;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\View;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;

/**
 * Pulse dashboard card showing Africa's Talking SMS balance.
 *
 * Displays current balance, status (healthy/low/error), and trend graph.
 */
#[Lazy]
class SmsBalanceCard extends Card
{
    /**
     * Render the component.
     */
    public function render(): Renderable
    {
        [$smsData, $time, $runAt] = $this->remember(function () {
            $smsBalance = $this->values('sms_balance_status', ['africas_talking'])->first();

            $statusData = $smsBalance
                ? json_decode($smsBalance->value, true)
                : [
                    'status' => 'not_configured',
                    'balance' => 0,
                    'currency' => 'KES',
                    'message' => 'No data available',
                    'threshold' => 1000,
                    'checked_at' => null,
                ];

            // Get historical balance data for graph
            $graph = $this->graph(['sms_balance'], 'max');

            // Convert from cents to KES for display
            $graphData = collect($graph->get('sms_balance')?->get('africas_talking') ?? [])
                ->map(fn ($value) => round($value / 100, 2))
                ->toArray();

            return [
                'status' => $statusData['status'] ?? 'not_configured',
                'balance' => $statusData['balance'] ?? 0,
                'currency' => $statusData['currency'] ?? 'KES',
                'message' => $statusData['message'] ?? 'Unknown',
                'threshold' => $statusData['threshold'] ?? 1000,
                'checkedAt' => $statusData['checked_at'] ?? null,
                'graph' => $graphData,
            ];
        });

        return View::make('livewire.pulse.sms-balance-card', [
            'status' => $smsData['status'],
            'balance' => $smsData['balance'],
            'currency' => $smsData['currency'],
            'message' => $smsData['message'],
            'threshold' => $smsData['threshold'],
            'checkedAt' => $smsData['checkedAt'],
            'graph' => $smsData['graph'],
            'time' => $time,
            'runAt' => $runAt,
        ]);
    }
}
