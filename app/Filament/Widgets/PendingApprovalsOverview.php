<?php

namespace App\Filament\Widgets;

use App\Models\Lease;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PendingApprovalsOverview extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $pendingCount = Lease::whereHas('approvals', function ($query) {
            $query->whereNull('decision');
        })->count();

        $approvedToday = Lease::whereHas('approvals', function ($query) {
            $query->where('decision', 'approved')
                ->whereDate('reviewed_at', today());
        })->count();

        $rejectedToday = Lease::whereHas('approvals', function ($query) {
            $query->where('decision', 'rejected')
                ->whereDate('reviewed_at', today());
        })->count();

        return [
            Stat::make('Pending Approvals', $pendingCount)
                ->description('Leases awaiting landlord approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url(route('filament.admin.resources.leases.index', [
                    'tableFilters' => ['workflow_state' => 'pending_landlord_approval']
                ])),

            Stat::make('Approved Today', $approvedToday)
                ->description('Leases approved in the last 24 hours')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Rejected Today', $rejectedToday)
                ->description('Leases rejected in the last 24 hours')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }
}
