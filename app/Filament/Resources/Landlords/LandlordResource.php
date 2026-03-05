<?php

namespace App\Filament\Resources\Landlords;

use App\Filament\Resources\Landlords\Pages\CreateLandlord;
use App\Filament\Resources\Landlords\Pages\EditLandlord;
use App\Filament\Resources\Landlords\Pages\ListLandlords;
use App\Filament\Resources\Landlords\Pages\ViewLandlord;
use App\Models\Landlord;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class LandlordResource extends Resource
{
    protected static ?string $model = Landlord::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|UnitEnum|null $navigationGroup = 'Directory';

    // After Tenants (1), before Properties (3)
    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'names';

    public static function getGloballySearchableAttributes(): array
    {
        return ['names', 'mobile_number', 'email_address', 'reference_number', 'national_id', 'lan_id'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->names ?? 'Unknown Landlord';
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
        return $schema
            ->components([
                TextInput::make('lan_id')
                    ->label('Landlord Code')
                    ->maxLength(50)
                    ->helperText('Optional external code, e.g. LAN-000123'),

                TextInput::make('names')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('mobile_number')
                    ->label('Mobile')
                    ->tel()
                    ->required()
                    ->maxLength(50),

                TextInput::make('email_address')
                    ->label('Email')
                    ->email()
                    ->maxLength(255),

                TextInput::make('national_id')
                    ->label('National ID')
                    ->maxLength(50),

                TextInput::make('pin_number')
                    ->label('KRA PIN')
                    ->maxLength(50),

                TextInput::make('po_box')
                    ->label('PO Box (optional)')
                    ->placeholder('e.g. 1234')
                    ->maxLength(100)
                    ->helperText('Leave blank if not applicable — the lease will leave this space empty.'),

                TextInput::make('bank_name')
                    ->label('Bank')
                    ->maxLength(255),

                TextInput::make('account_number')
                    ->label('Account Number')
                    ->maxLength(255),

                Select::make('zone_id')
                    ->label('Zone')
                    ->relationship('zone', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')
                    ->label('Ref No.')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('lan_id')
                    ->label('Code')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('names')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('mobile_number')
                    ->label('Mobile')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('email_address')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('zone.name')
                    ->label('Zone')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('System Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['properties', 'leases']);
    }

    public static function getRelations(): array
    {
        return [];
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

