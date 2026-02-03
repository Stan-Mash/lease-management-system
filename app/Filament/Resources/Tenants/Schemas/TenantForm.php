<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenants\Schemas;

use App\Enums\PreferredLanguage;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('full_name')
                    ->required(),
                TextInput::make('id_number'),
                TextInput::make('phone_number')
                    ->tel()
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('notification_preference')
                    ->required()
                    ->default('SMS'),
                Select::make('preferred_language')
                    ->label('SMS Language')
                    ->options(PreferredLanguage::options())
                    ->default(PreferredLanguage::ENGLISH->value)
                    ->native(false)
                    ->helperText('Language for SMS notifications'),
                TextInput::make('kra_pin'),
                TextInput::make('occupation'),
                TextInput::make('employer_name'),
                TextInput::make('next_of_kin_name'),
                TextInput::make('next_of_kin_phone')
                    ->tel(),
            ]);
    }
}
