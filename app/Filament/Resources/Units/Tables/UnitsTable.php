<?php

namespace App\Filament\Resources\Units\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date_time')
                    ->label('Date/Time')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('unit_code')
                    ->label('Unit Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('unit_name')
                    ->label('Unit Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('unit_number')
                    ->label('Unit No.')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('property.name')
                    ->label('Property')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('client.names')
                    ->label('Client')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('rent_amount')
                    ->label('Rent')
                    ->money('KES')
                    ->sortable(),

                IconColumn::make('vat_able')
                    ->label('VAT')
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('initial_water_meter_reading')
                    ->label('Water Meter')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('System Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
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
