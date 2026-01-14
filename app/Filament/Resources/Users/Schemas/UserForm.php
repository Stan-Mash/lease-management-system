<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('User Information')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->required()
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

                        Select::make('role')
                            ->options([
                                'super_admin' => 'Super Admin',
                                'admin' => 'Admin',
                                'manager' => 'Manager',
                                'staff' => 'Staff',
                            ])
                            ->required()
                            ->default('staff'),

                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(20),

                        TextInput::make('department')
                            ->maxLength(100),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
                ]),

            Section::make('Additional Information')
                ->schema([
                    FileUpload::make('avatar_path')
                        ->label('Avatar')
                        ->image()
                        ->directory('avatars')
                        ->columnSpanFull(),

                    Textarea::make('bio')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
