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
                        'approved' => 'info',
                        'active' => 'success',
                        'terminated', 'expired' => 'danger',
                        default => 'gray',
                    }),
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
