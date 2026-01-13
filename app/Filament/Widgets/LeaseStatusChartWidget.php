<?php

namespace App\Filament\Widgets;

use App\Models\Lease;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class LeaseStatusChartWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    public ?string $heading = 'Lease Status Distribution';
    public ?string $maxHeight = '300px';

    protected function getData(): array
    {
        // Get count by workflow state
        $statusCounts = Lease::select('workflow_state', DB::raw('count(*) as count'))
            ->groupBy('workflow_state')
            ->get()
            ->pluck('count', 'workflow_state');

        // Define colors for each status
        $colors = [
            'draft' => '#94a3b8',
            'approved' => '#3b82f6',
            'active' => '#10b981',
            'pending_tenant_signature' => '#f59e0b',
            'pending_deposit' => '#f59e0b',
            'expired' => '#ef4444',
            'terminated' => '#dc2626',
            'cancelled' => '#64748b',
            'archived' => '#475569',
        ];

        $labels = [];
        $data = [];
        $backgroundColor = [];

        foreach ($statusCounts as $status => $count) {
            $labels[] = ucwords(str_replace('_', ' ', $status));
            $data[] = $count;
            $backgroundColor[] = $colors[$status] ?? '#6b7280';
        }

        return [
            'datasets' => [
                [
                    'label' => 'Leases',
                    'data' => $data,
                    'backgroundColor' => $backgroundColor,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
