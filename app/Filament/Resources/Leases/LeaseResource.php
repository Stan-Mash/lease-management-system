<?php

namespace App\Filament\Resources\Leases;

use App\Filament\Resources\Leases\Pages\CreateLease;
use App\Filament\Resources\Leases\Pages\EditLease;
use App\Filament\Resources\Leases\Pages\ListLeases;
use App\Filament\Resources\Leases\Pages\ViewLease;
use App\Filament\Resources\Leases\Schemas\LeaseForm;
use App\Filament\Resources\Leases\Schemas\LeaseInfolist;
use App\Models\Lease;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;

class LeaseResource extends Resource
{
    protected static ?string $model = Lease::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-document-text';
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
                TextColumn::make('reference_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tenant.full_name')
                    ->label('Tenant'),
                TextColumn::make('property.name')
                    ->label('Property'),
                TextColumn::make('unit.unit_number')
                    ->label('Unit'),
                TextColumn::make('lease_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'commercial' => 'Commercial',
                        'residential_micro' => 'Residential (Micro)',
                        'residential_major' => 'Residential (Major)',
                        default => $state,
                    }),
                TextColumn::make('workflow_state')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'draft' => 'gray',
                        'pending_landlord_approval' => 'warning',
                        'approved' => 'info',
                        'active' => 'success',
                        'terminated', 'expired' => 'danger',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('approval_status')
                    ->label('Approval')
                    ->badge()
                    ->state(function ($record) {
                        if (!$record->landlord_id) return null;
                        if ($record->hasBeenApproved()) return 'Approved';
                        if ($record->hasBeenRejected()) return 'Rejected';
                        if ($record->hasPendingApproval()) return 'Pending';
                        return null;
                    })
                    ->color(function ($record) {
                        if ($record->hasBeenApproved()) return 'success';
                        if ($record->hasBeenRejected()) return 'danger';
                        if ($record->hasPendingApproval()) return 'warning';
                        return 'gray';
                    })
                    ->visible(fn ($record) => $record !== null && $record->landlord_id !== null),
                TextColumn::make('monthly_rent')
                    ->money('KES')
                    ->label('Rent'),
            ])
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
            ]);
    }

    /**
     * Apply zone-based filtering for field officers and zone managers.
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && $user->hasZoneRestriction() && $user->zone_id) {
            $query->where('zone_id', $user->zone_id);
        }

        return $query;
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
