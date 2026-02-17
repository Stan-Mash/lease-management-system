<?php

namespace App\Filament\Widgets;

use App\Models\Lease;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UpcomingExpirationsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Upcoming Lease Expirations (90 Days)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Lease::accessibleByUser(auth()->user())
                    ->where('workflow_state', 'active')
                    ->whereBetween('end_date', [now(), now()->addDays(90)])
                    ->orderBy('end_date')
                    ->with(['tenant', 'unit.property', 'assignedZone']),
            )
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Reference')
                    ->searchable(),

                Tables\Columns\TextColumn::make('tenant.names')
                    ->label('Tenant')
                    ->searchable(),

                Tables\Columns\TextColumn::make('unit.property.property_name')
                    ->label('Property'),

                Tables\Columns\TextColumn::make('unit.unit_number')
                    ->label('Unit'),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Expires')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('days_until_expiry')
                    ->label('Days Left')
                    ->state(fn (Lease $record) => now()->diffInDays($record->end_date, false))
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state <= 30 => 'danger',
                        $state <= 60 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('monthly_rent')
                    ->label('Rent')
                    ->money('KES')
                    ->sortable(),

                Tables\Columns\TextColumn::make('assignedZone.name')
                    ->label('Zone')
                    ->badge(),
            ])
            ->actions([
                Action::make('view')
                    ->url(fn (Lease $record) => route('filament.admin.resources.leases.view', $record))
                    ->icon('heroicon-o-eye'),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('view_leases') ?? false;
    }
}
