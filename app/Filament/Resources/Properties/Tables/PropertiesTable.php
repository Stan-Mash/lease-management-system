<?php

namespace App\Filament\Resources\Properties\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PropertiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date_time')
                    ->label('Date/Time')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('reference_number')
                    ->label('Ref No.')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('name')
                    ->label('Property Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('client.names')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('lr_number')
                    ->label('LR Number')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('acquisition_date')
                    ->label('Acquisition Date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('fieldOfficer.name')
                    ->label('Field Officer')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('zoneManager.name')
                    ->label('Zone Manager')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('units_count')
                    ->counts('units')
                    ->label('Units')
                    ->sortable()
                    ->toggleable(),

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
