<?php

namespace App\Filament\Resources\Leases\Schemas;

use App\Models\Unit;
use Filament\Forms;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class LeaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // --- Lease Details ---
                Forms\Components\TextInput::make('reference_number')
                    ->default('LSE-' . strtoupper(Str::random(10)))
                    ->required()
                    ->readOnly(),

                Forms\Components\Select::make('workflow_state')
                    ->options([
                        'DRAFT' => 'Draft',
                        'ACTIVE' => 'Active',
                        'TERMINATED' => 'Terminated',
                    ])
                    ->default('DRAFT')
                    ->required(),

                Forms\Components\Select::make('source')
                    ->options([
                        'chabrin' => 'Chabrin Generated',
                        'landlord' => 'Landlord Provided',
                    ])
                    ->default('chabrin')
                    ->required(),

                Forms\Components\Select::make('lease_type')
                    ->options([
                        'residential_major' => 'Residential Major',
                        'residential_micro' => 'Residential Micro',
                        'commercial' => 'Commercial',
                    ])
                    ->required(),

                // --- Property & Tenant ---
                Forms\Components\Select::make('tenant_id')
                    ->relationship('tenant', 'full_name')
                    ->searchable()
                    ->preload()
                    ->required(),

                // Smart Unit Search
                Forms\Components\Select::make('unit_id')
                    ->label('Unit')
                    ->options(Unit::where('status', 'VACANT')->pluck('unit_number', 'id'))
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) =>
                        $set('monthly_rent', Unit::find($state)?->market_rent ?? 0)
                    ),

                // Hidden fields
                Forms\Components\Hidden::make('property_id'),
                Forms\Components\Hidden::make('landlord_id'),
                Forms\Components\Hidden::make('zone')->default('A'),

                // --- Financials ---
                Forms\Components\TextInput::make('monthly_rent')
                    ->numeric()
                    ->prefix('KES')
                    ->required(),

                Forms\Components\TextInput::make('deposit_amount')
                    ->numeric()
                    ->prefix('KES')
                    ->required(),

                Forms\Components\DatePicker::make('start_date')
                    ->required(),

                Forms\Components\DatePicker::make('end_date'),
            ]);
    }
}
