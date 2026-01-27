<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DateRangeFilterWidget;
use App\Filament\Widgets\LeaseStatsWidget;
use App\Filament\Widgets\LeaseStatusChartWidget;
use App\Models\User;
use Filament\Pages\Dashboard as BaseDashboard;

class FieldOfficerDashboard extends BaseDashboard
{
    protected static string $routePath = 'field-officer-dashboard';
    protected static bool $shouldRegisterNavigation = false;

    public ?int $fieldOfficerId = null;
    public ?User $fieldOfficer = null;

    public function mount(): void
    {
        $this->fieldOfficerId = request()->query('user');
        $currentUser = auth()->user();

        if ($this->fieldOfficerId) {
            $this->fieldOfficer = User::find($this->fieldOfficerId);

            if (!$this->fieldOfficer) {
                abort(404, 'Field officer not found.');
            }

            // Authorization check
            if ($currentUser->isSuperAdmin() || $currentUser->isAdmin()) {
                // Admins can view any field officer
            } elseif ($currentUser->isZoneManager()) {
                // Zone managers can only view field officers in their zone
                if ($this->fieldOfficer->zone_id !== $currentUser->zone_id) {
                    abort(403, 'You do not have access to this field officer.');
                }
            } elseif ($currentUser->isFieldOfficer()) {
                // Field officers can only view their own dashboard
                if ($this->fieldOfficerId !== $currentUser->id) {
                    abort(403, 'You can only view your own dashboard.');
                }
            } else {
                abort(403, 'Unauthorized access.');
            }
        } else {
            // If no user specified, default to current user if they're a field officer
            if ($currentUser->isFieldOfficer()) {
                $this->fieldOfficerId = $currentUser->id;
                $this->fieldOfficer = $currentUser;
            }
        }
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user->isSuperAdmin()
            || $user->isAdmin()
            || $user->isZoneManager()
            || $user->isFieldOfficer();
    }

    public function getTitle(): string
    {
        if ($this->fieldOfficer) {
            if (auth()->user()->id === $this->fieldOfficer->id) {
                return 'My Assigned Leases';
            }
            return $this->fieldOfficer->name . ' - Assigned Leases';
        }
        return 'Field Officer Dashboard';
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
            LeaseStatusChartWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return 2;
    }

    public function getVisibleWidgets(): array
    {
        $widgets = $this->filterVisibleWidgets($this->getWidgets());

        // Pass field officer ID to widgets that need it
        foreach ($widgets as $widget) {
            if (method_exists($widget, 'fieldOfficerId')) {
                $widget->fieldOfficerId = $this->fieldOfficerId;
            }
        }

        return $widgets;
    }
}
