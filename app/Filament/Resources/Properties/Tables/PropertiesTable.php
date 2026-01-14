<?php

namespace App\Filament\Resources\Properties\Tables;

use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PropertiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('property_code')
                    ->searchable(),
                TextColumn::make('zone')
                    ->searchable(),
                TextColumn::make('location')
                    ->searchable(),
                TextColumn::make('landlord_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('management_commission')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('zone')
                    ->options([
                        'zone_a' => 'Zone A',
                        'zone_b' => 'Zone B',
                        'zone_c' => 'Zone C',
                    ]),
                SelectFilter::make('landlord')
                    ->relationship('landlord', 'name'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
