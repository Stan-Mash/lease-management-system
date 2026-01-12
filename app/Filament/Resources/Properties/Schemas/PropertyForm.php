<?php

namespace App\Filament\Resources\Properties\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PropertyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('property_code')
                    ->required(),
                TextInput::make('zone')
                    ->required()
                    ->default('A'),
                TextInput::make('location'),
                TextInput::make('landlord_id')
                    ->required()
                    ->numeric(),
                TextInput::make('management_commission')
                    ->required()
                    ->numeric()
                    ->default(10),
            ]);
    }
}
