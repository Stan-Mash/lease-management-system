<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDateFiltering;
use App\Models\Lease;
use App\Models\User;
use App\Models\Zone;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class ZonePerformanceWidget extends BaseWidget
{
    use HasDateFiltering;

    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Zone Performance';
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
        return auth()->user()->isSuperAdmin() || auth()->user()->isAdmin();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('name')
                    ->label('Zone Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->url(fn (Zone $record) => route('filament.admin.pages.zone-dashboard', ['zone' => $record->id]))
                    ->color('primary'),

                TextColumn::make('active_leases_count')
                    ->label('Active Leases')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                TextColumn::make('total_revenue')
                    ->label('Monthly Revenue')
                    ->money('KES')
                    ->sortable()
                    ->alignEnd()
                    ->weight('medium'),

                TextColumn::make('field_officers_count')
                    ->label('Field Officers')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                TextColumn::make('pending_leases_count')
                    ->label('Pending Actions')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(fn (int $state): string => $state > 5 ? 'warning' : 'gray'),

                TextColumn::make('occupancy_rate')
                    ->label('Occupancy')
                    ->sortable()
                    ->alignCenter()
                    ->suffix('%')
                    ->color(fn (string $state): string =>
                        ((float) $state >= 90) ? 'success' :
                        (((float) $state >= 70) ? 'warning' : 'danger')
                    ),

                TextColumn::make('zone_manager.name')
                    ->label('Zone Manager')
                    ->searchable()
                    ->toggleable(),
            ])
            ->defaultSort('active_leases_count', 'desc')
            ->paginated([10, 25, 50])
            ->poll(null);
    }

    protected function getTableQuery(): Builder
    {
        $dateColumn = 'created_at';

        return Zone::query()
            ->with(['zoneManager', 'fieldOfficers'])
            ->withCount([
                'leases as active_leases_count' => function ($query) use ($dateColumn) {
                    $query->where('workflow_state', 'active');
                    $this->applyDateFilter($query, $dateColumn);
                },
                'fieldOfficers as field_officers_count',
                'leases as pending_leases_count' => function ($query) use ($dateColumn) {
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
                'leases as total_revenue' => function ($query) use ($dateColumn) {
                    $query->where('workflow_state', 'active');
                    $this->applyDateFilter($query, $dateColumn);
                }
            ], 'monthly_rent')
            ->selectRaw('zones.*,
                CASE
                    WHEN (SELECT COUNT(*) FROM units WHERE units.property_id IN (SELECT id FROM properties WHERE properties.zone_id = zones.id)) = 0
                    THEN 0
                    ELSE ROUND(
                        (SELECT COUNT(*) FROM units WHERE units.status = \'occupied\' AND units.property_id IN (SELECT id FROM properties WHERE properties.zone_id = zones.id)) * 100.0 /
                        (SELECT COUNT(*) FROM units WHERE units.property_id IN (SELECT id FROM properties WHERE properties.zone_id = zones.id))
                    , 1)
                END as occupancy_rate
            ')
            ->where('is_active', true);
    }
}
