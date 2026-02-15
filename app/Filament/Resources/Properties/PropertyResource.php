<?php

namespace App\Filament\Resources\Properties;

use App\Filament\Resources\Properties\Pages\CreateProperty;
use App\Filament\Resources\Properties\Pages\EditProperty;
use App\Filament\Resources\Properties\Pages\ListProperties;
use App\Filament\Resources\Properties\Pages\ViewProperty;
use App\Filament\Resources\Properties\Schemas\PropertyForm;
use App\Filament\Resources\Properties\Schemas\PropertyInfolist;
use App\Filament\Resources\Properties\Tables\PropertiesTable;
use App\Models\Property;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home-modern';

    protected static string|UnitEnum|null $navigationGroup = 'Directory';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    // Enable global search
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'property_code', 'location', 'landlord.name'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->name . ' (' . ($record->property_code ?? 'N/A') . ')';
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Landlord' => $record->landlord?->name ?? 'N/A',
            'Location' => $record->location ?? 'N/A',
            'Units' => $record->units_count ?? $record->units()->count(),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with('landlord')->withCount('units');
    }

    public static function form(Schema $schema): Schema
    {
        return PropertyForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PropertyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PropertiesTable::configure($table);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['landlord', 'fieldOfficer', 'zoneManager'])
            ->withCount('units');

        $user = auth()->user();
        if (!$user) {
            return $query;
        }

        // Field officers: only see properties allocated to them
        if ($user->role === 'field_officer') {
            return $query->where('field_officer_id', $user->id);
        }

        // Zone-restricted users (zone_manager, auditor, senior_field_officer): filter by zone
        if ($user->hasZoneRestriction() && $user->zone_id) {
            return $query->where('zone_id', $user->zone_id);
        }

        // Everyone else (super_admin, admin, PM, asst PM, internal_auditor): see all
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
            'index' => ListProperties::route('/'),
            'create' => CreateProperty::route('/create'),
            'view' => ViewProperty::route('/{record}'),
            'edit' => EditProperty::route('/{record}/edit'),
        ];
    }
}
