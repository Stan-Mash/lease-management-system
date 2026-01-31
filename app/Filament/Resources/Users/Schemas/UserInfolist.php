<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Services\RoleService;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('User Overview')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('name')
                            ->weight('bold'),

                        TextEntry::make('email')
                            ->label('Email Address')
                            ->copyable(),

                        TextEntry::make('role')
                            ->badge()
                            ->color(fn (string $state): string => RoleService::getRoleColor($state))
                            ->formatStateUsing(fn (string $state): string => RoleService::getRoleName($state)),

                        TextEntry::make('phone')
                            ->placeholder('-')
                            ->copyable(),

                        TextEntry::make('department')
                            ->placeholder('-'),

                        IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                    ]),
                ]),

            Section::make('Profile')
                ->schema([
                    ImageEntry::make('avatar_path')
                        ->label('Avatar')
                        ->placeholder('-'),

                    TextEntry::make('bio')
                        ->placeholder('-')
                        ->columnSpanFull(),
                ]),

            Section::make('Activity')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('last_login_at')
                            ->dateTime()
                            ->placeholder('Never'),

                        TextEntry::make('created_at')
                            ->dateTime(),
                    ]),
                ]),
        ]);
    }
}
