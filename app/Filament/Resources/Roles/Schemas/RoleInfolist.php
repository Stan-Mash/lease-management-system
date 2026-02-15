<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

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
                            ->size('lg')
                            ->copyable()
                            ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),

                        TextEntry::make('guard_name')
                            ->label('Guard')
                            ->badge()
                            ->color('gray'),

                        TextEntry::make('color')
                            ->badge()
                            ->color(fn (string $state): string => $state)
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),

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

            Section::make('Spatie Permissions')
                ->schema([
                    TextEntry::make('spatie_permissions')
                        ->label('Assigned Permissions')
                        ->state(function ($record) {
                            $permissions = DB::table('role_has_permissions')
                                ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                                ->where('role_has_permissions.role_id', $record->id)
                                ->pluck('permissions.name')
                                ->sort()
                                ->toArray();

                            return ! empty($permissions) ? implode(', ', $permissions) : 'No permissions assigned';
                        })
                        ->placeholder('No permissions assigned'),
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
