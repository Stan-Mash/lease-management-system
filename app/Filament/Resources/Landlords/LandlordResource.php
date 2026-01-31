<?php

namespace App\Filament\Resources\Landlords;

use App\Filament\Resources\Landlords\Pages\CreateLandlord;
use App\Filament\Resources\Landlords\Pages\EditLandlord;
use App\Filament\Resources\Landlords\Pages\ListLandlords;
use App\Filament\Resources\Landlords\Pages\ViewLandlord;
use App\Filament\Resources\Landlords\Schemas\LandlordForm;
use App\Filament\Resources\Landlords\Schemas\LandlordInfolist;
use App\Filament\Resources\Landlords\Tables\LandlordsTable;
use App\Models\Landlord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LandlordResource extends Resource
{
    protected static ?string $model = Landlord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    // Enable global search
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone', 'id_number'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->name;
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Phone' => $record->phone ?? 'N/A',
            'Email' => $record->email ?? 'N/A',
            'Properties' => $record->properties_count ?? $record->properties()->count(),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getGlobalSearchEloquentQuery()->withCount('properties');
    }

    public static function form(Schema $schema): Schema
    {
        return LandlordForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LandlordInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LandlordsTable::configure($table);
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
            'index' => ListLandlords::route('/'),
            'create' => CreateLandlord::route('/create'),
            'view' => ViewLandlord::route('/{record}'),
            'edit' => EditLandlord::route('/{record}/edit'),
        ];
    }
}
