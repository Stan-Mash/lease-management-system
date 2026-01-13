<?php

namespace App\Filament\Resources\Leases\Schemas;

use Filament\Schemas\Schema;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\TextEntry;

class LeaseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Lease Overview')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('reference_number')
                            ->label('Reference Number')
                            ->weight('bold'),

                        TextEntry::make('workflow_state')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match (strtolower($state)) {
                                'draft' => 'gray',
                                'active' => 'success',
                                'terminated' => 'danger',
                                default => 'gray',
                            }),

                        TextEntry::make('lease_type')
                            ->label('Type'),
                    ]),
                ]),

            Section::make('Property & Tenant Details')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('tenant.full_name')->label('Tenant Name'),
                        TextEntry::make('unit.unit_number')->label('Unit Number'),
                        TextEntry::make('property.name')->label('Property'),
                        TextEntry::make('landlord.name')->label('Landlord'),
                    ]),
                ]),
        ]);
    }
}
