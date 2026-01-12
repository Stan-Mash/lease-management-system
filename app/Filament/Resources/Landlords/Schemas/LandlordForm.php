<?php

namespace App\Filament\Resources\Landlords\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LandlordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('landlord_code'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('id_number'),
                TextInput::make('kra_pin'),
                TextInput::make('bank_name'),
                TextInput::make('account_number'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
