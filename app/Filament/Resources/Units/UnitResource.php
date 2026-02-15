<?php

namespace App\Filament\Resources\Units;

use App\Filament\Resources\Units\Pages\CreateUnit;
use App\Filament\Resources\Units\Pages\EditUnit;
use App\Filament\Resources\Units\Pages\ListUnits;
use App\Filament\Resources\Units\Pages\ViewUnit;
use App\Filament\Resources\Units\Schemas\UnitForm;
use App\Filament\Resources\Units\Schemas\UnitInfolist;
use App\Filament\Resources\Units\Tables\UnitsTable;
use App\Models\Unit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|UnitEnum|null $navigationGroup = 'Directory';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'unit_number';

    // Enable global search
    public static function getGloballySearchableAttributes(): array
    {
        return ['unit_number', 'unit_code', 'property.name', 'property.property_code'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return ($record->unit_code ?? $record->unit_number) . ' - ' . ($record->property?->name ?? 'Unknown Property');
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Unit Code' => $record->unit_code ?? 'N/A',
            'Property' => $record->property?->name,
            'Type' => ucfirst($record->type ?? 'N/A'),
            'Rent' => 'Ksh ' . number_format($record->market_rent ?? 0),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with('property');
    }

    public static function form(Schema $schema): Schema
    {
        return UnitForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UnitInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UnitsTable::configure($table);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['property', 'fieldOfficer', 'zoneManager', 'zone']);

        $user = auth()->user();
        if (!$user) {
            return $query;
        }

        // Field officers: only see units in properties allocated to them
        if ($user->role === 'field_officer') {
            return $query->whereHas('property', fn ($q) => $q->where('field_officer_id', $user->id));
        }

        // Zone-restricted users: filter by zone
        if ($user->hasZoneRestriction() && $user->zone_id) {
            return $query->where('zone_id', $user->zone_id);
        }

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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUnits::route('/'),
            'create' => CreateUnit::route('/create'),
            'view' => ViewUnit::route('/{record}'),
            'edit' => EditUnit::route('/{record}/edit'),
        ];
    }
}
