<?php

namespace App\Filament\Resources\Clients\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClientsTable
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

                TextColumn::make('names')
                    ->label('Names')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('second_name')
                    ->label('Second Name')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('last_name')
                    ->label('Last Name')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('mobile_number')
                    ->label('Mobile')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('email_address')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('national_id')
                    ->label('National ID')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('pin_number')
                    ->label('PIN')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('vat_number')
                    ->label('VAT')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('account_name')
                    ->label('Account Name')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('account_number')
                    ->label('Account No.')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('rent_amount')
                    ->label('Rent Amount')
                    ->money('KES')
                    ->sortable()
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
