<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),

            TextInput::make('username')
                ->maxLength(255),

            TextInput::make('email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            TextInput::make('password')
                ->password()
                ->required(fn ($context) => $context === 'create')
                ->dehydrateStateUsing(fn ($state) => $state ? Hash::make($state) : null)
                ->dehydrated(fn ($state) => filled($state))
                ->maxLength(255),

            Toggle::make('block')
                ->label('Blocked'),

            Toggle::make('sendEmail')
                ->label('Send Email'),

            TextInput::make('activation'),

            Textarea::make('params'),

            TextInput::make('resetCount')
                ->numeric()
                ->default(0),

            TextInput::make('otpKey'),

            Toggle::make('requireReset')
                ->label('Require Password Reset'),
        ]);
    }
}
