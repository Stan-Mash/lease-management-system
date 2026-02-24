<?php

namespace App\Filament\Resources\Leases\Tables;

use App\Enums\LeaseWorkflowState;
use App\Models\Lease;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
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
                    // Bulk countersign — for processing many tenant-signed leases at once
                    // (e.g. after a new property opening where 20+ tenants all signed)
                    BulkAction::make('bulkCountersignActivate')
                        ->label('Countersign & Activate Selected')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (): bool => Gate::allows('manage_leases') || auth()->user()?->canManageLeases())
                        ->modalHeading('Bulk Countersign & Activate Leases')
                        ->modalDescription('Only leases in "Tenant Signed" status will be activated. Leases in any other status are automatically skipped. Each tenant will receive their copy by email.')
                        ->modalSubmitActionLabel('Countersign & Activate All')
                        /** @phpstan-ignore-next-line */
                        ->schema([
                            TextInput::make('countersigned_by')
                                ->label('Your Full Name')
                                ->default(fn () => Auth::user()?->name ?? '')
                                ->required()
                                ->maxLength(255)
                                ->helperText('Recorded as the countersignature on all selected leases.'),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $countersignedBy = $data['countersigned_by'];
                            $activated       = 0;
                            $skipped         = 0;

                            foreach ($records as $lease) {
                                // Only process leases that are actually in tenant_signed state
                                if ($lease->workflow_state !== 'tenant_signed') {
                                    $skipped++;
                                    continue;
                                }

                                try {
                                    $lease->update([
                                        'countersigned_by'  => $countersignedBy,
                                        'countersigned_at'  => now(),
                                        'countersign_notes' => 'Bulk countersigned via lease list.',
                                    ]);
                                    $lease->transitionTo(LeaseWorkflowState::ACTIVE);
                                    $activated++;
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::warning('Bulk countersign failed for lease', [
                                        'lease_id' => $lease->id,
                                        'error'    => $e->getMessage(),
                                    ]);
                                    $skipped++;
                                }
                            }

                            $body = "Activated: {$activated} lease(s).";
                            if ($skipped > 0) {
                                $body .= " Skipped: {$skipped} (not in Tenant Signed state or error).";
                            }

                            Notification::make()
                                ->success()
                                ->title("Bulk Activation Complete")
                                ->body($body)
                                ->persistent()
                                ->send();
                        }),

                    DeleteAction::make()
                        ->visible(fn (): bool => Gate::allows('delete_lease')),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
