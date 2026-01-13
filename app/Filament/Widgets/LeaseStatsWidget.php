<?php

namespace App\Filament\Widgets;

use App\Models\Lease;
use App\Models\Property;
use App\Models\Unit;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class LeaseStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Total Active Leases
        $activeLeases = Lease::where('workflow_state', 'active')->count();
        $lastMonthActive = Lease::where('workflow_state', 'active')
            ->where('created_at', '<', now()->subMonth())
            ->count();
        $activeChange = $lastMonthActive > 0
            ? round((($activeLeases - $lastMonthActive) / $lastMonthActive) * 100, 1)
            : 0;

        // Total Revenue (from active leases)
        $totalRevenue = Lease::where('workflow_state', 'active')
            ->sum('monthly_rent');
        $lastMonthRevenue = Lease::where('workflow_state', 'active')
            ->where('updated_at', '<', now()->subMonth())
            ->sum('monthly_rent');
        $revenueChange = $lastMonthRevenue > 0
            ? round((($totalRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;

        // Occupancy Rate
        $totalUnits = Unit::count();
        $occupiedUnits = Unit::where('status', 'occupied')->count();
        $occupancyRate = $totalUnits > 0
            ? round(($occupiedUnits / $totalUnits) * 100, 1)
            : 0;

        // Pending Actions
        $pendingLeases = Lease::whereIn('workflow_state', [
            'draft',
            'pending_landlord_approval',
            'pending_tenant_signature',
            'pending_deposit',
        ])->count();

        // Expiring Soon (next 30 days)
        $expiringSoon = Lease::where('workflow_state', 'active')
            ->whereBetween('end_date', [now(), now()->addDays(30)])
            ->count();

        return [
            Stat::make('Active Leases', $activeLeases)
                ->description($activeChange >= 0 ? "+{$activeChange}% from last month" : "{$activeChange}% from last month")
                ->descriptionIcon($activeChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($activeChange >= 0 ? 'success' : 'danger')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

            Stat::make('Monthly Revenue', '$' . number_format($totalRevenue, 2))
                ->description($revenueChange >= 0 ? "+{$revenueChange}% from last month" : "{$revenueChange}% from last month")
                ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueChange >= 0 ? 'success' : 'danger')
                ->chart([3, 5, 8, 6, 7, 8, 9, 10]),

            Stat::make('Occupancy Rate', $occupancyRate . '%')
                ->description("{$occupiedUnits} of {$totalUnits} units occupied")
                ->descriptionIcon('heroicon-m-home')
                ->color($occupancyRate >= 90 ? 'success' : ($occupancyRate >= 70 ? 'warning' : 'danger'))
                ->chart([60, 65, 70, 75, 80, 85, 90, $occupancyRate]),

            Stat::make('Pending Actions', $pendingLeases)
                ->description('Leases requiring attention')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingLeases > 10 ? 'warning' : 'gray')
                ->url(route('filament.admin.resources.leases.index', ['tableFilters[workflow_state][values][0]' => 'draft'])),

            Stat::make('Expiring Soon', $expiringSoon)
                ->description('Leases expiring in 30 days')
                ->descriptionIcon('heroicon-m-calendar')
                ->color($expiringSoon > 5 ? 'warning' : 'success'),
        ];
    }
}
