<?php

namespace App\Filament\Resources\Leases\Schemas;

use App\Models\Tenant;
use App\Models\Unit;
use Filament\Forms;
use Filament\Schemas\Schema;

class LeaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // --- Lease Details ---
                // reference_number is auto-generated on save — hidden from the form
                Forms\Components\Hidden::make('reference_number'),

                // workflow_state is set to 'draft' on create — hidden from the form
                Forms\Components\Hidden::make('workflow_state')->default('draft'),

                Forms\Components\Select::make('source')
                    ->options([
                        'chabrin_issued' => 'Chabrin Generated',
                        'landlord_provided' => 'Landlord Provided',
                    ])
                    ->default('chabrin_issued')
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (callable $set) => $set('lease_type', null)),

                Forms\Components\Select::make('lease_type')
                    ->options(function ($get) {
                        $source = $get('source') ?? 'chabrin_issued';
                        if ($source === 'landlord_provided') {
                            return [
                                'residential' => 'Residential',
                                'commercial' => 'Commercial',
                            ];
                        }

                        return [
                            'residential_major' => 'Residential (Major)',
                            'residential_micro' => 'Residential (Micro)',
                            'commercial' => 'Commercial',
                        ];
                    })
                    ->default('residential_major')
                    ->required()
                    ->live(),

                Forms\Components\Select::make('lease_template_id')
                    ->label('Template')
                    ->options(function ($get) {
                        $leaseType = $get('lease_type');
                        $query = \App\Models\LeaseTemplate::where('is_active', true);
                        if ($leaseType) {
                            $query->where('template_type', $leaseType);
                        }

                        return $query->orderBy('name')->pluck('name', 'id');
                    })
                    ->searchable()
                    ->placeholder('Default template for this lease type')
                    ->helperText('Optional — leave blank to use the system default template')
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $template = \App\Models\LeaseTemplate::find($state);
                            if ($template) {
                                $set('template_version_used', $template->version_number);
                            }
                        }
                    }),

                Forms\Components\Hidden::make('template_version_used'),

                Forms\Components\Select::make('signing_mode')
                    ->label('Signing Method')
                    ->options([
                        'digital' => 'Digital Signing (Email/SMS Link)',
                        'physical' => 'Physical Signing (Field Officer)',
                    ])
                    ->default('digital')
                    ->required()
                    ->helperText('Digital: Tenant signs online via email link. Physical: Field officer delivers printed document.'),

                // --- Property & Tenant ---
                Forms\Components\Select::make('tenant_id')
                    ->label('Tenant')
                    ->options(
                        Tenant::orderBy('names')
                            ->get()
                            ->mapWithKeys(fn ($t) => [$t->id => "{$t->names} — {$t->mobile_number}"]),
                    )
                    ->searchable()
                    ->required(),

                // Smart Unit Search
                Forms\Components\Select::make('unit_id')
                    ->label('Unit')
                    ->options(Unit::pluck('unit_number', 'id'))
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $unit = Unit::with('property.client')->find($state);
                        if ($unit) {
                            $set('monthly_rent', $unit->rent_amount ?? 0);
                            $set('property_id', $unit->property_id);
                            $set('client_id', $unit->property?->client_id);
                        }
                    }),

                // Hidden fields
                Forms\Components\Hidden::make('property_id'),
                Forms\Components\Hidden::make('client_id'),
                Forms\Components\Hidden::make('zone')->default('A'),

                // --- Financials ---
                Forms\Components\TextInput::make('monthly_rent')
                    ->numeric()
                    ->prefix('Ksh')
                    ->required(),

                Forms\Components\TextInput::make('deposit_amount')
                    ->numeric()
                    ->prefix('Ksh')
                    ->required(),

                Forms\Components\DatePicker::make('start_date')
                    ->required(),

                Forms\Components\DatePicker::make('end_date'),

                // --- Guarantor Section ---
                Forms\Components\Toggle::make('requires_guarantor')
                    ->label('Requires Guarantor')
                    ->live()
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
                            ->prefix('Ksh')
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
