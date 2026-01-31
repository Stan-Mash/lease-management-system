<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class PendingApprovalsOverview extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $stats = DB::table('lease_approvals')
            ->selectRaw("
                COUNT(CASE WHEN decision IS NULL THEN 1 END) as pending,
                COUNT(CASE WHEN decision = 'approved' AND DATE(reviewed_at) = ? THEN 1 END) as approved_today,
                COUNT(CASE WHEN decision = 'rejected' AND DATE(reviewed_at) = ? THEN 1 END) as rejected_today
            ", [today(), today()])
            ->first();

        return [
            Stat::make('Pending Approvals', (int) ($stats->pending ?? 0))
                ->description('Leases awaiting landlord approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url(route('filament.admin.resources.leases.index', [
                    'tableFilters' => ['workflow_state' => 'pending_landlord_approval'],
                ])),

            Stat::make('Approved Today', (int) ($stats->approved_today ?? 0))
                ->description('Leases approved in the last 24 hours')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Rejected Today', (int) ($stats->rejected_today ?? 0))
                ->description('Leases rejected in the last 24 hours')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }
}
