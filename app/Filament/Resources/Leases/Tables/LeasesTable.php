<?php

namespace App\Filament\Resources\Leases\Tables;

use App\Models\Lease;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeasesTable
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Table::make()
                ->columns([
                    TextColumn::make('reference_number')
                        ->label('Reference')
                        ->searchable()
                        ->sortable()
                        ->copyable()
                        ->weight('bold'),

                    TextColumn::make('tenant.name')
                        ->label('Tenant')
                        ->searchable()
                        ->sortable(),

                    TextColumn::make('property.name')
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
                ])
                ->actions([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('print')
                        ->label('Print')
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->visible(fn (Lease $record): bool =>
                            in_array($record->workflow_state, ['approved', 'printed'])
                        ),
                ])
                ->bulkActions([
                    BulkActionGroup::make([
                        DeleteBulkAction::make()
                            ->visible(fn (): bool => auth()->user()->can('delete_lease')),
                    ]),
                ])
                ->defaultSort('created_at', 'desc')
                ->striped()
                ->paginated([10, 25, 50, 100])
        ]);
    }
}
