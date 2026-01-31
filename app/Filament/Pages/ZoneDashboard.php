<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DateRangeFilterWidget;
use App\Filament\Widgets\FieldOfficerPerformanceWidget;
use App\Filament\Widgets\LeaseStatsWidget;
use App\Filament\Widgets\LeaseStatusChartWidget;
use App\Filament\Widgets\RevenueChartWidget;
use App\Models\Zone;
use Filament\Pages\Dashboard as BaseDashboard;

class ZoneDashboard extends BaseDashboard
{
    public ?int $zoneId = null;

    public ?Zone $zone = null;

    protected static string $routePath = 'zone-dashboard';

    protected static bool $shouldRegisterNavigation = false;

    public function mount(): void
    {
        $this->zoneId = request()->query('zone');

        // Authorization check
        $user = auth()->user();

        if ($this->zoneId) {
            $this->zone = Zone::find($this->zoneId);

            if (! $this->zone) {
                abort(404, 'Zone not found.');
            }

            // Check if user can access this zone
            if (! $user->isSuperAdmin() && ! $user->isAdmin()) {
                if (! $user->canAccessZone($this->zoneId)) {
                    abort(403, 'You do not have access to this zone.');
                }
            }
        } else {
            // If no zone specified, redirect zone manager to their zone
            if ($user->isZoneManager() && $user->zone_id) {
                redirect()->route('filament.admin.pages.zone-dashboard', ['zone' => $user->zone_id]);
            }
        }
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user->isSuperAdmin() || $user->isAdmin() || $user->isZoneManager();
    }

    public function getTitle(): string
    {
        if ($this->zone) {
            return $this->zone->name . ' Performance';
        }

        return 'Zone Dashboard';
    }

    public function getHeading(): string
    {
        return $this->getTitle();
    }

    public function getWidgets(): array
    {
        return [
            DateRangeFilterWidget::class,
            LeaseStatsWidget::class,
            FieldOfficerPerformanceWidget::class,
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
        $widgets = $this->filterVisibleWidgets($this->getWidgets());

        // Pass zone ID to widgets that need it
        foreach ($widgets as $widget) {
            if (method_exists($widget, 'zoneId')) {
                $widget->zoneId = $this->zoneId;
            }
        }

        return $widgets;
    }
}
