<?php

namespace App\Filament\Widgets;

use App\Models\Lease;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RevenueChartWidget extends ChartWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    public ?string $heading = 'Monthly Revenue Trend';

    protected function getData(): array
    {
        // Get revenue data for the last 12 months
        $months = [];
        $revenues = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $months[] = $month->format('M Y');

            // Calculate total revenue for leases active in that month
            $revenue = Lease::where('workflow_state', 'active')
                ->where('start_date', '<=', $month->endOfMonth())
                ->where(function ($query) use ($month) {
                    $query->where('end_date', '>=', $month->startOfMonth())
                        ->orWhereNull('end_date');
                })
                ->sum('monthly_rent');

            $revenues[] = round($revenue, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue ($)',
                    'data' => $revenues,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => '#3b82f6',
                    'pointBackgroundColor' => '#3b82f6',
                    'pointBorderColor' => '#fff',
                    'pointHoverBackgroundColor' => '#fff',
                    'pointHoverBorderColor' => '#3b82f6',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "$" + value.toLocaleString(); }',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return context.dataset.label + ": $" + context.parsed.y.toLocaleString(); }',
                    ],
                ],
            ],
        ];
    }
}
