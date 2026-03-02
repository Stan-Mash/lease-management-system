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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home-modern';

    protected static string|UnitEnum|null $navigationGroup = 'Directory';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'property_name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['property_name', 'reference_number', 'lr_number', 'description'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->property_name ?? 'Unknown Property';
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Ref' => $record->reference_number ?? 'N/A',
            'LR' => $record->lr_number ?? 'N/A',
            'Client' => $record->client?->names ?? 'N/A',
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // Eager-load all relationships rendered in PropertiesTable columns
        // (client.names, fieldOfficer.name, zoneManager.name) to prevent N+1 queries.
        return parent::getEloquentQuery()->with([
            'client:id,names',
            'fieldOfficer:id,name',
            'zoneManager:id,name',
        ]);
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with('client:id,names');
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

    public static function getRelations(): array
    {
        return [];
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
