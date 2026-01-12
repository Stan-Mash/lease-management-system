<?php

namespace App\Filament\Resources\Units\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('property_id')
                    ->required()
                    ->numeric(),
                TextInput::make('unit_number')
                    ->required(),
                TextInput::make('type'),
                TextInput::make('market_rent')
                    ->required()
                    ->numeric(),
                TextInput::make('deposit_required')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('status')
                    ->required()
                    ->default('VACANT'),
            ]);
    }
}
