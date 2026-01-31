<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class RoleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Role Overview')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('name')
                            ->weight('bold')
                            ->size('lg'),

                        TextEntry::make('key')
                            ->label('Role Key')
                            ->copyable()
                            ->fontFamily('mono')
                            ->color('gray'),

                        TextEntry::make('color')
                            ->badge()
                            ->color(fn (string $state): string => $state)
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                        TextEntry::make('description')
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('sort_order')
                            ->label('Display Order'),

                        IconEntry::make('is_system')
                            ->label('System Role')
                            ->boolean(),

                        IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                    ]),
                ]),

            Section::make('Permissions')
                ->schema([
                    TextEntry::make('permissions')
                        ->listWithLineBreaks()
                        ->bulleted()
                        ->placeholder('No permissions defined')
                        ->formatStateUsing(
                            fn (?array $state): ?string => $state && count($state) > 0
                                ? implode("\n", array_column($state, 'permission'))
                                : null,
                        ),
                ]),

            Section::make('Statistics')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('users_count')
                            ->label('Users with this Role')
                            ->state(fn ($record) => $record->users()->count())
                            ->badge()
                            ->color('info'),

                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),
                    ]),
                ]),
        ]);
    }
}
