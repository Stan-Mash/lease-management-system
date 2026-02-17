<?php

namespace App\Filament\Resources\Leases;

use App\Filament\Resources\Leases\Pages\CreateLease;
use App\Filament\Resources\Leases\Pages\EditLease;
use App\Filament\Resources\Leases\Pages\ListLeases;
use App\Filament\Resources\Leases\Pages\ViewLease;
use App\Filament\Resources\Leases\Schemas\LeaseForm;
use App\Filament\Resources\Leases\Schemas\LeaseInfolist;
use App\Models\Lease;
use App\Models\User;
use App\Models\Zone;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;
use UnitEnum;

class LeaseResource extends Resource
{
    protected static ?string $model = Lease::class;

    protected static ?string $recordTitleAttribute = 'reference_number';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Lease Agreements';

    protected static string|UnitEnum|null $navigationGroup = 'Lease Portfolio';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        // Cache count for 5 minutes to avoid running COUNT(*) on every page load
        $activeCount = cache()->remember(
            'lease_navigation_badge_count',
            now()->addMinutes(5),
            fn () => static::getModel()::where('workflow_state', 'active')->count(),
        );

        return $activeCount > 0 ? (string) $activeCount : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Active leases';
    }

    // Enable global search
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'reference_number',
            'lease_reference_number',
            'unit_code',
            'tenant.names',
            'tenant.national_id',
            'tenant.mobile_number',
            'property.name',
            'property.reference_number',
            'unit.unit_number',
            'landlord.name',
        ];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->reference_number . ' - ' . ($record->tenant?->names ?? 'Unknown Tenant');
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Tenant' => $record->tenant?->names ?? 'N/A',
            'Property' => $record->property?->name ?? 'N/A',
            'Unit' => $record->unit?->unit_number ?? 'N/A',
            'Status' => ucfirst(str_replace('_', ' ', $record->workflow_state)),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return LeaseForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LeaseInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date_created')
                    ->label('Date Created')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                TextColumn::make('reference_number')
                    ->label('Ref No.')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('lease_reference_number')
                    ->label('Lease Ref')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('unit_code')
                    ->label('Unit Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->toggleable(),

                TextColumn::make('tenant.names')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tenant.national_id')
                    ->label('National ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tenant.mobile_number')
                    ->label('Tenant Mobile')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('property.property_name')
                    ->label('Property')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('unit.unit_number')
                    ->label('Unit')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('landlord.name')
                    ->label('Landlord')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('assignedZone.name')
                    ->label('Zone')
                    ->badge()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('assignedFieldOfficer.name')
                    ->label('Field Officer')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('zoneManager.name')
                    ->label('Zone Manager')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('lease_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'commercial' => 'Commercial',
                        'residential_micro' => 'Residential (Micro)',
                        'residential_macro', 'residential_major' => 'Residential (Macro)',
                        default => ucwords(str_replace('_', ' ', $state)),
                    })
                    ->toggleable(),

                TextColumn::make('workflow_state')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'draft' => 'gray',
                        'pending_landlord_approval' => 'warning',
                        'approved' => 'info',
                        'active' => 'success',
                        'terminated', 'expired' => 'danger',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('monthly_rent')
                    ->money('KES')
                    ->label('Rent')
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label('Start')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('end_date')
                    ->label('End')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('source')
                    ->label('Source')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('System Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('workflow_state')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'pending_landlord_approval' => 'Pending Landlord Approval',
                        'approved' => 'Approved',
                        'sent_digital' => 'Sent (Digital)',
                        'pending_otp' => 'Pending OTP',
                        'tenant_signed' => 'Tenant Signed',
                        'active' => 'Active',
                        'terminated' => 'Terminated',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple()
                    ->preload(),

                SelectFilter::make('lease_type')
                    ->label('Lease Type')
                    ->options([
                        'commercial' => 'Commercial',
                        'residential_micro' => 'Residential (Micro)',
                        'residential_macro' => 'Residential (Macro)',
                        'residential_major' => 'Residential (Major/Legacy)',
                    ]),

                SelectFilter::make('zone_id')
                    ->label('Zone')
                    ->relationship('assignedZone', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('assigned_field_officer_id')
                    ->label('Field Officer')
                    ->relationship('assignedFieldOfficer', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('property_id')
                    ->label('Property')
                    ->relationship('property', 'property_name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('landlord_id')
                    ->label('Landlord')
                    ->relationship('landlord', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('zone_manager_id')
                    ->label('Zone Manager')
                    ->relationship('zoneManager', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('date_created')
                    ->form([
                        DatePicker::make('date_from')
                            ->label('Date Created From'),
                        DatePicker::make('date_until')
                            ->label('Date Created Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['date_from'], fn (Builder $q, $date) => $q->whereDate('date_created', '>=', $date))
                            ->when($data['date_until'], fn (Builder $q, $date) => $q->whereDate('date_created', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['date_from'] ?? null) {
                            $indicators['date_from'] = 'Created from ' . \Carbon\Carbon::parse($data['date_from'])->format('d/m/Y');
                        }
                        if ($data['date_until'] ?? null) {
                            $indicators['date_until'] = 'Created until ' . \Carbon\Carbon::parse($data['date_until'])->format('d/m/Y');
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
                            ->when($data['start_from'], fn (Builder $q, $date) => $q->whereDate('start_date', '>=', $date))
                            ->when($data['start_until'], fn (Builder $q, $date) => $q->whereDate('start_date', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['start_from'] ?? null) {
                            $indicators['start_from'] = 'Start from ' . \Carbon\Carbon::parse($data['start_from'])->format('d/m/Y');
                        }
                        if ($data['start_until'] ?? null) {
                            $indicators['start_until'] = 'Start until ' . \Carbon\Carbon::parse($data['start_until'])->format('d/m/Y');
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
                            ->when($data['end_from'], fn (Builder $q, $date) => $q->whereDate('end_date', '>=', $date))
                            ->when($data['end_until'], fn (Builder $q, $date) => $q->whereDate('end_date', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['end_from'] ?? null) {
                            $indicators['end_from'] = 'End from ' . \Carbon\Carbon::parse($data['end_from'])->format('d/m/Y');
                        }
                        if ($data['end_until'] ?? null) {
                            $indicators['end_until'] = 'End until ' . \Carbon\Carbon::parse($data['end_until'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),

                Filter::make('expiring_soon')
                    ->label('Expiring within 90 days')
                    ->toggle()
                    ->query(fn (Builder $query) => $query
                        ->where('workflow_state', 'active')
                        ->whereBetween('end_date', [now(), now()->addDays(90)])
                    ),
            ])
            ->filtersFormColumns(3)
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Lease $record) => route('lease.preview', $record))
                    ->openUrlInNewTab(),
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->url(fn (Lease $record) => route('lease.download', $record)),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }

    /**
     * Apply zone-based filtering and eager loading for performance.
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['tenant', 'property', 'unit', 'landlord', 'approvals', 'assignedZone', 'assignedFieldOfficer', 'zoneManager']);

        $user = auth()->user();
        if (!$user) {
            return $query;
        }

        // Field officers: only see leases assigned to them
        if ($user->role === 'field_officer') {
            return $query->where('assigned_field_officer_id', $user->id);
        }

        // Zone-restricted users: filter by zone
        if ($user->hasZoneRestriction() && $user->zone_id) {
            return $query->where('zone_id', $user->zone_id);
        }

        // Everyone else sees all
        return $query;
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        return $user && $user->role !== 'field_officer' && !in_array($user->role, ['auditor', 'internal_auditor']);
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        return $user && $user->role !== 'field_officer' && !in_array($user->role, ['auditor', 'internal_auditor']);
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['super_admin', 'admin', 'property_manager']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeases::route('/'),
            'create' => CreateLease::route('/create'),
            'view' => ViewLease::route('/{record}'),
            'edit' => EditLease::route('/{record}/edit'),
        ];
    }
}
