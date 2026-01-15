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
                    ->afterStateUpdated(function ($state, callable $set) {
                        $unit = Unit::with('property.landlord')->find($state);
                        if ($unit) {
                            $set('monthly_rent', $unit->market_rent ?? 0);
                            $set('property_id', $unit->property_id);
                            $set('landlord_id', $unit->property?->landlord_id);
                        }
                    }),

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

                // --- Guarantor Section ---
                Forms\Components\Toggle::make('requires_guarantor')
                    ->label('Requires Guarantor')
                    ->reactive()
                    ->default(false),

                Forms\Components\Repeater::make('guarantors')
                    ->relationship('guarantors')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->label('Full Name')
                            ->maxLength(200),

                        Forms\Components\TextInput::make('id_number')
                            ->required()
                            ->label('National ID')
                            ->maxLength(20),

                        Forms\Components\TextInput::make('phone')
                            ->required()
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(100),

                        Forms\Components\Select::make('relationship')
                            ->required()
                            ->options([
                                'Parent' => 'Parent',
                                'Spouse' => 'Spouse',
                                'Sibling' => 'Sibling',
                                'Employer' => 'Employer',
                                'Friend' => 'Friend',
                                'Other' => 'Other',
                            ]),

                        Forms\Components\TextInput::make('guarantee_amount')
                            ->numeric()
                            ->prefix('KES')
                            ->label('Guarantee Amount (optional - defaults to deposit)'),

                        Forms\Components\Toggle::make('signed')
                            ->label('Has Signed')
                            ->default(false),

                        Forms\Components\Textarea::make('notes')
                            ->label('Additional Notes')
                            ->rows(2),
                    ])
                    ->visible(fn ($get) => $get('requires_guarantor'))
                    ->collapsible()
                    ->defaultItems(0)
                    ->addActionLabel('Add Guarantor')
                    ->label('Guarantors'),
            ]);
    }
}
