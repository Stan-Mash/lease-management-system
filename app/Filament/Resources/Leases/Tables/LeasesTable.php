<?php

namespace App\Filament\Resources\Leases\Tables;

use App\Models\Lease;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class LeasesTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->query(Lease::query())
            ->columns([
                TextColumn::make('reference_number')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('tenant.names')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('property.property_name')
                    ->label('Property')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('unit.unit_number')
                    ->label('Unit')
                    ->sortable(),

                TextColumn::make('zone')
                    ->label('Zone')
                    ->badge()
                    ->sortable(),

                TextColumn::make('lease_type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'commercial' => 'Commercial',
                        'residential_micro' => 'Res. Micro',
                        'residential_major' => 'Res. Major',
                        'landlord_provided' => 'Landlord',
                        default => $state,
                    })
                    ->toggleable(),

                TextColumn::make('signing_mode')
                    ->label('Mode')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'digital' => 'info',
                        'physical' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('workflow_state')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'approved', 'printed' => 'info',
                        'sent_digital', 'checked_out', 'pending_tenant_signature' => 'warning',
                        'tenant_signed' => 'success',
                        'with_lawyer' => 'purple',
                        'pending_upload', 'pending_deposit' => 'orange',
                        'active' => 'success',
                        'expired', 'terminated' => 'danger',
                        'archived', 'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('monthly_rent')
                    ->label('Rent')
                    ->money('KES')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('deposit_verified')
                    ->label('Deposit')
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('start_date')
                    ->label('Start')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('workflow_state')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'approved' => 'Approved',
                        'sent_digital' => 'Sent Digital',
                        'checked_out' => 'Checked Out',
                        'pending_tenant_signature' => 'Pending Signature',
                        'tenant_signed' => 'Tenant Signed',
                        'with_lawyer' => 'With Lawyer',
                        'pending_upload' => 'Pending Upload',
                        'pending_deposit' => 'Pending Deposit',
                        'active' => 'Active',
                        'expired' => 'Expired',
                        'terminated' => 'Terminated',
                        'archived' => 'Archived',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('zone')
                    ->label('Zone')
                    ->options([
                        'A' => 'Zone A',
                        'B' => 'Zone B',
                        'C' => 'Zone C',
                        'D' => 'Zone D',
                        'E' => 'Zone E',
                        'F' => 'Zone F',
                        'G' => 'Zone G',
                    ]),

                SelectFilter::make('source')
                    ->label('Source')
                    ->options([
                        'chabrin' => 'Chabrin',
                        'landlord' => 'Landlord',
                    ]),

                SelectFilter::make('signing_mode')
                    ->label('Signing Mode')
                    ->options([
                        'digital' => 'Digital',
                        'physical' => 'Physical',
                    ]),

                SelectFilter::make('lease_type')
                    ->label('Type')
                    ->options([
                        'commercial' => 'Commercial',
                        'residential_micro' => 'Residential Micro',
                        'residential_major' => 'Residential Major',
                        'landlord_provided' => 'Landlord Provided',
                    ]),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Created From'),
                        DatePicker::make('created_until')
                            ->label('Created Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'Created from ' . \Carbon\Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Created until ' . \Carbon\Carbon::parse($data['created_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),

                Filter::make('start_date')
                    ->form([
                        DatePicker::make('start_from')
                            ->label('Start Date From'),
                        DatePicker::make('start_until')
                            ->label('Start Date Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['start_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['start_from'] ?? null) {
                            $indicators[] = 'Start from ' . \Carbon\Carbon::parse($data['start_from'])->toFormattedDateString();
                        }
                        if ($data['start_until'] ?? null) {
                            $indicators[] = 'Start until ' . \Carbon\Carbon::parse($data['start_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),

                Filter::make('end_date')
                    ->form([
                        DatePicker::make('end_from')
                            ->label('End Date From'),
                        DatePicker::make('end_until')
                            ->label('End Date Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['end_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('end_date', '>=', $date),
                            )
                            ->when(
                                $data['end_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('end_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['end_from'] ?? null) {
                            $indicators[] = 'End from ' . \Carbon\Carbon::parse($data['end_from'])->toFormattedDateString();
                        }
                        if ($data['end_until'] ?? null) {
                            $indicators[] = 'End until ' . \Carbon\Carbon::parse($data['end_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ])
            /** @phpstan-ignore-next-line */
            /** @noinspection PhpDeprecationInspection */
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->visible(
                        fn (Lease $record): bool => in_array($record->workflow_state, ['approved', 'printed']),
                    ),
            ])
            /** @phpstan-ignore-next-line */
            /** @noinspection PhpDeprecationInspection */
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteAction::make()
                        ->visible(fn (): bool => Gate::allows('delete_lease')),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
