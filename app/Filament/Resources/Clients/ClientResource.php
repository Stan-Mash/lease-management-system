<?php

namespace App\Filament\Resources\Clients;

use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Filament\Resources\Clients\Pages\EditClient;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Filament\Resources\Clients\Pages\ViewClient;
use App\Filament\Resources\Clients\Schemas\ClientForm;
use App\Filament\Resources\Clients\Schemas\ClientInfolist;
use App\Filament\Resources\Clients\Tables\ClientsTable;
use App\Models\Client;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static string|UnitEnum|null $navigationGroup = 'Directory';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'names';

    public static function getGloballySearchableAttributes(): array
    {
        return ['names', 'second_name', 'last_name', 'mobile_number', 'email_address', 'reference_number', 'national_id'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->names ?? 'Unknown Client';
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Mobile' => $record->mobile_number ?? 'N/A',
            'Email' => $record->email_address ?? 'N/A',
            'Ref' => $record->reference_number ?? 'N/A',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return ClientForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClientInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClientsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClients::route('/'),
            'create' => CreateClient::route('/create'),
            'view' => ViewClient::route('/{record}'),
            'edit' => EditClient::route('/{record}/edit'),
        ];
    }
}
