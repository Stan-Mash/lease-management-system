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
use Filament\Tables\Table;
use UnitEnum;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|UnitEnum|null $navigationGroup = 'Directory';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'unit_name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['unit_name', 'unit_number', 'unit_code', 'description'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->unit_name ?? $record->unit_code ?? 'Unknown Unit';
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Code' => $record->unit_code ?? 'N/A',
            'Number' => $record->unit_number ?? 'N/A',
            'Property' => $record->property?->property_name ?? 'N/A',
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

    public static function getRelations(): array
    {
        return [];
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
