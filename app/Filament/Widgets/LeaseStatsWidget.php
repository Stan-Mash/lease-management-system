<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDateFiltering;
use App\Filament\Widgets\Concerns\HasLeaseQueryFiltering;
use App\Models\Lease;
use App\Models\Unit;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class LeaseStatsWidget extends StatsOverviewWidget
{
    use HasDateFiltering;
    use HasLeaseQueryFiltering;

    protected static ?int $sort = 1;

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

    protected function getStats(): array
    {
        // Base query with zone/FO/date filtering
        $baseQuery = $this->getFilteredQuery();

        // Consolidate 6 separate lease queries into a single aggregation query.
        // This reduces dashboard DB roundtrips from ~8 to ~2.
        $oneMonthAgo = now()->subMonth();
        $todayStr = now()->toDateString();
        $thirtyDaysFromNow = now()->addDays(30)->toDateString();

        $leaseStats = (clone $baseQuery)
            ->selectRaw("
                COUNT(CASE WHEN workflow_state = 'active' THEN 1 END) as active_count,
                COUNT(CASE WHEN workflow_state = 'active' AND created_at < ? THEN 1 END) as last_month_active,
                COALESCE(SUM(CASE WHEN workflow_state = 'active' THEN monthly_rent END), 0) as total_revenue,
                COALESCE(SUM(CASE WHEN workflow_state = 'active' AND updated_at < ? THEN monthly_rent END), 0) as last_month_revenue,
                COUNT(CASE WHEN workflow_state IN ('draft','pending_landlord_approval','pending_tenant_signature','pending_deposit') THEN 1 END) as pending_count,
                COUNT(CASE WHEN workflow_state = 'active' AND end_date >= ? AND end_date <= ? THEN 1 END) as expiring_soon
            ", [$oneMonthAgo, $oneMonthAgo, $todayStr, $thirtyDaysFromNow])
            ->first();

        $activeLeases = (int) $leaseStats->active_count;
        $lastMonthActive = (int) $leaseStats->last_month_active;
        $activeChange = $lastMonthActive > 0
            ? round((($activeLeases - $lastMonthActive) / $lastMonthActive) * 100, 1)
            : 0;

        $totalRevenue = (float) $leaseStats->total_revenue;
        $lastMonthRevenue = (float) $leaseStats->last_month_revenue;
        $revenueChange = $lastMonthRevenue > 0
            ? round((($totalRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;

        $pendingLeases = (int) $leaseStats->pending_count;
        $expiringSoon = (int) $leaseStats->expiring_soon;

        // Occupancy Rate — consolidated 2 unit queries into 1 (zone-specific if zoneId is set)
        $unitQuery = Unit::query();
        if ($this->zoneId) {
            $unitQuery->whereHas('property', fn ($q) => $q->where('zone_id', $this->zoneId));
        }
        $unitStats = (clone $unitQuery)
            ->selectRaw("
                COUNT(*) as total_units,
                SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied_units
            ")
            ->first();
        $totalUnits = (int) $unitStats->total_units;
        $occupiedUnits = (int) $unitStats->occupied_units;
        $occupancyRate = $totalUnits > 0
            ? round(($occupiedUnits / $totalUnits) * 100, 1)
            : 0;

        return [
            Stat::make('Active Leases', $activeLeases)
                ->description($activeChange >= 0 ? "+{$activeChange}% from last month" : "{$activeChange}% from last month")
                ->descriptionIcon($activeChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($activeChange >= 0 ? 'success' : 'danger')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3])
                ->url($this->getFilteredUrl(['workflow_state' => 'active'])),

            Stat::make('Monthly Revenue', '$' . number_format($totalRevenue, 2))
                ->description($revenueChange >= 0 ? "+{$revenueChange}% from last month" : "{$revenueChange}% from last month")
                ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueChange >= 0 ? 'success' : 'danger')
                ->chart([3, 5, 8, 6, 7, 8, 9, 10])
                ->url($this->getFilteredUrl(['workflow_state' => 'active'])),

            Stat::make('Occupancy Rate', $occupancyRate . '%')
                ->description("{$occupiedUnits} of {$totalUnits} units occupied")
                ->descriptionIcon('heroicon-m-home')
                ->color($occupancyRate >= 90 ? 'success' : ($occupancyRate >= 70 ? 'warning' : 'danger'))
                ->chart([60, 65, 70, 75, 80, 85, 90, $occupancyRate]),

            Stat::make('Pending Actions', $pendingLeases)
                ->description('Leases requiring attention')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingLeases > 10 ? 'warning' : 'gray')
                ->url($this->getFilteredUrl([
                    'workflow_state' => ['draft', 'pending_landlord_approval', 'pending_tenant_signature', 'pending_deposit'],
                ])),

            Stat::make('Expiring Soon', $expiringSoon)
                ->description('Leases expiring in 30 days')
                ->descriptionIcon('heroicon-m-calendar')
                ->color($expiringSoon > 5 ? 'warning' : 'success')
                ->url($this->getFilteredUrl(['workflow_state' => 'active', 'expiring_soon' => true])),
        ];
    }

    protected function getFilteredQuery()
    {
        return $this->getFilteredLeaseQuery();
    }

    protected function getFilteredUrl(array $additionalFilters = []): string
    {
        $filters = [];

        // Add workflow state filter
        if (isset($additionalFilters['workflow_state'])) {
            $states = is_array($additionalFilters['workflow_state'])
                ? $additionalFilters['workflow_state']
                : [$additionalFilters['workflow_state']];

            foreach ($states as $index => $state) {
                $filters["tableFilters[workflow_state][values][{$index}]"] = $state;
            }
        }

        // Add zone filter
        if ($this->zoneId) {
            $filters['tableFilters[zone_id][values][0]'] = $this->zoneId;
        }

        // Add field officer filter
        if ($this->fieldOfficerId) {
            $filters['tableFilters[assigned_field_officer_id][value]'] = $this->fieldOfficerId;
        }

        // Add date filters
        if ($this->dateFilter) {
            $filters['dateFilter'] = $this->dateFilter;
            if ($this->startDate) {
                $filters['startDate'] = $this->startDate;
            }
            if ($this->endDate) {
                $filters['endDate'] = $this->endDate;
            }
        }

        return route('filament.admin.resources.leases.index', $filters);
    }
}
