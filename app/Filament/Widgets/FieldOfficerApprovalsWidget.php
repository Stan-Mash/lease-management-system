<?php

namespace App\Filament\Widgets;

use App\Models\Lease;
use App\Models\LeaseApproval;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class FieldOfficerApprovalsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // Total pending approvals across all landlords
        $totalPending = Lease::where('workflow_state', 'pending_landlord_approval')
            ->whereNotNull('landlord_id')
            ->count();

        // Approvals waiting more than 24 hours
        $overdue = Lease::where('workflow_state', 'pending_landlord_approval')
            ->whereNotNull('landlord_id')
            ->where('created_at', '<', now()->subHours(24))
            ->count();

        // Approved in last 7 days
        $recentlyApproved = LeaseApproval::where('decision', 'approved')
            ->where('reviewed_at', '>=', now()->subDays(7))
            ->count();

        // Rejected in last 7 days
        $recentlyRejected = LeaseApproval::where('decision', 'rejected')
            ->where('reviewed_at', '>=', now()->subDays(7))
            ->count();

        // Average approval time (in hours)
        $avgApprovalTime = LeaseApproval::where('decision', 'approved')
            ->whereNotNull('reviewed_at')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, reviewed_at)) as avg_hours')
            ->value('avg_hours');

        return [
            Stat::make('Total Pending Approvals', $totalPending)
                ->description('Across all landlords')
                ->descriptionIcon('heroicon-m-clock')
                ->color($totalPending > 10 ? 'danger' : ($totalPending > 5 ? 'warning' : 'success'))
                ->url(route('filament.admin.resources.leases.index', [
                    'tableFilters' => ['workflow_state' => 'pending_landlord_approval']
                ])),

            Stat::make('Overdue (>24hrs)', $overdue)
                ->description('Require follow-up')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overdue > 0 ? 'danger' : 'success')
                ->chart([7, 5, 10, 8, 12, $overdue])
                ->url(route('filament.admin.resources.leases.index', [
                    'tableFilters' => ['workflow_state' => 'pending_landlord_approval']
                ])),

            Stat::make('Approved (7 days)', $recentlyApproved)
                ->description('Last 7 days')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([3, 5, 7, 4, 8, 6, $recentlyApproved]),

            Stat::make('Rejected (7 days)', $recentlyRejected)
                ->description('Need revision')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->chart([1, 2, 0, 3, 1, 2, $recentlyRejected]),

            Stat::make('Avg. Approval Time', $avgApprovalTime ? round($avgApprovalTime, 1) . ' hrs' : 'N/A')
                ->description('Last 30 days')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($avgApprovalTime && $avgApprovalTime > 48 ? 'warning' : 'success'),
        ];
    }
}
