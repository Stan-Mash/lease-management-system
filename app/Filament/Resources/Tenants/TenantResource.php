<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenants;

use App\Filament\Resources\Tenants\Pages\CreateTenant;
use App\Filament\Resources\Tenants\Pages\EditTenant;
use App\Filament\Resources\Tenants\Pages\ListTenants;
use App\Filament\Resources\Tenants\Pages\ViewTenant;
use App\Filament\Resources\Tenants\RelationManagers\EventsRelationManager;
use App\Filament\Resources\Tenants\Schemas\TenantForm;
use App\Filament\Resources\Tenants\Schemas\TenantInfolist;
use App\Filament\Resources\Tenants\Tables\TenantsTable;
use App\Models\Tenant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|UnitEnum|null $navigationGroup = 'Directory';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'full_name';

    // Enable global search
    public static function getGloballySearchableAttributes(): array
    {
        return ['full_name', 'id_number', 'phone_number', 'email'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->full_name ?? 'Unknown Tenant';
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'ID' => $record->id_number,
            'Phone' => $record->phone_number,
            'Email' => $record->email,
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

    public static function table(Table $table): Table
    {
        return TenantsTable::configure($table);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['fieldOfficer', 'zoneManager', 'zone'])
            ->withCount('leases');

        $user = auth()->user();
        if (!$user) {
            return $query;
        }

        // Field officers: only see tenants with leases assigned to them
        if ($user->role === 'field_officer') {
            return $query->whereHas('leases', fn ($q) => $q->where('assigned_field_officer_id', $user->id));
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
            EventsRelationManager::class,
        ];
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
