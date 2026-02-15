<?php

namespace App\Filament\Resources\Tenants\Tables;

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

class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date_created')
                    ->label('Date Created')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                TextColumn::make('full_name')
                    ->label('Full Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('id_number')
                    ->label('ID Number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone_number')
                    ->label('Phone')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('leases.property.name')
                    ->label('Property')
                    ->getStateUsing(fn ($record) => $record->leases()->latest()->first()?->property?->name ?? 'N/A')
                    ->toggleable(),

                TextColumn::make('occupation')
                    ->label('Occupation')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('employer_name')
                    ->label('Employer')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('notification_preference')
                    ->label('Notification')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? 'N/A'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('kra_pin')
                    ->label('KRA PIN')
                    ->searchable()
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
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('next_of_kin_name')
                    ->label('Next of Kin')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('next_of_kin_phone')
                    ->label('NOK Phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('leases_count')
                    ->counts('leases')
                    ->label('Leases')
                    ->sortable()
                    ->badge()
                    ->color('primary')
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
                SelectFilter::make('notification_preference')
                    ->label('Notification')
                    ->options([
                        'email' => 'Email',
                        'sms' => 'SMS',
                        'both' => 'Both',
                    ]),

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

                Filter::make('has_leases')
                    ->label('Has Active Leases')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->whereHas('leases', fn ($q) => $q->where('workflow_state', 'active'))),
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
