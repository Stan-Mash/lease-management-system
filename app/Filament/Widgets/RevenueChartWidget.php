<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDateFiltering;
use App\Filament\Widgets\Concerns\HasLeaseQueryFiltering;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class RevenueChartWidget extends ChartWidget
{
    use HasDateFiltering;
    use HasLeaseQueryFiltering;

    public ?string $heading = 'Revenue Trend';

    public ?string $maxHeight = '300px';

    protected static ?int $sort = 5;

    protected ?string $pollingInterval = null;

    public function mount(): void
    {
        $this->setDateFilterFromRequest();
    }

    #[On('dateFilterUpdated')]
    public function updateDateFilter($dateFilter = null, $startDate = null, $endDate = null): void
    {
        $this->dateFilter = $dateFilter;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    protected function getData(): array
    {
        // Get base query with filters
        $query = $this->getFilteredQuery();

        // Determine grouping based on date filter
        $groupBy = $this->getGroupingPeriod();

        // Get revenue data grouped by period
        $revenueData = (clone $query)
            ->where('workflow_state', 'active')
            ->select(
                DB::raw($this->getDateSelectExpression($groupBy) . ' as period'),
                DB::raw('SUM(monthly_rent) as total_revenue'),
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $labels = [];
        $data = [];

        foreach ($revenueData as $item) {
            $labels[] = $this->formatPeriodLabel($item->period, $groupBy);
            $data[] = (float) $item->total_revenue;
        }

        // If no data, show placeholder
        if (empty($labels)) {
            $labels = ['No Data'];
            $data = [0];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (Ksh)',
                    'data' => $data,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
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
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "Ksh " + value.toLocaleString(); }',
                    ],
                ],
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
        ];
    }

    protected function getFilteredQuery()
    {
        return $this->getFilteredLeaseQuery();
    }

    protected function getGroupingPeriod(): string
    {
        // Determine grouping based on selected date filter
        return match ($this->dateFilter) {
            'today', 'yesterday' => 'hour',
            'this_week', 'last_week' => 'day',
            'this_month', 'last_month' => 'day',
            'this_quarter', 'last_quarter' => 'week',
            'this_year', 'last_year' => 'month',
            'custom' => $this->determineCustomGrouping(),
            default => 'month', // All time defaults to monthly
        };
    }

    protected function determineCustomGrouping(): string
    {
        if (! $this->startDate || ! $this->endDate) {
            return 'month';
        }

        $start = \Carbon\Carbon::parse($this->startDate);
        $end = \Carbon\Carbon::parse($this->endDate);
        $daysDiff = $start->diffInDays($end);

        return match (true) {
            $daysDiff <= 1 => 'hour',
            $daysDiff <= 31 => 'day',
            $daysDiff <= 90 => 'week',
            default => 'month',
        };
    }

    protected function getDateSelectExpression(string $groupBy): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            return match ($groupBy) {
                'hour' => "TO_CHAR(created_at, 'YYYY-MM-DD HH24:00:00')",
                'day' => 'DATE(created_at)',
                'week' => "TO_CHAR(created_at, 'IYYY-IW')",
                'month' => "TO_CHAR(created_at, 'YYYY-MM')",
                default => "TO_CHAR(created_at, 'YYYY-MM')",
            };
        }

        // MySQL
        return match ($groupBy) {
            'hour' => "DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00')",
            'day' => 'DATE(created_at)',
            'week' => "DATE_FORMAT(created_at, '%Y-%u')",
            'month' => "DATE_FORMAT(created_at, '%Y-%m')",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };
    }

    protected function formatPeriodLabel(string $period, string $groupBy): string
    {
        return match ($groupBy) {
            'hour' => \Carbon\Carbon::parse($period)->format('h A'),
            'day' => \Carbon\Carbon::parse($period)->format('M d'),
            'week' => 'Week ' . substr($period, -2),
            'month' => \Carbon\Carbon::parse($period . '-01')->format('M Y'),
            default => $period,
        };
    }
}
