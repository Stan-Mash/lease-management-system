<?php

namespace App\Filament\Resources\Leases\Schemas;

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

            // ── 1. JOURNEY STATUS PANEL ─────────────────────────────────────────
            // This is the most important section — shows exactly where the lease
            // is, what just happened, and what the admin needs to do next.
            Section::make()
                ->schema([
                    Grid::make(1)->schema([
                        TextEntry::make('_journey_heading')
                            ->label('')
                            ->state(fn ($record) => self::journeyHeading($record))
                            ->html()
                            ->columnSpanFull(),
                    ]),
                ])
                ->extraAttributes([
                    'class' => 'lease-journey-panel',
                    'style' => 'background: linear-gradient(135deg, #faf8f4 0%, #fff9e8 100%); border: 1.5px solid rgba(218,165,32,0.35); border-left: 5px solid #DAA520; border-radius: 12px;',
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

                        TextEntry::make('latest_approval.reviewer.name')
                            ->label('Approved / Actioned By')
                            ->default('—')
                            ->visible(fn ($record) => $record->getLatestApproval() !== null),

                        TextEntry::make('latest_approval.reviewed_at')
                            ->label('Decision Date & Time')
                            ->dateTime('d M Y, H:i')
                            ->placeholder('—')
                            ->visible(fn ($record) => $record->getLatestApproval() !== null),
                    ]),

                    TextEntry::make('latest_approval.rejection_reason')
                        ->label('❌ Rejection Reason')
                        ->visible(fn ($record) => $record->hasBeenRejected())
                        ->color('danger')
                        ->weight('bold')
                        ->columnSpanFull(),

                    TextEntry::make('latest_approval.comments')
                        ->label('Comments from Approver')
                        ->visible(fn ($record) => $record->getLatestApproval()?->comments !== null)
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

            // ── 6. FINANCIAL SUMMARY ──────────────────────────────────────────────
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
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

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
            'tenant_signed' => [
                '🎉', '#059669',
                'Tenant Has Signed!',
                "{$tenant} has digitally signed this lease. The signature has been captured with IP address and timestamp. The lease now needs to go through lawyer review or be uploaded.",
                'Proceed to assign to a lawyer (if required) or advance to "Pending Upload" then confirm deposit.',
            ],
            'with_lawyer' => [
                '⚖️', '#6366f1',
                'With Lawyer for Review',
                'This lease has been sent to a lawyer for legal review. Turnaround time is being tracked.',
                'Wait for lawyer to complete review. Follow up if turnaround time is exceeded.',
            ],
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
}
