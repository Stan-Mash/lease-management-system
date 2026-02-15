<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Models\Role;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Role Information')
                ->description('Basic information about this role')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->regex('/^[a-z0-9_]+$/')
                            ->disabled(fn ($context, $record) => $context === 'edit' && $record?->is_system)
                            ->helperText('Unique identifier used in code (lowercase, underscores only)')
                            ->dehydrated(),

                        TextInput::make('guard_name')
                            ->default('web')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Guard name for this role'),

                        Select::make('color')
                            ->options(Role::getAvailableColors())
                            ->required()
                            ->default('gray')
                            ->helperText('Badge color shown in the interface')
                            ->searchable()
                            ->native(false),

                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Order in which roles are displayed (lower = first)'),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive roles cannot be assigned to users'),

                        Toggle::make('is_system')
                            ->label('System Role')
                            ->default(false)
                            ->disabled(fn ($context) => $context === 'edit')
                            ->helperText('System roles cannot be deleted')
                            ->dehydrated()
                            ->visible(fn () => auth()->user()?->role === 'super_admin'),
                    ]),
                ]),

            Section::make('Permissions')
                ->description('Define what this role can do')
                ->schema([
                    Repeater::make('permissions')
                        ->schema([
                            TextInput::make('permission')
                                ->label('Permission Key')
                                ->required()
                                ->placeholder('e.g., view_any_lease, create_lease')
                                ->helperText('Permission identifier (use snake_case)'),
                        ])
                        ->columns(1)
                        ->addActionLabel('Add Permission')
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['permission'] ?? null)
                        ->helperText('Add granular permissions for this role')
                        ->default([]),
                ])
                ->collapsed(),
        ]);
    }
}
