<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
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
                            ->color(fn (string $state): string => match ($state) {
                                'super_admin' => 'danger',
                                'admin' => 'warning',
                                'manager' => 'info',
                                'staff' => 'success',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'super_admin' => 'Super Admin',
                                'admin' => 'Admin',
                                'manager' => 'Manager',
                                'staff' => 'Staff',
                                default => ucfirst($state),
                            }),

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
