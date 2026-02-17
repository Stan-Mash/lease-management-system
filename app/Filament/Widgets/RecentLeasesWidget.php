<?php

namespace App\Filament\Widgets;

use App\Models\Lease;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentLeasesWidget extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Recent Lease Activity';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Lease::accessibleByUser(auth()->user())
                    ->with(['tenant', 'property', 'unit'])
                    ->latest()
                    ->limit(10),
            )
            ->columns([
                TextColumn::make('serial_number')
                    ->label('Serial #')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->default('N/A'),

                TextColumn::make('reference_number')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('tenant.names')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('property.name')
                    ->label('Property')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('unit.unit_number')
                    ->label('Unit')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('monthly_rent')
                    ->label('Monthly Rent')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('workflow_state')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'draft' => 'gray',
                        'approved' => 'info',
                        'expired', 'terminated' => 'danger',
                        'pending_tenant_signature', 'pending_deposit' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
