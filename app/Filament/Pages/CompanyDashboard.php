<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DateRangeFilterWidget;
use App\Filament\Widgets\LeaseStatsWidget;
use App\Filament\Widgets\LeaseStatusChartWidget;
use App\Filament\Widgets\RevenueChartWidget;
use App\Filament\Widgets\ZonePerformanceWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class CompanyDashboard extends BaseDashboard
{
    protected static string $routePath = 'company-dashboard';

    public static function getNavigationLabel(): string
    {
        return 'Company Dashboard';
    }

    public function getTitle(): string
    {
        return 'Company-Wide Performance';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user->isSuperAdmin() || $user->isAdmin();
    }

    public function getWidgets(): array
    {
        return [
            DateRangeFilterWidget::class,
            LeaseStatsWidget::class,
            ZonePerformanceWidget::class,
            LeaseStatusChartWidget::class,
            RevenueChartWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }

    public function getVisibleWidgets(): array
    {
        return $this->filterVisibleWidgets($this->getWidgets());
    }
}
