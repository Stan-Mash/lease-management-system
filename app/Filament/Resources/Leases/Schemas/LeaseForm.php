<?php

namespace App\Filament\Resources\Leases\Schemas;

use Filament\Forms;
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
                Forms\Components\Section::make('Lease Configuration')
                    ->description('Set the lease type, template, and signing method.')
                    ->icon('heroicon-o-document-text')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Hidden::make('reference_number'),
                        Forms\Components\Hidden::make('workflow_state')->default('draft'),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\DatePicker::make('date_created')
                                ->label('Date Created')
                                ->default(now())
                                ->native(false),

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

                        Forms\Components\Grid::make(2)->schema([
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
                            ->searchable()
                            ->placeholder('Leave blank to use system default')
                            ->helperText('Optional — the system will use the default template for the selected lease type.')
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
                    ]),

                // ═══════════════════════════════════════════════════════════
                // SECTION 2 — Property, Unit & Tenant
                // The cascade: select Property → Units filtered → Tenant filtered
                // ═══════════════════════════════════════════════════════════
                Forms\Components\Section::make('Property, Unit & Tenant')
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
                                $set('landlord_id', null);
                                $set('client_id', null);
                                $set('zone_id', null);
                                $set('assigned_field_officer_id', null);

                                // Auto-fill landlord/zone from property immediately
                                if ($state) {
                                    $property = \App\Models\Property::find($state);
                                    if ($property) {
                                        $set('landlord_id', $property->landlord_id);
                                        $set('client_id', $property->client_id);
                                        if ($property->zone_id) {
                                            $set('zone_id', $property->zone_id);
                                        }
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
                            ->searchable()
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
                                    : "{$count} unit(s) available for this property. ✅ = Vacant, 🔴 = Occupied";
                            })
                            ->disabled(fn ($get) => ! $get('property_id'))
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (! $state) {
                                    $set('monthly_rent', null);

                                    return;
                                }
                                $unit = \App\Models\Unit::with('property')->find($state);
                                if ($unit) {
                                    $set('monthly_rent', $unit->rent_amount ?? 0);
                                    // Re-confirm property-level fields from unit in case
                                    // property was pre-selected via property_id field
                                    if ($unit->property) {
                                        $set('landlord_id', $unit->property->landlord_id);
                                        $set('client_id', $unit->property->client_id);
                                        if ($unit->property->zone_id) {
                                            $set('zone_id', $unit->property->zone_id);
                                            $fieldOfficer = \App\Models\User::where('zone_id', $unit->property->zone_id)
                                                ->where('role', 'field_officer')
                                                ->first();
                                            if ($fieldOfficer) {
                                                $set('assigned_field_officer_id', $fieldOfficer->id);
                                            }
                                        }
                                    }
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
                                $result = self::tenantOptionsForProperty((int) $propId);
                                if ($result['is_filtered']) {
                                    $count = count($result['options']);

                                    return "Showing {$count} tenant(s) linked to this property.";
                                }

                                return '⚠️ No tenants are linked to this property — showing all tenants. You can still proceed.';
                            }),

                        // Hidden auto-filled fields
                        Forms\Components\Hidden::make('landlord_id'),
                        Forms\Components\Hidden::make('client_id'),
                        Forms\Components\Hidden::make('zone')->default('A'),
                        Forms\Components\Hidden::make('zone_id'),
                        Forms\Components\Hidden::make('assigned_field_officer_id'),
                    ]),

                // ═══════════════════════════════════════════════════════════
                // SECTION 3 — Financial Terms
                // ═══════════════════════════════════════════════════════════
                Forms\Components\Section::make('Financial Terms')
                    ->description('Monthly rent is auto-filled from the unit rate. Adjust if needed.')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
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
                                ->required(),
                        ]),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\DatePicker::make('start_date')
                                ->label('Lease Start Date')
                                ->required()
                                ->native(false),

                            Forms\Components\DatePicker::make('end_date')
                                ->label('Lease End Date (Optional)')
                                ->native(false)
                                ->helperText('Leave blank for a periodic/rolling tenancy.'),
                        ]),
                    ]),

                // ═══════════════════════════════════════════════════════════
                // SECTION 4 — Guarantor (collapsible, off by default)
                // ═══════════════════════════════════════════════════════════
                Forms\Components\Section::make('Guarantor')
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
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->required()
                                        ->label('Full Name')
                                        ->maxLength(200),

                                    Forms\Components\TextInput::make('id_number')
                                        ->required()
                                        ->label('National ID')
                                        ->maxLength(20),
                                ]),

                                Forms\Components\Grid::make(2)->schema([
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

                                Forms\Components\Grid::make(2)->schema([
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

                                Forms\Components\Grid::make(2)->schema([
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
