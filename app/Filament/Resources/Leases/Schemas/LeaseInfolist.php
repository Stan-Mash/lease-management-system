<?php

namespace App\Filament\Resources\Leases\Schemas;

use App\Models\LeaseAuditLog;
use App\Services\LeaseHealthService;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeaseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // ── 1. LEASE JOURNEY STEPPER (top, horizontal, full width) ─────────
            Section::make()
                ->schema([
                    Grid::make(1)->schema([
                        TextEntry::make('_stepper')
                            ->label('')
                            ->state(fn ($record) => self::buildStepperHtml($record))
                            ->html()
                            ->columnSpanFull(),
                    ]),
                ])
                ->extraAttributes([
                    'style' => 'padding:0; border:none; box-shadow:none; background:transparent;',
                ]),

            // ── 1b. JOURNEY HEADING (directly underneath stepper) ───────────────
            Section::make()
                ->schema([
                    Grid::make(1)->schema([
                        TextEntry::make('_journey_heading')
                            ->label('')
                            ->state(function ($record) {
                                try {
                                    return self::journeyHeading($record);
                                } catch (\Throwable $e) {
                                    report($e);
                                    $ref = $record?->reference_number ?? 'Lease';
                                    return '<div style="padding:12px; color:#92400e; background:#fef3c7; border-radius:8px;">'
                                        . '<strong>Unable to load journey status.</strong> Reference: ' . e($ref) . '. Please try again or check logs.</div>';
                                }
                            })
                            ->html()
                            ->columnSpanFull(),
                    ]),
                ])
                ->extraAttributes([
                    'class' => 'lease-journey-panel',
                    'style' => 'background: linear-gradient(135deg, #faf8f4 0%, #fff9e8 100%); border: 1.5px solid rgba(218,165,32,0.35); border-left: 5px solid #DAA520; border-radius: 12px;',
                ]),

            // ── 1b. LEASE JOURNEY STEPPER ───────────────────────────────────────
            Section::make()
                ->schema([
                    Grid::make(1)->schema([
                        TextEntry::make('_stepper')
                            ->label('')
                            ->state(function ($record) {
                                try {
                                    return self::buildStepperHtml($record);
                                } catch (\Throwable $e) {
                                    report($e);
                                    $ref = $record?->reference_number ?? 'Lease';
                                    return '<div style="padding:12px; color:#92400e; background:#fef3c7; border-radius:8px;">'
                                        . '<strong>Unable to load lease journey stepper.</strong> Reference: ' . e($ref) . '. Please try again or check logs.</div>';
                                }
                            })
                            ->html()
                            ->columnSpanFull(),
                    ]),
                ])
                ->extraAttributes([
                    'style' => 'padding:0; border:none; box-shadow:none; background:transparent;',
                ]),

            // ── 2. CORE LEASE DETAILS ────────────────────────────────────────────
            Section::make('Lease Details')
                ->icon('heroicon-o-document-text')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('reference_number')
                            ->label('Reference Number')
                            ->copyable()
                            ->weight('bold')
                            ->size('lg'),

                        TextEntry::make('workflow_state')
                            ->label('Current Status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => self::stateLabel($state))
                            ->color(fn (string $state): string => self::stateColor($state)),

                        TextEntry::make('signing_mode')
                            ->label('Signing Method')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'digital' => '📱 Digital (SMS Link)',
                                'physical' => '🖊 Physical (Print & Sign)',
                                default => '—',
                            })
                            ->color(fn (?string $state): string => match ($state) {
                                'digital' => 'info',
                                'physical' => 'gray',
                                default => 'gray',
                            }),
                    ]),

                    Grid::make(3)->schema([
                        TextEntry::make('lease_type')
                            ->label('Lease Type')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'residential_major' => 'Residential (Major)',
                                'residential_micro' => 'Residential (Micro)',
                                'commercial' => 'Commercial',
                                default => ucfirst($state ?? '—'),
                            })
                            ->color('gray'),

                        TextEntry::make('start_date')
                            ->label('Start Date')
                            ->date('d M Y')
                            ->placeholder('—'),

                        TextEntry::make('end_date')
                            ->label('End Date')
                            ->date('d M Y')
                            ->placeholder('—'),
                    ]),

                    Grid::make(3)->schema([
                        TextEntry::make('monthly_rent')
                            ->label('Monthly Rent')
                            ->money('KES')
                            ->weight('bold')
                            ->size('lg')
                            ->color('success'),

                        TextEntry::make('deposit_amount')
                            ->label('Security Deposit')
                            ->money('KES')
                            ->weight('bold'),

                        TextEntry::make('createdBy.name')
                            ->label('Created By')
                            ->default('—'),
                    ]),
                ]),

            // ── 3. TENANT & PROPERTY ──────────────────────────────────────────────
            Section::make('Tenant & Property')
                ->icon('heroicon-o-home')
                ->schema([
                    Grid::make(2)->schema([
                        // Tenant column
                        Section::make('Tenant')
                            ->schema([
                                TextEntry::make('tenant.names')
                                    ->label('Full Name')
                                    ->weight('bold'),
                                TextEntry::make('tenant.mobile_number')
                                    ->label('Phone Number')
                                    ->copyable()
                                    ->icon('heroicon-o-phone'),
                                TextEntry::make('tenant.email_address')
                                    ->label('Email')
                                    ->copyable()
                                    ->icon('heroicon-o-envelope')
                                    ->default('No email on record'),
                                TextEntry::make('tenant.national_id')
                                    ->label('National ID')
                                    ->copyable(),
                                TextEntry::make('tenant.preferred_language')
                                    ->label('SMS Language')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'en' => '🇬🇧 English',
                                        'sw' => '🇰🇪 Kiswahili',
                                        default => '🇬🇧 English (default)',
                                    })
                                    ->color('gray'),
                            ])
                            ->extraAttributes(['style' => 'background:#f8fafc; border:1px solid rgba(218,165,32,0.15); border-radius:10px;']),

                        // Property column
                        Section::make('Property & Unit')
                            ->schema([
                                TextEntry::make('property.property_name')
                                    ->label('Property')
                                    ->weight('bold'),
                                TextEntry::make('unit.unit_number')
                                    ->label('Unit Number')
                                    ->copyable(),
                                TextEntry::make('landlord.names')
                                    ->label('Landlord')
                                    ->default('—'),
                                TextEntry::make('landlord.mobile_number')
                                    ->label('Landlord Phone')
                                    ->copyable()
                                    ->icon('heroicon-o-phone')
                                    ->default('—'),
                                TextEntry::make('assignedZone.name')
                                    ->label('Zone')
                                    ->badge()
                                    ->color('gray')
                                    ->default('—'),
                            ])
                            ->extraAttributes(['style' => 'background:#f8fafc; border:1px solid rgba(218,165,32,0.15); border-radius:10px;']),
                    ]),
                ]),

            // ── 4. LANDLORD APPROVAL JOURNEY ─────────────────────────────────────
            Section::make('Step 1 — Landlord Approval')
                ->icon('heroicon-o-check-badge')
                ->description('The landlord must approve this lease before it can be sent to the tenant.')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('approvals_status')
                            ->label('Approval Status')
                            ->badge()
                            ->state(function ($record) {
                                if ($record->hasBeenApproved()) {
                                    return '✅ Approved';
                                }
                                if ($record->hasBeenRejected()) {
                                    return '❌ Rejected';
                                }
                                if ($record->hasPendingApproval()) {
                                    return '⏳ Waiting for Landlord';
                                }

                                return '⭕ Not Yet Sent';
                            })
                            ->color(function ($record) {
                                if ($record->hasBeenApproved()) {
                                    return 'success';
                                }
                                if ($record->hasBeenRejected()) {
                                    return 'danger';
                                }
                                if ($record->hasPendingApproval()) {
                                    return 'warning';
                                }

                                return 'gray';
                            }),

                        TextEntry::make('_approval_actioned_by')
                            ->label('Approved / Actioned By')
                            ->state(fn ($record) => $record->getLatestApproval()?->reviewer?->name ?? '—')
                            ->visible(fn ($record) => $record->getLatestApproval() !== null),

                        TextEntry::make('_approval_reviewed_at')
                            ->label('Decision Date & Time')
                            ->state(function ($record) {
                                $at = $record->getLatestApproval()?->reviewed_at;

                                return $at ? \Carbon\Carbon::parse($at)->format('d M Y, H:i') : '—';
                            })
                            ->visible(fn ($record) => $record->getLatestApproval() !== null),
                    ]),

                    TextEntry::make('_approval_rejection_reason')
                        ->label('❌ Rejection Reason')
                        ->state(fn ($record) => $record->getLatestApproval()?->rejection_reason ?? '—')
                        ->visible(fn ($record) => $record->hasBeenRejected())
                        ->color('danger')
                        ->weight('bold')
                        ->columnSpanFull(),

                    TextEntry::make('_approval_comments')
                        ->label('Comments from Approver')
                        ->state(fn ($record) => $record->getLatestApproval()?->comments ?? '')
                        ->visible(fn ($record) => filled($record->getLatestApproval()?->comments))
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed(fn ($record) => in_array($record->workflow_state, ['active', 'expired', 'terminated'])),

            // ── 5. DIGITAL SIGNING JOURNEY ────────────────────────────────────────
            // Only visible if signing mode is digital
            Section::make('Step 2 — Digital Signing Journey')
                ->icon('heroicon-o-device-phone-mobile')
                ->description(fn ($record) => $record->signing_mode === 'digital'
                    ? 'Track the tenant\'s progress through the SMS signing process.'
                    : 'This lease uses physical signing — no digital link is used.')
                ->schema([
                    // Signing link status
                    Grid::make(3)->schema([
                        TextEntry::make('_signing_link_status')
                            ->label('Signing Link')
                            ->state(function ($record) {
                                if ($record->signing_mode !== 'digital') {
                                    return '🖊 Physical signing method';
                                }
                                $state = $record->workflow_state;
                                if (in_array($state, ['draft', 'received', 'pending_landlord_approval'])) {
                                    return '⭕ Not sent yet — lease needs approval first';
                                }
                                if ($state === 'approved') {
                                    return '✅ Approved — ready to send link';
                                }
                                if (in_array($state, ['sent_digital', 'pending_otp', 'pending_tenant_signature', 'tenant_signed', 'active'])) {
                                    return '✅ Link sent to tenant';
                                }

                                return '—';
                            })
                            ->badge()
                            ->color(function ($record) {
                                $state = $record->workflow_state;
                                if ($state === 'approved') {
                                    return 'warning';
                                }
                                if (in_array($state, ['sent_digital', 'pending_otp', 'pending_tenant_signature', 'tenant_signed', 'active'])) {
                                    return 'success';
                                }

                                return 'gray';
                            }),

                        TextEntry::make('_otp_status')
                            ->label('OTP Verification')
                            ->state(function ($record) {
                                if ($record->signing_mode !== 'digital') {
                                    return '—';
                                }
                                $otp = $record->otpVerifications()->latest()->first();
                                if (! $otp) {
                                    return '⭕ No OTP requested yet';
                                }
                                if ($otp->is_verified) {
                                    return '✅ OTP verified by tenant';
                                }
                                if ($otp->hasExpired()) {
                                    return '⏰ OTP expired — tenant must request again';
                                }
                                if ($otp->maxAttemptsReached()) {
                                    return '🔒 Too many attempts — expired';
                                }

                                return '⏳ OTP sent — waiting for tenant to enter code';
                            })
                            ->badge()
                            ->color(function ($record) {
                                if ($record->signing_mode !== 'digital') {
                                    return 'gray';
                                }
                                $otp = $record->otpVerifications()->latest()->first();
                                if (! $otp) {
                                    return 'gray';
                                }
                                if ($otp->is_verified) {
                                    return 'success';
                                }
                                if ($otp->hasExpired() || $otp->maxAttemptsReached()) {
                                    return 'danger';
                                }

                                return 'warning';
                            }),

                        TextEntry::make('_signature_status')
                            ->label('Digital Signature')
                            ->state(function ($record) {
                                if ($record->signing_mode !== 'digital') {
                                    return '—';
                                }
                                $sig = $record->digitalSignatures()->latest()->first();
                                if (! $sig) {
                                    return '⭕ Not signed yet';
                                }
                                if ($sig->is_verified) {
                                    return '✅ Signed & verified';
                                }

                                return '⚠️ Signature recorded (unverified)';
                            })
                            ->badge()
                            ->color(function ($record) {
                                if ($record->signing_mode !== 'digital') {
                                    return 'gray';
                                }
                                $sig = $record->digitalSignatures()->latest()->first();
                                if (! $sig) {
                                    return 'gray';
                                }

                                return $sig->is_verified ? 'success' : 'warning';
                            }),
                    ]),

                    // Signature details — only show when signed
                    Grid::make(4)->schema([
                        TextEntry::make('_sig_by')
                            ->label('Signed By (Tenant)')
                            ->state(fn ($record) => $record->digitalSignatures()->latest()->first()?->tenant?->names ?? '—'),

                        TextEntry::make('_sig_at')
                            ->label('Signed At')
                            ->state(function ($record) {
                                $sig = $record->digitalSignatures()->latest()->first();

                                return $sig?->signed_at ? \Carbon\Carbon::parse($sig->signed_at)->format('d M Y, H:i') : '—';
                            }),

                        TextEntry::make('_sig_ip')
                            ->label('IP Address')
                            ->state(fn ($record) => $record->digitalSignatures()->latest()->first()?->ip_address ?? '—'),

                        TextEntry::make('_sig_location')
                            ->label('Location (GPS)')
                            ->state(function ($record) {
                                $sig = $record->digitalSignatures()->latest()->first();
                                if (! $sig || ! $sig->signature_latitude) {
                                    return 'Not captured';
                                }

                                return round($sig->signature_latitude, 4) . ', ' . round($sig->signature_longitude, 4);
                            }),
                    ])
                        ->visible(fn ($record) => $record->digitalSignatures()->exists()),

                    // OTP details
                    Grid::make(3)->schema([
                        TextEntry::make('_otp_sent_at')
                            ->label('Last OTP Sent')
                            ->state(function ($record) {
                                $otp = $record->otpVerifications()->latest()->first();

                                return $otp?->sent_at ? \Carbon\Carbon::parse($otp->sent_at)->format('d M Y, H:i') : '—';
                            }),

                        TextEntry::make('_otp_expires')
                            ->label('OTP Expiry')
                            ->state(function ($record) {
                                $otp = $record->otpVerifications()->latest()->first();
                                if (! $otp) {
                                    return '—';
                                }
                                if ($otp->is_verified) {
                                    return 'Used ✅';
                                }
                                if ($otp->hasExpired()) {
                                    return 'Expired ⏰';
                                }

                                return \Carbon\Carbon::parse($otp->expires_at)->format('H:i') . ' (expires)';
                            }),

                        TextEntry::make('_otp_attempts')
                            ->label('Verification Attempts')
                            ->state(function ($record) {
                                $otp = $record->otpVerifications()->latest()->first();
                                if (! $otp) {
                                    return '—';
                                }

                                return $otp->attempts . ' / 3';
                            }),
                    ])
                        ->visible(fn ($record) => $record->otpVerifications()->exists()),

                    // Phone confirmation
                    TextEntry::make('_sms_sent_to')
                        ->label('📱 SMS Sent To')
                        ->state(fn ($record) => $record->tenant?->mobile_number ?? 'No phone number')
                        ->weight('bold')
                        ->visible(fn ($record) => $record->signing_mode === 'digital'),
                ])
                ->collapsible()
                ->collapsed(false),

            // ── 6. LAWYER REVIEW TRACKING ─────────────────────────────────────────
            Section::make('Lawyer Review')
                ->icon('heroicon-o-scale')
                ->description('Legal review tracking — shows which lawyer handled this lease and turnaround time.')
                ->schema([
                    Grid::make(1)->schema([
                        TextEntry::make('_lawyer_tracking_panel')
                            ->label('')
                            ->state(fn ($record) => self::buildLawyerTrackingPanel($record))
                            ->html()
                            ->columnSpanFull(),
                    ]),
                ])
                ->visible(fn ($record) => $record->lawyerTrackings()->exists())
                ->collapsible()
                ->collapsed(fn ($record) => ! in_array($record->workflow_state, ['with_lawyer'])),

            // ── WITNESS RECORDS ───────────────────────────────────────────────────
            Section::make('Witness Records')
                ->icon('heroicon-o-eye')
                ->description('Persons who formally witnessed the signing of this lease (Track 1 — Operational Signing).')
                ->schema([
                    Grid::make(1)->schema([
                        TextEntry::make('_witness_panel')
                            ->label('')
                            ->state(fn ($record) => self::buildWitnessPanel($record))
                            ->html()
                            ->columnSpanFull(),
                    ]),
                ])
                ->visible(fn ($record) => $record->witnesses()->exists())
                ->collapsible()
                ->collapsed(fn ($record) => in_array($record->workflow_state, ['draft', 'received', 'pending_landlord_approval', 'approved', 'printed', 'checked_out'])),

            // ── 7. FINANCIAL SUMMARY ──────────────────────────────────────────────
            Section::make('Financial Summary')
                ->icon('heroicon-o-banknotes')
                ->schema([
                    Grid::make(4)->schema([
                        TextEntry::make('monthly_rent')
                            ->label('Monthly Rent')
                            ->money('KES')
                            ->weight('bold')
                            ->color('success'),

                        TextEntry::make('deposit_amount')
                            ->label('Security Deposit')
                            ->money('KES')
                            ->weight('bold'),

                        TextEntry::make('_annual_rent')
                            ->label('Annual Rent Value')
                            ->state(fn ($record) => 'KES ' . number_format($record->monthly_rent * 12, 2)),

                        TextEntry::make('deposit_verified')
                            ->label('Deposit Received?')
                            ->badge()
                            ->state(fn ($record) => $record->deposit_verified ? '✅ Verified' : '⭕ Not Yet Received')
                            ->color(fn ($record) => $record->deposit_verified ? 'success' : 'warning'),
                    ]),

                    Grid::make(3)->schema([
                        TextEntry::make('deposit_verified_at')
                            ->label('Deposit Verified On')
                            ->dateTime('d M Y')
                            ->placeholder('—')
                            ->visible(fn ($record) => (bool) $record->deposit_verified),

                        TextEntry::make('lease_term_months')
                            ->label('Lease Term')
                            ->formatStateUsing(fn ($state) => $state ? $state . ' months' : '—'),

                        TextEntry::make('is_periodic')
                            ->label('Periodic Tenancy?')
                            ->badge()
                            ->state(fn ($record) => $record->is_periodic ? 'Yes (month-to-month)' : 'No (fixed term)')
                            ->color(fn ($record) => $record->is_periodic ? 'warning' : 'gray'),
                    ]),
                ])
                ->collapsible(),

            // ── 7. GUARANTORS ─────────────────────────────────────────────────────
            Section::make('Guarantors')
                ->icon('heroicon-o-user-group')
                ->description('Individuals guaranteeing this lease agreement.')
                ->schema([
                    RepeatableEntry::make('guarantors')
                        ->schema([
                            Grid::make(5)->schema([
                                TextEntry::make('name')
                                    ->label('Name')
                                    ->weight('bold'),
                                TextEntry::make('id_number')
                                    ->label('National ID'),
                                TextEntry::make('phone')
                                    ->label('Phone')
                                    ->copyable(),
                                TextEntry::make('relationship')
                                    ->label('Relationship')
                                    ->badge()
                                    ->color('gray'),
                                TextEntry::make('signed')
                                    ->label('Signed?')
                                    ->badge()
                                    ->state(fn ($record) => $record->signed ? '✅ Signed' : '⭕ Not Yet')
                                    ->color(fn ($record) => $record->signed ? 'success' : 'warning'),
                            ]),
                        ])
                        ->contained(false),
                ])
                ->visible(fn ($record) => $record->guarantors()->exists())
                ->collapsible(),

            // ── 8. UPLOADED DOCUMENTS ─────────────────────────────────────────────
            Section::make('Uploaded Documents')
                ->icon('heroicon-o-paper-clip')
                ->description('Documents attached to this lease — scanned copies, ID, receipts.')
                ->schema([
                    RepeatableEntry::make('documents')
                        ->schema([
                            Grid::make(5)->schema([
                                TextEntry::make('title')
                                    ->label('Title')
                                    ->weight('bold'),
                                TextEntry::make('document_type')
                                    ->label('Type')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'signed_physical_lease' => 'Signed Lease',
                                        'original_signed' => 'Original Signed',
                                        'amendment' => 'Amendment',
                                        'addendum' => 'Addendum',
                                        'notice' => 'Notice',
                                        'id_copy' => 'ID Copy',
                                        'deposit_receipt' => 'Deposit Receipt',
                                        'other' => 'Other',
                                        default => ucfirst(str_replace('_', ' ', $state)),
                                    })
                                    ->color('gray'),
                                TextEntry::make('status')
                                    ->label('Review Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'approved', 'linked' => 'success',
                                        'pending_review', 'in_review' => 'warning',
                                        'rejected' => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('document_date')
                                    ->label('Doc Date')
                                    ->date('d/m/Y')
                                    ->placeholder('—'),
                                TextEntry::make('created_at')
                                    ->label('Uploaded')
                                    ->since(),
                            ]),
                        ])
                        ->contained(false),
                ])
                ->visible(fn ($record) => $record->documents()->exists())
                ->collapsible(),

            // ── 9. NOTES & HISTORY ────────────────────────────────────────────────
            Section::make('Notes & History')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->schema([
                    TextEntry::make('notes')
                        ->label('Lease Notes')
                        ->prose()
                        ->html()
                        ->default('No notes recorded.')
                        ->columnSpanFull(),
                ])
                ->visible(fn ($record) => filled($record->notes))
                ->collapsible()
                ->collapsed(),

            // ── 10. ACTIVITY TIMELINE ─────────────────────────────────────────────
            Section::make()
                ->schema([
                    Grid::make(1)->schema([
                        TextEntry::make('_timeline')
                            ->label('')
                            ->state(fn ($record) => self::buildTimelineHtml($record))
                            ->html()
                            ->columnSpanFull(),
                    ]),
                ])
                ->extraAttributes([
                    'style' => 'padding:0; border:none; box-shadow:none; background:transparent;',
                ]),
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Build the HTML lawyer tracking panel shown in the "Lawyer Review" section.
     * Shows all tracking records (sent/returned history) for this lease.
     */
    private static function buildLawyerTrackingPanel($record): string
    {
        $trackings = $record->lawyerTrackings()->with(['lawyer', 'sentByUser', 'receivedByUser'])->latest('sent_at')->get();
        $expected  = config('lease.lawyer.expected_turnaround_days', 7);

        if ($trackings->isEmpty()) {
            return '<p style="color:#6b7280; font-size:10pt; margin:0;">No lawyer tracking records found.</p>';
        }

        $rows = '';
        foreach ($trackings as $tracking) {
            $lawyer     = $tracking->lawyer;
            $name       = $lawyer ? ($lawyer->name . ($lawyer->firm ? " — {$lawyer->firm}" : '')) : '—';
            $sentVia    = ucfirst($tracking->sent_method ?? '—');
            $sentDate   = $tracking->sent_at?->format('d M Y, H:i') ?? '—';
            $sentBy     = $tracking->sentByUser?->name ?? '—';
            $returnedDate = $tracking->returned_at?->format('d M Y, H:i') ?? null;
            $receivedBy   = $tracking->receivedByUser?->name ?? null;
            $returnVia    = ucfirst($tracking->returned_method ?? '—');
            $days         = $tracking->turnaround_days;

            // Status badge styling
            [$statusLabel, $statusBg, $statusColor] = match ($tracking->status) {
                'sent'      => ['⏳ With Lawyer', '#fef3c7', '#92400e'],
                'returned'  => ['✅ Returned', '#d1fae5', '#065f46'],
                'cancelled' => ['❌ Cancelled', '#fee2e2', '#991b1b'],
                default     => ['⬜ Pending', '#f3f4f6', '#374151'],
            };

            // Turnaround info
            if ($days !== null) {
                $turnaroundColor = $days <= $expected ? '#059669' : '#dc2626';
                $turnaroundLabel = "{$days} day" . ($days !== 1 ? 's' : '');
                if ($days > $expected) {
                    $turnaroundLabel .= " (+" . ($days - $expected) . " over target)";
                }
            } elseif ($tracking->status === 'sent' && $tracking->sent_at) {
                $elapsed = (int) $tracking->sent_at->diffInDays(now());
                $turnaroundColor = $elapsed > $expected ? '#dc2626' : '#f59e0b';
                $turnaroundLabel = "{$elapsed} day" . ($elapsed !== 1 ? 's' : '') . ' (ongoing)';
                if ($elapsed > $expected) {
                    $turnaroundLabel .= " ⚠️ OVERDUE";
                }
            } else {
                $turnaroundColor = '#9ca3af';
                $turnaroundLabel = '—';
            }

            $returnSection = $returnedDate
                ? <<<HTML
                    <div style="margin-top:10px; padding-top:10px; border-top:1px dashed #e5e7eb;">
                        <div style="font-size:9pt; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:#059669; margin-bottom:6px;">Return Details</div>
                        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:6px; font-size:9.5pt;">
                            <div><span style="color:#6b7280;">Returned:</span> {$returnedDate}</div>
                            <div><span style="color:#6b7280;">Via:</span> {$returnVia}</div>
                            <div><span style="color:#6b7280;">Received by:</span> {$receivedBy}</div>
                        </div>
                    </div>
                HTML
                : '';

            $specializationHtml = ($lawyer && $lawyer->specialization)
                ? "<div style=\"font-size:9pt; color:#6366f1; margin-top:2px;\">{$lawyer->specialization}</div>"
                : '';
            $phoneHtml = ($lawyer && $lawyer->phone)
                ? "<div style=\"font-size:9pt; color:#6b7280; margin-top:2px;\">📞 {$lawyer->phone}</div>"
                : '';

            $rows .= <<<HTML
            <div style="background:white; border:1px solid #e5e7eb; border-radius:8px; padding:14px 16px; margin-bottom:10px;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                    <div>
                        <div style="font-size:11pt; font-weight:700; color:#1a365d;">⚖️ {$name}</div>
                        {$specializationHtml}
                        {$phoneHtml}
                    </div>
                    <span style="background:{$statusBg}; color:{$statusColor}; font-size:9pt; font-weight:700; padding:3px 10px; border-radius:999px;">{$statusLabel}</span>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:6px; font-size:9.5pt;">
                    <div><span style="color:#6b7280;">Sent:</span> {$sentDate}</div>
                    <div><span style="color:#6b7280;">Via:</span> {$sentVia}</div>
                    <div><span style="color:#6b7280;">Sent by:</span> {$sentBy}</div>
                    <div><span style="color:{$turnaroundColor}; font-weight:700;">⏱ {$turnaroundLabel}</span></div>
                </div>
                {$returnSection}
            </div>
            HTML;
        }

        $targetNote = "Chabrin target: {$expected} working days turnaround.";

        return <<<HTML
        <div>
            <p style="font-size:9pt; color:#6b7280; margin:0 0 12px 0;">{$targetNote}</p>
            {$rows}
        </div>
        HTML;
    }

    /**
     * Build HTML panel showing all witness records for this lease.
     */
    private static function buildWitnessPanel($record): string
    {
        $witnesses = $record->witnesses()->orderBy('witnessed_at')->get();

        if ($witnesses->isEmpty()) {
            return '<p style="color:#9ca3af; font-size:9pt; margin:0;">No witness records recorded yet.</p>';
        }

        $cards = '';
        foreach ($witnesses as $w) {
            $partyLabel = $w->witnessed_party === 'tenant' ? 'Tenant (Lessee)' : 'Lessor / Property Manager';
            $partyColor = $w->witnessed_party === 'tenant' ? '#2563eb' : '#16a34a';
            $typeLabel  = match ($w->witness_type) {
                'advocate' => 'LSK Advocate',
                'external' => 'External Witness',
                default    => 'Chabrin Staff',
            };
            $typeBg = match ($w->witness_type) {
                'advocate' => '#fef3c7',
                'external' => '#f3e8ff',
                default    => '#f0f9ff',
            };
            $typeColor = match ($w->witness_type) {
                'advocate' => '#92400e',
                'external' => '#6b21a8',
                default    => '#0c4a6e',
            };
            $name    = htmlspecialchars($w->witnessed_by_name);
            $title   = $w->witnessed_by_title ? ' &mdash; <em>' . htmlspecialchars($w->witnessed_by_title) . '</em>' : '';
            $lsk     = $w->lsk_number ? "<span style=\"font-size:8pt; color:#6b7280;\">LSK No: {$w->lsk_number}</span><br>" : '';
            $date    = $w->witnessed_at?->format('d M Y, h:i A') ?? '—';
            $notes   = $w->notes ? '<p style="font-size:8pt; color:#6b7280; margin:4px 0 0 0;">' . htmlspecialchars($w->notes) . '</p>' : '';

            $cards .= <<<HTML
            <div style="border:1px solid #e5e7eb; border-radius:8px; padding:12px 16px; margin-bottom:10px; background:#fff;">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                    <span style="font-size:8pt; font-weight:600; background:{$partyColor}; color:#fff; padding:2px 8px; border-radius:10px;">
                        Witnessed: {$partyLabel}
                    </span>
                    <span style="font-size:8pt; font-weight:500; background:{$typeBg}; color:{$typeColor}; padding:2px 8px; border-radius:10px;">
                        {$typeLabel}
                    </span>
                </div>
                <p style="margin:0 0 2px 0; font-size:10pt; font-weight:600; color:#111827;">{$name}{$title}</p>
                {$lsk}
                <p style="margin:0; font-size:9pt; color:#6b7280;">Witnessed on: <strong>{$date}</strong></p>
                {$notes}
            </div>
            HTML;
        }

        $count = $witnesses->count();
        $max   = 5; // expected for a full commercial lease
        $summary = "{$count} of {$max} witness position(s) recorded";

        return <<<HTML
        <div>
            <p style="font-size:9pt; color:#6b7280; margin:0 0 12px 0;">{$summary} — full execution requires witnesses for both Lessor and Lessee.</p>
            {$cards}
        </div>
        HTML;
    }

    private static function journeyHeading($record): string
    {
        $state = $record->workflow_state;
        $tenant = $record->tenant?->names ?? 'Tenant';
        $phone = $record->tenant?->mobile_number ?? '—';
        $ref = $record->reference_number ?? '—';
        $mode = $record->signing_mode ?? 'digital';

        [$icon, $color, $title, $body, $next] = self::journeyContent($record, $state, $tenant, $phone, $mode);

        return <<<HTML
        <div style="padding:4px 0 8px 0">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                <span style="font-size:22pt; line-height:1;">{$icon}</span>
                <div>
                    <div style="font-size:9pt; font-weight:700; letter-spacing:0.12em; text-transform:uppercase; color:#b8960a; margin-bottom:2px;">Lease Reference: {$ref}</div>
                    <div style="font-size:14pt; font-weight:800; color:#1a365d; font-family:'Cormorant Garamond',Georgia,serif; line-height:1.2;">{$title}</div>
                </div>
            </div>
            <p style="font-size:10pt; color:#374151; margin:0 0 10px 0; line-height:1.6;">{$body}</p>
            <div style="background:rgba(218,165,32,0.1); border-radius:8px; padding:10px 14px; display:inline-block;">
                <span style="font-size:9pt; font-weight:700; color:#92700a; text-transform:uppercase; letter-spacing:0.08em;">👉 Next Step: </span>
                <span style="font-size:9.5pt; color:#1a365d; font-weight:600;">{$next}</span>
            </div>
        </div>
        HTML;
    }

    private static function journeyContent($record, string $state, string $tenant, string $phone, string $mode): array
    {
        return match ($state) {
            'draft' => [
                '📝', '#DAA520',
                'Lease is a Draft',
                "This lease for {$tenant} has been created but not yet sent for landlord approval. Review the details and request approval when ready.",
                'Click "Request Landlord Approval" to notify the landlord — or "Approve Lease" to approve it yourself.',
            ],
            'received' => [
                '📬', '#3b82f6',
                'Lease Received',
                "This lease for {$tenant} has been received and logged in the system.",
                'Review the lease details then click "Request Landlord Approval".',
            ],
            'pending_landlord_approval' => [
                '⏳', '#f59e0b',
                'Waiting for Landlord Approval',
                "An approval request has been sent to the landlord. The lease for {$tenant} is on hold until they respond.",
                'Wait for landlord response, or use "Approve Lease" to approve on their behalf if authorised.',
            ],
            'approved' => [
                '✅', '#059669',
                'Approved — Ready to Send to Tenant',
                "The landlord has approved this lease for {$tenant}. "
                    . ($mode === 'digital'
                        ? "The tenant will receive an SMS signing link to their phone ({$phone})."
                        : 'Print the lease and arrange physical signing.'),
                $mode === 'digital'
                    ? "Click \"Send Digital Link\" — an SMS will be sent to {$phone} with a link to sign online."
                    : 'Click "Print Lease" to print the physical copy for signing.',
            ],
            'sent_digital' => [
                '📱', '#3b82f6',
                'Signing Link Sent to Tenant',
                "An SMS signing link has been sent to {$tenant} at {$phone}. The tenant has not yet opened the link or requested an OTP.",
                'Wait for the tenant to open the link and request their OTP. You can resend the link if needed.',
            ],
            'pending_otp' => [
                '🔐', '#f59e0b',
                'Tenant Has Requested the OTP',
                "Good progress! {$tenant} opened the signing link and requested a one-time verification code (OTP) to their phone {$phone}. They now need to enter the code to proceed.",
                'Wait for the tenant to enter their OTP. If they say they didn\'t receive it, ask them to request it again on the signing page.',
            ],
            'pending_tenant_signature' => [
                '✍️', '#8b5cf6',
                'Waiting for Tenant to Sign',
                "{$tenant} has verified their OTP and is now on the signature page. They can see the full lease and are about to sign.",
                'Wait for the tenant to draw or upload their signature and click Submit.',
            ],
            'tenant_signed' => (function () use ($record, $tenant): array {
                $requiresLawyer = (bool) $record->requires_lawyer;
                $body = "{$tenant} has digitally signed this lease. The signature has been captured with timestamp and IP address. ";
                $next = $requiresLawyer
                    ? 'This lease requires lawyer review — click "Send to Lawyer" to assign it to a lawyer and start turnaround tracking.'
                    : 'Click "Countersign & Activate" to countersign directly, or use "Send to Lawyer" if legal review is needed first.';
                $body .= $requiresLawyer
                    ? 'Lawyer review is required before this lease can be activated.'
                    : 'Lawyer review is optional — you may countersign directly or send to a lawyer.';

                return ['🎉', '#059669', 'Tenant Has Signed!', $body, $next];
            })(),
            'with_lawyer' => (function () use ($record): array {
                $tracking  = $record->lawyerTrackings()
                    ->with('lawyer')
                    ->whereIn('status', ['sent', 'pending'])
                    ->latest('sent_at')
                    ->first();

                $expected    = config('lease.lawyer.expected_turnaround_days', 7);
                $daysElapsed = $tracking?->sent_at ? (int) $tracking->sent_at->diffInDays(now()) : null;
                $isOverdue   = $daysElapsed !== null && $daysElapsed > $expected;

                if ($tracking && $tracking->lawyer) {
                    $lawyer      = $tracking->lawyer;
                    $lawyerName  = $lawyer->name . ($lawyer->firm ? " ({$lawyer->firm})" : '');
                    $sentVia     = ucfirst($tracking->sent_method ?? 'unknown');
                    $sentDate    = $tracking->sent_at?->format('d M Y') ?? '—';
                    $daysMsg     = $daysElapsed !== null ? " — {$daysElapsed} day(s) elapsed" : '';
                    $overdueMsg  = $isOverdue
                        ? " ⚠️ OVERDUE by " . ($daysElapsed - $expected) . " day(s). Please follow up."
                        : " (target: {$expected} days)";

                    $body = "Sent to {$lawyerName} via {$sentVia} on {$sentDate}{$daysMsg}.{$overdueMsg}";

                    $contact = [];
                    if ($lawyer->phone) {
                        $contact[] = "📞 {$lawyer->phone}";
                    }
                    if ($lawyer->email) {
                        $contact[] = "✉️ {$lawyer->email}";
                    }
                    if ($contact) {
                        $body .= ' Contact: ' . implode('  |  ', $contact);
                    }

                    $next = $isOverdue
                        ? "⚠️ Follow up urgently with {$lawyer->name} — lease is overdue by " . ($daysElapsed - $expected) . " day(s). Once returned, click \"Mark as Returned from Lawyer\"."
                        : "Wait for {$lawyer->name} to return the stamped lease. Once received, click \"Mark as Returned from Lawyer\".";

                    return ['⚖️', $isOverdue ? '#ef4444' : '#6366f1', 'With Lawyer for Review', $body, $next];
                }

                // Fallback if no tracking record found
                return [
                    '⚖️', '#6366f1',
                    'With Lawyer for Review',
                    'This lease has been sent to a lawyer for legal review. Turnaround time is being tracked.',
                    'Once the lawyer returns the document, click "Mark as Returned from Lawyer".',
                ];
            })(),
            'pending_upload' => [
                '📤', '#f59e0b',
                'Waiting for Signed Copy Upload',
                'The lease needs the signed physical copy to be scanned and uploaded before it can be activated.',
                'Click "Upload Documents" to upload the scanned signed lease.',
            ],
            'pending_deposit' => [
                '💰', '#f59e0b',
                'Waiting for Security Deposit',
                'Almost there! The signed lease is in order. The security deposit of KES ' . number_format((float) $record->deposit_amount, 2) . ' needs to be confirmed before activating.',
                'Once deposit is received, confirm it and transition the lease to ACTIVE.',
            ],
            'active' => [
                '🏠', '#059669',
                'Lease is ACTIVE',
                "This lease for {$tenant} is fully executed and active. The tenant is occupying the unit. Monthly rent: KES " . number_format((float) $record->monthly_rent, 2) . '.',
                'Monitor rent payments, watch for upcoming escalations, and manage renewal when the time comes.',
            ],
            'disputed' => [
                '⚠️', '#ef4444',
                'Tenant Has Disputed This Lease',
                "The tenant ({$tenant}) has raised a dispute about this lease. They have provided a reason and the lease cannot proceed until the dispute is resolved.",
                'Review the dispute reason below, then either "Resolve Dispute" (fix the issue and re-send) or "Cancel Lease".',
            ],
            'renewal_offered' => [
                '🔄', '#8b5cf6',
                'Renewal Offer Sent',
                "A lease renewal offer has been sent to {$tenant}. The current lease is expiring soon.",
                'Wait for tenant to accept or decline the renewal offer.',
            ],
            'expired' => [
                '📅', '#6b7280',
                'Lease Expired',
                "This lease for {$tenant} has expired. The tenancy period has ended.",
                'Archive this lease or create a new lease if the tenant is continuing.',
            ],
            'terminated' => [
                '🛑', '#ef4444',
                'Lease Terminated',
                'This lease was terminated before its natural end date.',
                'No further action required. Review notes for termination details.',
            ],
            'cancelled' => [
                '❌', '#6b7280',
                'Lease Cancelled',
                'This lease was cancelled and is no longer active.',
                'No further action required.',
            ],
            default => [
                '📄', '#6b7280',
                'Status: ' . self::stateLabel($state),
                "Lease for {$tenant}.",
                'Review and take the appropriate action.',
            ],
        };
    }

    private static function stateLabel(string $state): string
    {
        return match ($state) {
            'draft' => 'Draft',
            'received' => 'Received',
            'pending_landlord_approval' => 'Pending Landlord Approval',
            'approved' => 'Approved',
            'printed' => 'Printed',
            'checked_out' => 'Checked Out',
            'sent_digital' => 'Signing Link Sent',
            'pending_otp' => 'OTP Requested',
            'pending_tenant_signature' => 'Awaiting Signature',
            'tenant_signed' => 'Tenant Signed',
            'with_lawyer' => 'With Lawyer',
            'pending_upload' => 'Pending Upload',
            'pending_deposit' => 'Pending Deposit',
            'active' => 'Active',
            'renewal_offered' => 'Renewal Offered',
            'renewal_accepted' => 'Renewal Accepted',
            'renewal_declined' => 'Renewal Declined',
            'expired' => 'Expired',
            'terminated' => 'Terminated',
            'cancelled' => 'Cancelled',
            'disputed' => 'Disputed',
            'archived' => 'Archived',
            default => ucwords(str_replace('_', ' ', $state)),
        };
    }

    private static function stateColor(string $state): string
    {
        return match ($state) {
            'draft' => 'gray',
            'received' => 'info',
            'pending_landlord_approval' => 'warning',
            'approved' => 'info',
            'printed', 'checked_out' => 'gray',
            'sent_digital' => 'info',
            'pending_otp' => 'warning',
            'pending_tenant_signature' => 'warning',
            'tenant_signed' => 'success',
            'with_lawyer' => 'info',
            'pending_upload' => 'warning',
            'pending_deposit' => 'warning',
            'active' => 'success',
            'renewal_offered' => 'warning',
            'renewal_accepted' => 'success',
            'renewal_declined' => 'danger',
            'disputed' => 'danger',
            'expired', 'terminated', 'cancelled', 'archived' => 'danger',
            default => 'gray',
        };
    }

    // ── Stepper HTML ─────────────────────────────────────────────────────────

    private static function buildStepperHtml($record): string
    {
        try {
            $macroSteps = self::buildMacroSteps($record);
            $detailSteps = self::buildDetailSteps($record);
            $progress = self::buildProgress($record);
            $currentStateLabel = self::stateLabel($record->workflow_state ?? 'draft');
            $currentStateColor = self::stateColor($record->workflow_state ?? 'draft');

            $health = ['score' => 0, 'grade' => 'F', 'flags' => []];
            try {
                $health = LeaseHealthService::score($record);
            } catch (\Throwable) {
                // keep default health
            }
        } catch (\Throwable $e) {
            report($e);
            $ref = $record?->reference_number ?? 'Lease';
            return '<div style="padding:12px; color:#92400e; background:#fef3c7; border-radius:8px;">'
                . '<strong>Unable to load lease journey stepper.</strong> Reference: ' . e($ref) . '. Please try again or check logs.</div>';
        }

        $score = $health['score'];
        $grade = $health['grade'];
        $ring  = $grade === 'A' ? '#DAA520' : ($grade === 'B' ? '#f59e0b' : '#ef4444');

        $statePillStyle = [
            'gray'    => 'background:#f3f4f6; color:#374151;',
            'warning' => 'background:#fef3c7; color:#92400e;',
            'info'    => 'background:#dbeafe; color:#1e40af;',
            'success' => 'background:#dcfce7; color:#166534;',
            'danger'  => 'background:#fee2e2; color:#991b1b;',
            'primary' => 'background:#e0e7ff; color:#3730a3;',
        ];
        $pillStyle = $statePillStyle[$currentStateColor] ?? 'background:#f3f4f6; color:#374151;';

        // Progress bar
        $progressBar = '<div style="height:4px; width:100%; background:rgba(218,165,32,0.15);">'
            . '<div style="height:100%; width:' . $progress . '%; background:linear-gradient(to right,#DAA520,#92700a);"></div>'
            . '</div>';

        // Health ring SVG
        $healthRing = '<div style="display:flex; align-items:center; gap:8px;" title="Health: ' . $score . '/100 (' . $grade . ')">'
            . '<div style="position:relative; width:40px; height:40px;">'
            . '<svg width="40" height="40" viewBox="0 0 36 36" style="transform:rotate(-90deg);">'
            . '<path stroke="rgba(218,165,32,0.2)" stroke-width="3" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>'
            . '<path stroke="' . $ring . '" stroke-width="3" stroke-dasharray="' . $score . ',100" stroke-linecap="round" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>'
            . '</svg>'
            . '<span style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; color:' . $ring . ';">' . $grade . '</span>'
            . '</div>'
            . '<span style="font-size:11px; color:#92700a;">' . $score . '/100</span>'
            . '</div>';

        // Phase track
        $phaseHtml = '<div style="display:flex; align-items:center; gap:4px; overflow-x:auto; padding-bottom:4px;">';
        foreach ($macroSteps as $index => $step) {
            if ($step['disputed']) {
                $dot = '<div style="width:32px; height:32px; border-radius:50%; background:#fee2e2; color:#dc2626; display:flex; align-items:center; justify-content:center;">'
                    . '<svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd"/></svg>'
                    . '</div>';
            } elseif ($step['completed']) {
                $dot = '<div style="width:32px; height:32px; border-radius:50%; background:#DAA520; color:#fff; display:flex; align-items:center; justify-content:center;">'
                    . '<svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M19.916 4.626a.75.75 0 01.208 1.04l-9 13.5a.75.75 0 01-1.154.114l-6-6a.75.75 0 011.06-1.06l5.353 5.353 8.493-12.739a.75.75 0 011.04-.208z" clip-rule="evenodd"/></svg>'
                    . '</div>';
            } elseif ($step['current']) {
                $dot = '<div style="width:32px; height:32px; border-radius:50%; background:#1a365d; color:#fff; display:flex; align-items:center; justify-content:center;">'
                    . '<span style="width:8px; height:8px; border-radius:50%; background:#DAA520;"></span>'
                    . '</div>';
            } else {
                $dot = '<div style="width:32px; height:32px; border-radius:50%; border:2px dashed rgba(218,165,32,0.4); background:#fff;"></div>';
            }

            $labelColor = $step['current'] ? '#1a365d' : ($step['completed'] ? '#92700a' : '#9ca3af');
            $labelWeight = $step['current'] ? '700' : '500';
            $ts = $step['timestamp'] ? '<div style="font-size:9px; color:#b8960a; margin-top:1px;">' . $step['timestamp'] . '</div>' : '';

            $phaseHtml .= '<div style="display:flex; flex-direction:column; align-items:center; flex-shrink:0;">'
                . $dot
                . '<span style="margin-top:4px; font-size:10px; font-weight:' . $labelWeight . '; color:' . $labelColor . '; text-align:center; max-width:64px;">' . htmlspecialchars($step['label']) . '</span>'
                . $ts
                . '</div>';

            if ($index < count($macroSteps) - 1) {
                $next = $macroSteps[$index + 1] ?? null;
                if ($step['completed'] && $next && $next['completed']) {
                    $lineStyle = 'background:#DAA520;';
                } elseif ($step['completed'] && $next && $next['current']) {
                    $lineStyle = 'background:linear-gradient(to right,#DAA520,#1a365d);';
                } else {
                    $lineStyle = 'border-top:2px dashed rgba(218,165,32,0.3);';
                }
                $phaseHtml .= '<div style="flex:1; min-width:12px; height:2px; ' . $lineStyle . '"></div>';
            }
        }
        $phaseHtml .= '<div style="margin-left:8px; flex-shrink:0;">'
            . '<span style="display:inline-flex; border-radius:9999px; padding:3px 12px; font-size:11px; font-weight:600; ' . $pillStyle . '">' . htmlspecialchars($currentStateLabel) . '</span>'
            . '</div>';
        $phaseHtml .= '</div>';

        // Detail cards
        $statusLabels = [
            'done' => 'Done', 'active' => 'Active', 'pending' => 'Pending',
            'skipped' => 'Skipped', 'action_required' => 'Action Required',
        ];
        $badgeStyles = [
            'done'            => 'background:rgba(218,165,32,0.15); color:#92700a;',
            'active'          => 'background:#1a365d; color:#fff;',
            'pending'         => 'background:#f3f4f6; color:#6b7280;',
            'skipped'         => 'background:#fff7ed; color:#c2410c;',
            'action_required' => 'background:#fef2f2; color:#b91c1c;',
        ];
        $topColors = [
            'done' => '#DAA520', 'active' => '#1a365d', 'pending' => '#e5e7eb',
            'skipped' => '#e5e7eb', 'action_required' => '#ef4444',
        ];

        $cardsHtml = '<div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:10px;">';
        foreach ($detailSteps as $step) {
            $topColor = $topColors[$step['status']] ?? '#e5e7eb';
            $badgeStyle = $badgeStyles[$step['status']] ?? 'background:#f3f4f6; color:#6b7280;';
            $numStyle = $step['status'] === 'done' ? 'background:rgba(218,165,32,0.15); color:#92700a;' : 'background:#f3f4f6; color:#6b7280;';
            $badgeLabel = $statusLabels[$step['status']] ?? $step['status'];
            $ts = $step['timestamp'] ? '<div style="font-size:10px; color:#b8960a; margin-top:6px;">' . $step['timestamp'] . '</div>' : '';

            $cardsHtml .= '<div style="border-radius:8px; padding:12px; background:#fff;'
                . ' border-top:4px solid ' . $topColor . ';'
                . ' border-right:1px solid rgba(218,165,32,0.2);'
                . ' border-bottom:1px solid rgba(218,165,32,0.2);'
                . ' border-left:1px solid rgba(218,165,32,0.2);">'
                . '<div style="display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; border-radius:50%; font-size:11px; font-weight:700; margin-bottom:4px; ' . $numStyle . '">' . $step['number'] . '</div>'
                . '<span style="display:inline-block; border-radius:9999px; padding:1px 7px; font-size:10px; font-weight:600; margin-left:4px; ' . $badgeStyle . '">' . $badgeLabel . '</span>'
                . '<div style="font-size:12px; font-weight:600; color:#1a365d; line-height:1.3; margin-top:6px;">' . htmlspecialchars($step['title']) . '</div>'
                . '<div style="font-size:11px; color:#6b7280; margin-top:2px;">' . htmlspecialchars($step['description']) . '</div>'
                . $ts
                . '</div>';
        }
        $cardsHtml .= '</div>';

        // Assemble full stepper
        return '<div style="overflow:hidden; border-radius:12px; background:linear-gradient(135deg,#faf8f4 0%,#fff9e8 100%); border-top:1.5px solid rgba(218,165,32,0.35); border-right:1.5px solid rgba(218,165,32,0.35); border-bottom:1.5px solid rgba(218,165,32,0.35); border-left:5px solid #DAA520;">'
            . $progressBar
            . '<div style="padding:20px;">'
            . '<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">'
            . '<div style="display:flex; align-items:center; gap:8px;">'
            . '<span style="display:inline-block; width:12px; height:12px; border-radius:3px; background:#DAA520;"></span>'
            . '<span style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:#92700a;">Lease Journey</span>'
            . '</div>'
            . $healthRing
            . '</div>'
            . $phaseHtml
            . '<div style="margin:20px 0; height:1px; background:linear-gradient(to right,rgba(218,165,32,0.5),transparent);"></div>'
            . $cardsHtml
            . '</div>'
            . '</div>';
    }

    private static function buildMacroSteps($record): array
    {
        $labels = [1 => 'Draft', 2 => 'Landlord Approved', 3 => 'Sent to Tenant', 4 => 'Tenant Signed', 5 => 'Countersigned', 6 => 'Active', 7 => 'Closed'];
        $phaseMap = [
            'draft' => 1, 'received' => 1,
            'pending_landlord_approval' => 2, 'approved' => 2,
            'printed' => 3, 'checked_out' => 3, 'sent_digital' => 3,
            'pending_otp' => 3, 'pending_tenant_signature' => 3, 'returned_unsigned' => 3,
            'tenant_signed' => 4, 'with_lawyer' => 4, 'pending_upload' => 4,
            'pending_deposit' => 5,
            'active' => 6, 'renewal_offered' => 6, 'renewal_accepted' => 6,
            'expired' => 7, 'terminated' => 7, 'cancelled' => 7, 'renewal_declined' => 7, 'archived' => 7,
        ];

        if (! $record) {
            return array_map(fn ($phase, $label) => ['phase' => $phase, 'label' => $label, 'completed' => false, 'current' => $phase === 1, 'disputed' => false, 'timestamp' => null], array_keys($labels), $labels);
        }

        $workflowState = $record->workflow_state ?? 'draft';
        $currentPhase = $phaseMap[$workflowState] ?? 1;
        $isDisputed   = $workflowState === 'disputed';

        // Build timestamps from audit log
        $timestamps = [];
        foreach (LeaseAuditLog::where('lease_id', $record->id)->whereNotNull('new_state')->orderBy('created_at')->get() as $log) {
            $phase = $phaseMap[$log->new_state] ?? null;
            if ($phase && ! isset($timestamps[$phase])) {
                $timestamps[$phase] = $log->created_at->format('j M Y, g:i A');
            }
        }

        $steps = [];
        for ($phase = 1; $phase <= 7; $phase++) {
            $steps[] = [
                'phase'     => $phase,
                'label'     => $labels[$phase],
                'completed' => ! $isDisputed && $currentPhase > $phase,
                'current'   => ! $isDisputed && $currentPhase === $phase,
                'disputed'  => $isDisputed && $phase === 3,
                'timestamp' => $timestamps[$phase] ?? null,
            ];
        }

        return $steps;
    }

    private static function buildDetailSteps($record): array
    {
        $defs = [
            ['title' => 'Create Lease',           'description' => 'Draft created',                    'states' => ['draft', 'received']],
            ['title' => 'Register & Assign Zone',  'description' => 'Zone and field officer assigned',  'states' => ['pending_landlord_approval']],
            ['title' => 'Landlord Approval',       'description' => 'Landlord has approved',            'states' => ['approved']],
            ['title' => 'Send Signing Link',       'description' => 'Link sent to tenant',              'states' => ['sent_digital']],
            ['title' => 'OTP Verification',        'description' => 'Tenant verifies with OTP',         'states' => ['pending_otp']],
            ['title' => 'Tenant Reviews Lease',    'description' => 'Tenant reviews document',          'states' => ['pending_tenant_signature']],
            ['title' => 'Tenant Signs',            'description' => 'Tenant has signed',                'states' => ['tenant_signed']],
            ['title' => 'Manager Countersigns',    'description' => 'Manager countersigns',             'states' => ['pending_deposit']],
            ['title' => 'Deposit & Activation',    'description' => 'Lease active',                     'states' => ['active', 'renewal_offered', 'renewal_accepted']],
            ['title' => 'Closed',                  'description' => 'Lease ended or archived',          'states' => ['expired', 'terminated', 'cancelled', 'renewal_declined', 'archived']],
        ];

        $workflowState = $record?->workflow_state ?? 'draft';

        // Build a simple ordering map
        $allStates = ['draft', 'received', 'pending_landlord_approval', 'approved', 'printed', 'checked_out',
            'sent_digital', 'pending_otp', 'pending_tenant_signature', 'returned_unsigned',
            'tenant_signed', 'with_lawyer', 'pending_upload', 'pending_deposit',
            'active', 'renewal_offered', 'renewal_accepted', 'expired', 'terminated', 'cancelled', 'renewal_declined', 'archived'];
        $stateOrder    = array_flip($allStates);
        $currentOrder  = $stateOrder[$workflowState] ?? 999;

        $timestamps = $record
            ? LeaseAuditLog::where('lease_id', $record->id)->whereNotNull('new_state')->orderBy('created_at')->get()
                ->unique('new_state')
                ->mapWithKeys(fn ($l) => [$l->new_state => $l->created_at->format('j M Y, g:i A')])
                ->all()
            : [];

        $steps = [];
        foreach ($defs as $i => $def) {
            $done = $active = $actionRequired = false;
            foreach ($def['states'] as $s) {
                if ($s === $workflowState) { $active = true; break; }
                if (($stateOrder[$s] ?? 999) < $currentOrder) { $done = true; }
            }
            if ($workflowState === 'disputed') {
                $active = in_array('pending_otp', $def['states']) || in_array('pending_tenant_signature', $def['states']);
                $actionRequired = $active;
            }
            if ($workflowState === 'returned_unsigned') {
                $actionRequired = in_array('pending_tenant_signature', $def['states']);
            }
            $ts = null;
            foreach ($def['states'] as $s) {
                if (isset($timestamps[$s])) { $ts = $timestamps[$s]; break; }
            }
            $steps[] = [
                'number'      => $i + 1,
                'title'       => $def['title'],
                'description' => $def['description'],
                'status'      => $actionRequired ? 'action_required' : ($active ? 'active' : ($done ? 'done' : 'pending')),
                'timestamp'   => $ts,
            ];
        }

        return $steps;
    }

    private static function buildProgress($record): int
    {
        if (! $record) return 0;
        $phaseMap = [
            'draft' => 1, 'received' => 1,
            'pending_landlord_approval' => 2, 'approved' => 2,
            'printed' => 3, 'checked_out' => 3, 'sent_digital' => 3,
            'pending_otp' => 3, 'pending_tenant_signature' => 3, 'returned_unsigned' => 3,
            'tenant_signed' => 4, 'with_lawyer' => 4, 'pending_upload' => 4,
            'pending_deposit' => 5,
            'active' => 6, 'renewal_offered' => 6, 'renewal_accepted' => 6,
            'expired' => 7, 'terminated' => 7, 'cancelled' => 7, 'renewal_declined' => 7, 'archived' => 7,
        ];
        $phase = $phaseMap[$record->workflow_state] ?? 1;
        return (int) round(($phase / 7) * 100);
    }

    // ── Timeline HTML ─────────────────────────────────────────────────────────

    private static function buildTimelineHtml($record): string
    {
        if (! $record) return '';

        $logs = LeaseAuditLog::where('lease_id', $record->id)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        if ($logs->isEmpty()) {
            return '<div style="overflow:hidden; border-radius:12px; background:linear-gradient(135deg,#faf8f4 0%,#fff9e8 100%); border-top:1.5px solid rgba(218,165,32,0.35); border-right:1.5px solid rgba(218,165,32,0.35); border-bottom:1.5px solid rgba(218,165,32,0.35); border-left:5px solid #DAA520; padding:20px;">'
                . '<div style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">'
                . '<span style="display:inline-block; width:12px; height:12px; border-radius:3px; background:#DAA520;"></span>'
                . '<span style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:#92700a;">Activity Timeline</span>'
                . '</div>'
                . '<p style="font-size:12px; color:#6b7280; margin:0;">No activity recorded yet.</p>'
                . '</div>';
        }

        $iconMap = [
            'created'           => ['📝', '#DAA520'],
            'state_changed'     => ['🔄', '#1a365d'],
            'approved'          => ['✅', '#059669'],
            'rejected'          => ['❌', '#dc2626'],
            'signed'            => ['✍️', '#7c3aed'],
            'sent'              => ['📱', '#2563eb'],
            'document_uploaded' => ['📎', '#92700a'],
            'note_added'        => ['💬', '#6b7280'],
            'disputed'          => ['⚠️', '#dc2626'],
            'activated'         => ['🎉', '#059669'],
            'expired'           => ['⏰', '#6b7280'],
        ];

        $html = '<div style="overflow:hidden; border-radius:12px; background:linear-gradient(135deg,#faf8f4 0%,#fff9e8 100%); border-top:1.5px solid rgba(218,165,32,0.35); border-right:1.5px solid rgba(218,165,32,0.35); border-bottom:1.5px solid rgba(218,165,32,0.35); border-left:5px solid #DAA520;">'
            . '<div style="padding:20px;">'
            . '<div style="display:flex; align-items:center; gap:8px; margin-bottom:20px;">'
            . '<span style="display:inline-block; width:12px; height:12px; border-radius:3px; background:#DAA520;"></span>'
            . '<span style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:#92700a;">Activity Timeline</span>'
            . '<span style="margin-left:auto; font-size:10px; color:#9ca3af;">Last ' . $logs->count() . ' events</span>'
            . '</div>'
            . '<div style="position:relative;">'
            . '<div style="position:absolute; left:15px; top:0; bottom:0; width:2px; background:linear-gradient(to bottom,#DAA520,rgba(218,165,32,0.1));"></div>'
            . '<div style="display:flex; flex-direction:column; gap:0;">';

        foreach ($logs as $log) {
            $action = $log->action ?? 'state_changed';
            [$icon, $iconColor] = $iconMap[$action] ?? ['🔹', '#DAA520'];
            $user = $log->user?->name ?? 'System';
            $time = $log->created_at->format('j M Y, g:i A');
            $desc = $log->description ?? ($log->new_state ? 'Status changed to: ' . ucwords(str_replace('_', ' ', $log->new_state)) : 'Action recorded');

            $html .= '<div style="display:flex; gap:12px; padding:12px 0; border-bottom:1px solid rgba(218,165,32,0.1);">'
                . '<div style="flex-shrink:0; width:30px; height:30px; border-radius:50%; background:#fff; border:2px solid rgba(218,165,32,0.4); display:flex; align-items:center; justify-content:center; font-size:13px; z-index:1;">' . $icon . '</div>'
                . '<div style="flex:1; min-width:0;">'
                . '<div style="display:flex; align-items:center; gap:8px; margin-bottom:3px;">'
                . '<span style="font-size:12px; font-weight:600; color:#1a365d;">' . htmlspecialchars($user) . '</span>'
                . '<span style="font-size:10px; color:#9ca3af;">' . $time . '</span>'
                . '</div>'
                . '<p style="font-size:11px; color:#374151; margin:0; line-height:1.5;">' . htmlspecialchars($desc) . '</p>'
                . '</div>'
                . '</div>';
        }

        $html .= '</div></div></div></div>';
        return $html;
    }
}
