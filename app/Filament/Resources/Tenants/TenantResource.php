<?php

namespace App\Filament\Resources\Tenants;

use App\Filament\Resources\Tenants\Pages\CreateTenant;
use App\Filament\Resources\Tenants\Pages\EditTenant;
use App\Filament\Resources\Tenants\Pages\ListTenants;
use App\Filament\Resources\Tenants\Pages\ViewTenant;
use App\Filament\Resources\Tenants\Schemas\TenantForm;
use App\Filament\Resources\Tenants\Schemas\TenantInfolist;
use App\Filament\Resources\Tenants\Tables\TenantsTable;
use App\Models\Tenant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|UnitEnum|null $navigationGroup = 'Directory';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'names';

    public static function getGloballySearchableAttributes(): array
    {
        return ['names', 'second_name', 'last_name', 'mobile_number', 'email_address', 'reference_number', 'national_id'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->names ?? 'Unknown Tenant';
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
        return TenantForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TenantInfolist::configure($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        // Tenant table columns are all direct fields — no relationships are displayed.
        // This override ensures the base query is always scoped correctly and
        // provides a hook for future eager loading if relations are added to the table.
        return parent::getEloquentQuery();
    }

    public static function table(Table $table): Table
    {
        return TenantsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTenants::route('/'),
            'create' => CreateTenant::route('/create'),
            'view' => ViewTenant::route('/{record}'),
            'edit' => EditTenant::route('/{record}/edit'),
        ];
    }
}
