<?php

namespace App\Filament\Resources\Leases\Schemas;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LeaseForm
{
    /**
     * Return cached property options for the top-level property selector.
     * Raw SQL — no Eloquent hydration, no N+1.
     * Cache TTL: 10 minutes.
     */
    public static function propertyOptions(): array
    {
        return Cache::remember('form_options.properties', 600, function () {
            return DB::table('properties')
                ->select('id', 'property_name', 'reference_number')
                ->whereNull('deleted_at')
                ->orderBy('property_name')
                ->get()
                ->mapWithKeys(fn ($p) => [
                    $p->id => $p->property_name . ($p->reference_number ? " [{$p->reference_number}]" : ''),
                ])
                ->all();
        });
    }

    /**
     * Return units for a given property — raw SQL, NOT cached per-property
     * (called reactively on property select so we need fresh results).
     *
     * @return array<int, string>
     */
    public static function unitOptionsForProperty(?int $propertyId): array
    {
        if (! $propertyId) {
            return [];
        }

        return DB::table('units')
            ->select('id', 'unit_number', 'unit_code', 'rent_amount', 'status_legacy')
            ->where('property_id', $propertyId)
            ->whereNull('deleted_at')
            ->orderBy('unit_number')
            ->get()
            ->mapWithKeys(function ($u) {
                $label = $u->unit_number;
                if ($u->unit_code) {
                    $label .= " · {$u->unit_code}";
                }
                if ($u->rent_amount) {
                    $label .= ' · Ksh ' . number_format((float) $u->rent_amount, 0);
                }
                if ($u->status_legacy) {
                    $badge = match (strtoupper($u->status_legacy)) {
                        'VACANT'      => ' ✅',
                        'OCCUPIED'    => ' 🔴',
                        'MAINTENANCE' => ' 🔧',
                        default       => '',
                    };
                    $label .= $badge;
                }

                return [$u->id => $label];
            })
            ->all();
    }

    /**
     * Return tenants for a given property.
     *
     * Primary: tenants with tenants.property_id = $propertyId (directly linked tenants).
     * Fallback: if none found, return ALL tenants so the form is never blocked.
     *
     * Uses raw SQL to skip AES-256 decryption on Eloquent model boot.
     *
     * @return array{options: array<int, string>, is_filtered: bool}
     */
    public static function tenantOptionsForProperty(?int $propertyId): array
    {
        if (! $propertyId) {
            // No property chosen yet — show all tenants
            return [
                'options'     => self::allTenantOptions(),
                'is_filtered' => false,
            ];
        }

        $linked = DB::table('tenants')
            ->select('id', 'names', 'mobile_number')
            ->where('property_id', $propertyId)
            ->whereNull('deleted_at')
            ->orderBy('names')
            ->get()
            ->mapWithKeys(fn ($t) => [$t->id => "{$t->names} — {$t->mobile_number}"])
            ->all();

        if (count($linked) > 0) {
            return [
                'options'     => $linked,
                'is_filtered' => true,
            ];
        }

        // Fallback — no tenants linked to this property; show everyone with a note
        return [
            'options'     => self::allTenantOptions(),
            'is_filtered' => false,
        ];
    }

    /**
     * All tenants (no property filter) — cached 10 min.
     * Avoids AES-256 decryption by using raw DB query.
     *
     * @return array<int, string>
     */
    public static function allTenantOptions(): array
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

    public static function configure(Schema $schema): Schema
    {
        $propertyOptions = self::propertyOptions();

        return $schema
            ->components([

                // ═══════════════════════════════════════════════════════════
                // SECTION 1 — Lease Configuration
                // ═══════════════════════════════════════════════════════════
                Section::make('Lease Configuration')
                    ->description('Set the lease type, template, and signing method.')
                    ->icon('heroicon-o-document-text')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Hidden::make('reference_number'),
                        Forms\Components\Hidden::make('workflow_state')->default('draft'),

                        Grid::make(2)->schema([
                            Forms\Components\DatePicker::make('date_created')
                                ->label('Date Created')
                                ->default(now())
                                ->native(false)
                                ->displayFormat('D, j M Y')
                                ->closeOnDateSelection(),

                            Forms\Components\Select::make('source')
                                ->label('Lease Origin')
                                ->options([
                                    'chabrin_issued'    => '🏢 Chabrin Generated',
                                    'landlord_provided' => '🤝 Landlord Provided',
                                ])
                                ->default('chabrin_issued')
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn (callable $set) => $set('lease_type', null)),
                        ]),

                        Grid::make(2)->schema([
                            Forms\Components\Select::make('lease_type')
                                ->label('Lease Type')
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

                            Forms\Components\Select::make('signing_mode')
                                ->label('Signing Method')
                                ->options([
                                    'digital'  => '📱 Digital — Email / SMS Link',
                                    'physical' => '🖊️ Physical — Field Officer',
                                ])
                                ->default('digital')
                                ->required()
                                ->helperText('Digital: Tenant signs online. Physical: Field officer delivers printed document.'),
                        ]),

                        Forms\Components\Select::make('lease_template_id')
                            ->label('Lease Template')
                            ->options(function ($get) {
                                $leaseType = $get('lease_type');
                                $query     = \App\Models\LeaseTemplate::where('is_active', true);
                                if ($leaseType) {
                                    $query->where('template_type', $leaseType);
                                }

                                return $query->orderBy('name')->pluck('name', 'id');
                            })
                            ->placeholder('Leave blank to use system default')
                            ->helperText('Optional — the system will use the default template for the selected lease type.')
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $template = \App\Models\LeaseTemplate::find($state);
                                    if ($template) {
                                        $set('template_version_used', $template->version_number);
                                    }
                                }
                            }),

                        Forms\Components\Hidden::make('template_version_used'),
                    ]),

                // ═══════════════════════════════════════════════════════════
                // SECTION 2 — Property, Unit & Tenant
                // The cascade: select Property → Units filtered → Tenant filtered
                // ═══════════════════════════════════════════════════════════
                Section::make('Property, Unit & Tenant')
                    ->description('Select the property first — units and tenants will filter automatically.')
                    ->icon('heroicon-o-home-modern')
                    ->schema([

                        // ── Step 1: Property ────────────────────────────────
                        Forms\Components\Select::make('property_id')
                            ->label('Property')
                            ->options($propertyOptions)
                            ->searchable()
                            ->required()
                            ->live()
                            ->placeholder('Search for a property...')
                            ->helperText('Selecting a property filters units and tenants below.')
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Clear downstream selections when property changes
                                $set('unit_id', null);
                                $set('tenant_id', null);
                                $set('monthly_rent', null);
                                $set('deposit_amount', null);
                                $set('landlord_id', null);
                                $set('zone_id', null);
                                $set('zone', null);
                                $set('assigned_field_officer_id', null);

                                if (! $state) {
                                    return;
                                }

                                // Single raw query joining property + zone — landlord and zone in one hit
                                $row = DB::table('properties')
                                    ->leftJoin('zones', 'properties.zone_id', '=', 'zones.id')
                                    ->select(
                                        'properties.landlord_id',
                                        'properties.zone_id',
                                        'zones.code as zone_code',
                                    )
                                    ->where('properties.id', $state)
                                    ->first();

                                if ($row) {
                                    $set('landlord_id', $row->landlord_id);
                                    if ($row->zone_id) {
                                        $set('zone_id', $row->zone_id);
                                        $set('zone', $row->zone_code ? strtoupper($row->zone_code[0]) : null);
                                    }
                                }
                            }),

                        // ── Step 2: Unit (filtered by property) ─────────────
                        Forms\Components\Select::make('unit_id')
                            ->label('Unit')
                            ->options(function ($get) {
                                $propertyId = (int) $get('property_id');

                                return self::unitOptionsForProperty($propertyId ?: null);
                            })
                            ->required()
                            ->live()
                            ->placeholder(fn ($get) => $get('property_id')
                                ? 'Select a unit...'
                                : '← Select a property first')
                            ->helperText(function ($get) {
                                $propId = $get('property_id');
                                if (! $propId) {
                                    return 'Units will appear here after you select a property.';
                                }
                                $count = DB::table('units')
                                    ->where('property_id', $propId)
                                    ->whereNull('deleted_at')
                                    ->count();

                                return $count === 0
                                    ? '⚠️ No units found for this property.'
                                    : "{$count} unit(s) available. ✅ = Vacant, 🔴 = Occupied";
                            })
                            ->disabled(fn ($get) => ! $get('property_id'))
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (! $state) {
                                    $set('monthly_rent', null);
                                    $set('deposit_amount', null);

                                    return;
                                }

                                // Single raw JOIN query — unit + property + zone in one round-trip.
                                // Avoids Eloquent hydration and eliminates the old broken
                                // User::where('role', 'field_officer') query.
                                $row = DB::table('units')
                                    ->join('properties', 'units.property_id', '=', 'properties.id')
                                    ->leftJoin('zones', 'properties.zone_id', '=', 'zones.id')
                                    ->select(
                                        'units.rent_amount',
                                        'units.deposit_required',
                                        'properties.landlord_id',
                                        'properties.zone_id',
                                        'zones.code as zone_code',
                                    )
                                    ->where('units.id', $state)
                                    ->first();

                                if ($row) {
                                    $set('monthly_rent', $row->rent_amount ?? 0);
                                    $set('deposit_amount', $row->deposit_required);
                                    $set('landlord_id', $row->landlord_id);
                                    if ($row->zone_id) {
                                        $set('zone_id', $row->zone_id);
                                        $set('zone', $row->zone_code ? strtoupper($row->zone_code[0]) : null);
                                    }
                                }

                                // Auto-populate tenant from the most recent active lease on this unit
                                $occupantTenantId = DB::table('leases')
                                    ->where('unit_id', $state)
                                    ->whereIn('workflow_state', [
                                        'active',
                                        'pending_tenant_signature',
                                        'pending_otp',
                                        'approved',
                                    ])
                                    ->orderByDesc('created_at')
                                    ->value('tenant_id');

                                if ($occupantTenantId) {
                                    $set('tenant_id', $occupantTenantId);
                                }
                            }),

                        // ── Step 3: Tenant (filtered to this property) ───────
                        Forms\Components\Select::make('tenant_id')
                            ->label('Tenant')
                            ->options(function ($get) {
                                $propertyId = (int) $get('property_id');
                                $result = self::tenantOptionsForProperty($propertyId ?: null);

                                return $result['options'];
                            })
                            ->searchable()
                            ->required()
                            ->placeholder(fn ($get) => $get('property_id')
                                ? 'Search for a tenant...'
                                : '← Select a property first')
                            ->helperText(function ($get) {
                                $propId = $get('property_id');
                                if (! $propId) {
                                    return 'Tenants linked to the selected property will appear here.';
                                }
                                // Cheap COUNT query — avoids fetching the full tenant list twice
                                $linkedCount = DB::table('tenants')
                                    ->where('property_id', $propId)
                                    ->whereNull('deleted_at')
                                    ->count();

                                if ($linkedCount > 0) {
                                    return "Showing {$linkedCount} tenant(s) linked to this property.";
                                }

                                return '⚠️ No tenants linked to this property — showing all tenants. You can still proceed.';
                            }),

                        // Hidden auto-filled fields
                        Forms\Components\Hidden::make('landlord_id'),
                        Forms\Components\Hidden::make('zone'),
                        Forms\Components\Hidden::make('zone_id'),
                        Forms\Components\Hidden::make('assigned_field_officer_id'),
                    ]),

                // ═══════════════════════════════════════════════════════════
                // SECTION 3 — Financial Terms
                // ═══════════════════════════════════════════════════════════
                Section::make('Financial Terms')
                    ->description('Monthly rent and deposit are auto-filled from the selected unit. Adjust if needed.')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\TextInput::make('monthly_rent')
                                ->label('Monthly Rent')
                                ->numeric()
                                ->prefix('Ksh')
                                ->required()
                                ->helperText('Auto-filled from the selected unit\'s rent rate.'),

                            Forms\Components\TextInput::make('deposit_amount')
                                ->label('Security Deposit')
                                ->numeric()
                                ->prefix('Ksh')
                                ->required()
                                ->helperText('Auto-filled from the unit\'s deposit requirement.'),
                        ]),

                        Grid::make(2)->schema([
                            Forms\Components\TextInput::make('rent_review_years')
                                ->label('Rent Review After (years)')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(10)
                                ->placeholder('e.g. 1')
                                ->helperText('How many years before rent is reviewed. Printed on commercial leases.'),

                            Forms\Components\TextInput::make('rent_review_rate')
                                ->label('Rent Review Rate (%)')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->step(0.5)
                                ->suffix('%')
                                ->placeholder('e.g. 5.0')
                                ->helperText('Guide escalation rate offered at review time.'),
                        ]),

                        Grid::make(2)->schema([
                            Forms\Components\DatePicker::make('start_date')
                                ->label('Lease Start Date')
                                ->required()
                                ->native(false)
                                ->displayFormat('D, j M Y')
                                ->closeOnDateSelection()
                                ->hintAction(
                                    Action::make('setStartToday')
                                        ->label('Today')
                                        ->link()
                                        ->icon('heroicon-m-calendar-days')
                                        ->action(fn (Set $set) => $set('start_date', now()->toDateString()))
                                ),

                            Forms\Components\DatePicker::make('end_date')
                                ->label('Lease End Date (Optional)')
                                ->native(false)
                                ->displayFormat('D, j M Y')
                                ->closeOnDateSelection()
                                ->helperText('Leave blank for a periodic/rolling tenancy.')
                                ->hintAction(
                                    Action::make('setEndToday')
                                        ->label('Today')
                                        ->link()
                                        ->icon('heroicon-m-calendar-days')
                                        ->action(fn (Set $set) => $set('end_date', now()->toDateString()))
                                ),
                        ]),
                    ]),

                // ═══════════════════════════════════════════════════════════
                // SECTION 4 — Guarantor (collapsible, off by default)
                // ═══════════════════════════════════════════════════════════
                Section::make('Guarantor')
                    ->description('Add a guarantor if this tenancy requires one.')
                    ->icon('heroicon-o-user-group')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Toggle::make('requires_guarantor')
                            ->label('This lease requires a guarantor')
                            ->live()
                            ->default(false),

                        Forms\Components\Repeater::make('guarantors')
                            ->relationship('guarantors')
                            ->schema([
                                Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->required()
                                        ->label('Full Name')
                                        ->maxLength(200),

                                    Forms\Components\TextInput::make('id_number')
                                        ->required()
                                        ->label('National ID')
                                        ->maxLength(20),
                                ]),

                                Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('phone')
                                        ->required()
                                        ->tel()
                                        ->label('Phone Number')
                                        ->maxLength(20),

                                    Forms\Components\TextInput::make('email')
                                        ->email()
                                        ->label('Email (Optional)')
                                        ->maxLength(100),
                                ]),

                                Grid::make(2)->schema([
                                    Forms\Components\Select::make('relationship')
                                        ->required()
                                        ->label('Relationship to Tenant')
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
                                        ->label('Guarantee Amount')
                                        ->helperText('Defaults to security deposit if left blank.'),
                                ]),

                                Grid::make(2)->schema([
                                    Forms\Components\Toggle::make('signed')
                                        ->label('Has Signed Guarantee')
                                        ->default(false),

                                    Forms\Components\Textarea::make('notes')
                                        ->label('Notes')
                                        ->rows(2),
                                ]),
                            ])
                            ->visible(fn ($get) => $get('requires_guarantor'))
                            ->collapsible()
                            ->defaultItems(0)
                            ->addActionLabel('+ Add Guarantor')
                            ->label('Guarantors'),
                    ]),

            ]);
    }
}
