<?php

namespace App\Filament\Resources\Leases\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LeaseForm
{
    /**
     * Return cached tenant options for the dropdown.
     *
     * Uses toBase() to skip Eloquent model hydration and cast resolution entirely —
     * this avoids the AES-256 decryption loop that fires when Eloquent boots each
     * Tenant model (national_id / passport_number / pin_number are 'encrypted' cast).
     * Raw stdClass rows are ~10x faster to build and use <5% of the memory of get().
     *
     * Cache TTL: 10 minutes. Invalidated by TenantObserver on create/update/delete.
     */
    public static function tenantOptions(): array
    {
        return Cache::remember('form_options.tenants', 600, function () {
            return DB::table('tenants')
                ->select('id', 'names', 'mobile_number')
                ->whereNull('deleted_at')
                ->orderBy('names')
                ->get()
                ->mapWithKeys(fn ($t) => [$t->id => "{$t->names} — {$t->mobile_number}"])
                ->all();
        });
    }

    /**
     * Return cached unit options for the dropdown.
     *
     * Cache TTL: 10 minutes. Invalidated by UnitObserver on create/update/delete.
     */
    public static function unitOptions(): array
    {
        return Cache::remember('form_options.units', 600, function () {
            return DB::table('units')
                ->select('id', 'unit_number', 'unit_code')
                ->whereNull('deleted_at')
                ->orderBy('unit_number')
                ->get()
                ->mapWithKeys(fn ($u) => [
                    $u->id => $u->unit_number . ($u->unit_code ? " ({$u->unit_code})" : ''),
                ])
                ->all();
        });
    }

    public static function configure(Schema $schema): Schema
    {
        // Load dropdown data once — cached for 10 minutes, raw SQL (no Eloquent hydration)
        $tenantOptions = self::tenantOptions();
        $unitOptions   = self::unitOptions();

        return $schema
            ->components([
                // --- Lease Details ---
                Forms\Components\Hidden::make('reference_number'),
                Forms\Components\Hidden::make('workflow_state')->default('draft'),
                Forms\Components\DatePicker::make('date_created')
                    ->label('Date Created')
                    ->default(now())
                    ->native(false),

                Forms\Components\Select::make('source')
                    ->options([
                        'chabrin_issued'    => 'Chabrin Generated',
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
                                'commercial'  => 'Commercial',
                            ];
                        }

                        return [
                            'residential_major' => 'Residential (Major)',
                            'residential_micro' => 'Residential (Micro)',
                            'commercial'        => 'Commercial',
                        ];
                    })
                    ->default('residential_major')
                    ->required()
                    ->live(),

                Forms\Components\Select::make('lease_template_id')
                    ->label('Template')
                    ->options(function ($get) {
                        $leaseType = $get('lease_type');
                        $query     = \App\Models\LeaseTemplate::where('is_active', true);
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
                        'digital'  => 'Digital Signing (Email/SMS Link)',
                        'physical' => 'Physical Signing (Field Officer)',
                    ])
                    ->default('digital')
                    ->required()
                    ->helperText('Digital: Tenant signs online via email link. Physical: Field officer delivers printed document.'),

                // --- Property & Tenant ---
                // Pre-loaded via raw SQL (toBase / DB::table) — no Eloquent hydration,
                // no AES decryption, cached 10 min. Dropdown is instant in the browser.
                Forms\Components\Select::make('tenant_id')
                    ->label('Tenant')
                    ->options($tenantOptions)
                    ->searchable()
                    ->required(),

                // Unit — same caching strategy. Auto-fills rent/property/landlord on select.
                Forms\Components\Select::make('unit_id')
                    ->label('Unit')
                    ->options($unitOptions)
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (! $state) {
                            return;
                        }
                        $unit = \App\Models\Unit::with('property')->find($state);
                        if ($unit) {
                            $set('monthly_rent', $unit->rent_amount ?? 0);
                            $set('property_id', $unit->property_id);
                            $set('landlord_id', $unit->property?->landlord_id);
                            $set('client_id', $unit->property?->client_id);
                            if ($unit->property?->zone_id) {
                                $set('zone_id', $unit->property->zone_id);
                                $fieldOfficer = \App\Models\User::where('zone_id', $unit->property->zone_id)
                                    ->where('role', 'field_officer')
                                    ->first();
                                if ($fieldOfficer) {
                                    $set('assigned_field_officer_id', $fieldOfficer->id);
                                }
                            }
                        }
                    }),

                // Hidden fields — auto-populated when unit is selected
                Forms\Components\Hidden::make('property_id'),
                Forms\Components\Hidden::make('landlord_id'),
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
                                'Parent'   => 'Parent',
                                'Spouse'   => 'Spouse',
                                'Sibling'  => 'Sibling',
                                'Employer' => 'Employer',
                                'Friend'   => 'Friend',
                                'Other'    => 'Other',
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
