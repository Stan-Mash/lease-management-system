<?php

namespace App\Filament\Widgets;

use App\Models\RentEscalation;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UpcomingRentEscalationsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Upcoming Rent Escalations (30 Days)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                RentEscalation::query()
                    ->where('applied', false)
                    ->whereBetween('effective_date', [now(), now()->addDays(30)])
                    ->whereHas('lease', function ($query) {
                        $user = auth()->user();
                        if ($user->isFieldOfficer()) {
                            $query->where('assigned_field_officer_id', $user->id);
                        } elseif (method_exists($user, 'hasZoneRestriction') && $user->hasZoneRestriction()) {
                            $query->where('zone_id', $user->zone_id);
                        }
                    })
                    ->orderBy('effective_date')
                    ->with(['lease.tenant', 'lease.unit.property'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('lease.reference_number')
                    ->label('Lease')
                    ->searchable(),

                Tables\Columns\TextColumn::make('lease.tenant.name')
                    ->label('Tenant')
                    ->searchable(),

                Tables\Columns\TextColumn::make('lease.unit.property.name')
                    ->label('Property'),

                Tables\Columns\TextColumn::make('effective_date')
                    ->label('Effective Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('previous_rent')
                    ->label('Current Rent')
                    ->money('KES'),

                Tables\Columns\TextColumn::make('new_rent')
                    ->label('New Rent')
                    ->money('KES'),

                Tables\Columns\TextColumn::make('increase_percentage')
                    ->label('Increase')
                    ->suffix('%')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\IconColumn::make('tenant_notified')
                    ->label('Notified')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\Action::make('notify')
                    ->label('Send Notification')
                    ->icon('heroicon-o-bell')
                    ->requiresConfirmation()
                    ->visible(fn (RentEscalation $record) => !$record->tenant_notified)
                    ->action(function (RentEscalation $record) {
                        $record->markTenantNotified();
                    }),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('view_leases') ?? false;
    }
}
