<?php

namespace App\Filament\Resources\Zones;

use App\Filament\Resources\Zones\Pages\CreateZone;
use App\Filament\Resources\Zones\Pages\EditZone;
use App\Filament\Resources\Zones\Pages\ListZones;
use App\Filament\Resources\Zones\Pages\ViewZone;
use App\Models\Zone;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ZoneResource extends Resource
{
    protected static ?string $model = Zone::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static string|UnitEnum|null $navigationGroup = 'Directory';

    // Appear after Units / Users
    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'code', 'description'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->name ?? 'Unknown Zone';
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Code' => $record->code ?? 'N/A',
            'Manager' => $record->zoneManager?->name ?? 'N/A',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('code')
                    ->maxLength(50)
                    ->helperText('Short code, e.g. A, B, CBD'),

                Textarea::make('description')
                    ->rows(3),

                TextInput::make('zone_manager_id')
                    ->numeric()
                    ->helperText('Optional: internal ID of the zone manager'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Zone')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('code')
                    ->label('Code')
                    ->sortable(),

                TextColumn::make('zoneManager.name')
                    ->label('Manager')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('field_officer_count')
                    ->label('Field Officers')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('active_lease_count')
                    ->label('Active Leases')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('name', 'asc')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['zoneManager']);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListZones::route('/'),
            'create' => CreateZone::route('/create'),
            'view' => ViewZone::route('/{record}'),
            'edit' => EditZone::route('/{record}/edit'),
        ];
    }
}

