<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDateFiltering;
use App\Models\Lease;
use App\Models\Property;
use App\Models\Unit;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class LeaseStatsWidget extends StatsOverviewWidget
{
    use HasDateFiltering;

    protected static ?int $sort = 1;

    public ?int $zoneId = null;
    public ?int $fieldOfficerId = null;

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

        // Total Active Leases
        $activeLeases = (clone $baseQuery)->where('workflow_state', 'active')->count();
        $lastMonthActive = (clone $baseQuery)
            ->where('workflow_state', 'active')
            ->where('created_at', '<', now()->subMonth())
            ->count();
        $activeChange = $lastMonthActive > 0
            ? round((($activeLeases - $lastMonthActive) / $lastMonthActive) * 100, 1)
            : 0;

        // Total Revenue (from active leases)
        $totalRevenue = (clone $baseQuery)->where('workflow_state', 'active')->sum('monthly_rent');
        $lastMonthRevenue = (clone $baseQuery)
            ->where('workflow_state', 'active')
            ->where('updated_at', '<', now()->subMonth())
            ->sum('monthly_rent');
        $revenueChange = $lastMonthRevenue > 0
            ? round((($totalRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;

        // Occupancy Rate (zone-specific if zoneId is set)
        $unitQuery = Unit::query();
        if ($this->zoneId) {
            $unitQuery->whereHas('property', fn($q) => $q->where('zone_id', $this->zoneId));
        }
        $totalUnits = (clone $unitQuery)->count();
        $occupiedUnits = (clone $unitQuery)->where('status', 'occupied')->count();
        $occupancyRate = $totalUnits > 0
            ? round(($occupiedUnits / $totalUnits) * 100, 1)
            : 0;

        // Pending Actions
        $pendingLeases = (clone $baseQuery)->whereIn('workflow_state', [
            'draft',
            'pending_landlord_approval',
            'pending_tenant_signature',
            'pending_deposit',
        ])->count();

        // Expiring Soon (next 30 days)
        $expiringSoon = (clone $baseQuery)
            ->where('workflow_state', 'active')
            ->whereBetween('end_date', [now(), now()->addDays(30)])
            ->count();

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
                    'workflow_state' => ['draft', 'pending_landlord_approval', 'pending_tenant_signature', 'pending_deposit']
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
        $query = Lease::accessibleByUser(auth()->user());

        // Apply zone filter
        if ($this->zoneId) {
            $query->where('zone_id', $this->zoneId);
        } elseif (auth()->user()->hasZoneRestriction()) {
            $query->where('zone_id', auth()->user()->zone_id);
        }

        // Apply field officer filter
        if ($this->fieldOfficerId) {
            $query->where('assigned_field_officer_id', $this->fieldOfficerId);
        } elseif (auth()->user()->isFieldOfficer()) {
            $query->where('assigned_field_officer_id', auth()->user()->id);
        }

        // Apply date filter
        $query = $this->applyDateFilter($query);

        return $query;
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
