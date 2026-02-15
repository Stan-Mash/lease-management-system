<?php

namespace App\Filament\Resources\Units\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;

class UnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date_created')
                    ->label('Date Created')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                TextColumn::make('unit_code')
                    ->label('Unit Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('unit_number')
                    ->label('Unit No.')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('property.name')
                    ->label('Property')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('property.property_code')
                    ->label('Property Code')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? 'N/A'))
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'available' => 'success',
                        'occupied' => 'primary',
                        'maintenance' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('market_rent')
                    ->label('Market Rent')
                    ->money('KES')
                    ->sortable(),

                TextColumn::make('deposit_required')
                    ->label('Deposit')
                    ->money('KES')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('fieldOfficer.name')
                    ->label('Field Officer')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('zoneManager.name')
                    ->label('Zone Manager')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('System Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'available' => 'Available',
                        'occupied' => 'Occupied',
                        'maintenance' => 'Maintenance',
                    ])
                    ->multiple(),

                SelectFilter::make('type')
                    ->label('Type')
                    ->options(fn () => \App\Models\Unit::query()
                        ->whereNotNull('type')
                        ->distinct()
                        ->pluck('type', 'type')
                        ->map(fn ($v) => ucfirst($v))
                        ->toArray()
                    ),

                SelectFilter::make('property_id')
                    ->label('Property')
                    ->relationship('property', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('field_officer_id')
                    ->label('Field Officer')
                    ->relationship('fieldOfficer', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('zone_manager_id')
                    ->label('Zone Manager')
                    ->relationship('zoneManager', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('date_created')
                    ->form([
                        DatePicker::make('from')
                            ->label('Date Created From'),
                        DatePicker::make('until')
                            ->label('Date Created Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, $date) => $q->whereDate('date_created', '>=', $date))
                            ->when($data['until'], fn (Builder $q, $date) => $q->whereDate('date_created', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'From ' . \Carbon\Carbon::parse($data['from'])->format('d/m/Y');
                        }
                        if ($data['until'] ?? null) {
                            $indicators['until'] = 'Until ' . \Carbon\Carbon::parse($data['until'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),
            ])
            ->filtersFormColumns(3)
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }
}
