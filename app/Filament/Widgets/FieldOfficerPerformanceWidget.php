<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDateFiltering;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;

class FieldOfficerPerformanceWidget extends BaseWidget
{
    use HasDateFiltering;

    public ?int $zoneId = null;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Field Officer Performance';

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

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user->isSuperAdmin() || $user->isAdmin() || $user->isZoneManager();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('name')
                    ->label('Field Officer')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->url(fn (User $record) => route('filament.admin.pages.field-officer-dashboard', ['user' => $record->id]))
                    ->color('primary'),

                TextColumn::make('zone.name')
                    ->label('Zone')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->visible(fn () => auth()->user()->isSuperAdmin() || auth()->user()->isAdmin()),

                TextColumn::make('assigned_leases_count')
                    ->label('Assigned Leases')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                TextColumn::make('active_leases_count')
                    ->label('Active Leases')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                TextColumn::make('completed_this_month_count')
                    ->label('Completed This Month')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                TextColumn::make('pending_leases_count')
                    ->label('Pending Actions')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(fn (int $state): string => $state > 3 ? 'warning' : 'gray'),

                TextColumn::make('total_revenue')
                    ->label('Monthly Revenue')
                    ->money('KES')
                    ->sortable()
                    ->alignEnd()
                    ->weight('medium'),
            ])
            ->defaultSort('active_leases_count', 'desc')
            ->paginated([10, 25, 50])
            ->poll(null);
    }

    protected function getTableQuery(): Builder
    {
        $dateColumn = 'created_at';

        // Cache the list of matching field officer IDs so the withCount
        // aggregation subqueries only execute once per 5-minute window.
        $cacheKey = 'widget:fo_performance:'
            . ($this->zoneId ?? auth()->id()) . ':'
            . ($this->dateFilter ?? 'none') . ':'
            . ($this->startDate ?? '') . ':'
            . ($this->endDate ?? '');

        $officerIds = Cache::remember($cacheKey . ':ids', now()->addMinutes(5), function () {
            $q = User::query()->where('role', 'field_officer');

            if ($this->zoneId) {
                $q->where('zone_id', $this->zoneId);
            } elseif (auth()->user()->hasZoneRestriction()) {
                $q->where('zone_id', auth()->user()->zone_id);
            }

            return $q->pluck('id');
        });

        $query = User::query()
            ->whereIn('id', $officerIds)
            ->with(['zone']);

        return $query->withCount([
            'assignedLeases as assigned_leases_count' => function ($query) use ($dateColumn) {
                $this->applyDateFilter($query, $dateColumn);
            },
            'assignedLeases as active_leases_count' => function ($query) use ($dateColumn) {
                $query->where('workflow_state', 'active');
                $this->applyDateFilter($query, $dateColumn);
            },
            'assignedLeases as completed_this_month_count' => function ($query) {
                $query->where('workflow_state', 'active')
                    ->whereYear('start_date', now()->year)
                    ->whereMonth('start_date', now()->month);
            },
            'assignedLeases as pending_leases_count' => function ($query) use ($dateColumn) {
                $query->whereIn('workflow_state', [
                    'draft',
                    'pending_landlord_approval',
                    'pending_tenant_signature',
                    'pending_deposit',
                ]);
                $this->applyDateFilter($query, $dateColumn);
            },
        ])
            ->withSum([
                'assignedLeases as total_revenue' => function ($query) use ($dateColumn) {
                    $query->where('workflow_state', 'active');
                    $this->applyDateFilter($query, $dateColumn);
                },
            ], 'monthly_rent');
    }
}
