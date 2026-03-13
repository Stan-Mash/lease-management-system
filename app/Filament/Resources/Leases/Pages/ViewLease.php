<?php

namespace App\Filament\Resources\Leases\Pages;

use App\Enums\LeaseWorkflowState;
use App\Filament\Resources\Leases\Actions\CancelDisputedLeaseAction;
use App\Services\DigitalSigningService;
use App\Filament\Resources\Leases\Actions\ResolveDisputeAction;
use App\Filament\Resources\Leases\LeaseResource;
use App\Models\DigitalSignature;
use App\Models\Lawyer;
use App\Models\LeaseLawyerTracking;
use App\Models\LeaseWitness;
use App\Notifications\LeaseSentToLawyerNotification;
use App\Notifications\LeaseApprovalRequestedNotification;
use App\Services\DocumentUploadService;
use App\Services\LandlordApprovalService;
use App\Services\PdfOverlayService;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ViewLease extends ViewRecord
{
    protected static string $resource = LeaseResource::class;

    /**
     * Holds the base64 PNG drawn on the countersign canvas pad.
     * Set via $wire.set('canvasPngB64', ...) from Alpine.js before the action fires.
     */
    public string $canvasPngB64 = '';

    public function getHeading(): string
    {
        return $this->record->reference_number ?? 'Lease';
    }

    public function getSubheading(): ?string
    {
        $tenant = $this->record->tenant?->names ?? '';
        $state = ucwords(str_replace('_', ' ', $this->record->workflow_state ?? ''));

        return $tenant ? "{$tenant} — {$state}" : $state;
    }

    public function getBreadcrumbs(): array
    {
        return [
            LeaseResource::getUrl() => 'Leases',
            '' => $this->record->reference_number ?? 'View',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [

            // ── EDIT (draft / received only) ────────────────────────────────
            EditAction::make()
                ->label('Edit Lease')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->visible(fn () => in_array($this->record->workflow_state, ['draft', 'received'])),

            // ── STEP 1: REQUEST LANDLORD APPROVAL ───────────────────────────
            Action::make('requestApproval')
                ->label('Send for Landlord Approval')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(
                    // Only visible on landlord-route leases — manager-route leases skip this step entirely.
                    fn () => $this->record->usesLandlordRoute()
                        && $this->record->workflow_state === 'draft'
                        && ! $this->record->hasPendingApproval(),
                )
                ->requiresConfirmation()
                ->modalHeading('Send for Landlord Approval')
                ->modalDescription(
                    fn () => 'This will notify the landlord ('
                        . ($this->record->landlord?->names ?? 'Landlord')
                        . ') to review and approve the lease for '
                        . ($this->record->tenant?->names ?? 'tenant')
                        . '. An SMS and email will be sent to them.',
                )
                ->modalSubmitActionLabel('Yes, Send for Approval')
                ->action(function () {
                    $result = LandlordApprovalService::requestApproval($this->record, 'both');
                    if ($result['success']) {
                        Notification::make()->success()
                            ->title('Approval Request Sent')
                            ->body('The landlord has been notified and is waiting to approve.')
                            ->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                    } else {
                        Notification::make()->danger()
                            ->title('Could Not Send Request')
                            ->body($result['message'])
                            ->send();
                    }
                }),

            // ── STEP 1b: APPROVE LEASE (admin on behalf of landlord) ────────
            Action::make('approveLease')
                ->label('Approve Lease')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(
                    // Only for landlord-route leases in pre-signing approval states.
                    // Manager-route leases move directly to SENT_DIGITAL without a landlord approval step.
                    fn () => $this->record->usesLandlordRoute()
                        && in_array($this->record->workflow_state, ['pending_landlord_approval', 'draft'])
                        && ! ($this->record->workflow_state === 'draft' && $this->record->hasPendingApproval()),
                )
                /** @phpstan-ignore-next-line */
                ->schema([
                    Textarea::make('comments')
                        ->label('Approval Comments (Optional)')
                        ->placeholder('Add any notes about this approval...')
                        ->rows(3)
                        ->maxLength(1000),
                ])
                ->modalHeading('Approve This Lease')
                ->modalDescription(
                    fn () => 'Approving on behalf of the landlord ('
                        . ($this->record->landlord?->names ?? 'Landlord')
                        . '). Once approved, you can send the digital signing link to '
                        . ($this->record->tenant?->names ?? 'the tenant')
                        . ' (' . ($this->record->tenant?->mobile_number ?? '—') . ').',
                )
                ->modalSubmitActionLabel('Approve Lease')
                ->action(function (array $data) {
                    // If already approved (e.g. page was stale), just redirect
                    if ($this->record->workflow_state === 'approved') {
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));

                        return;
                    }
                    $result = LandlordApprovalService::approveLease(
                        $this->record,
                        $data['comments'] ?? null,
                        'email',
                    );
                    if ($result['success']) {
                        Notification::make()->success()
                            ->title('Lease Approved ✅')
                            ->body('Next: Click "Send Signing Link to Tenant" to notify the tenant via SMS.')
                            ->duration(10000)
                            ->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                    } else {
                        Notification::make()->danger()
                            ->title('Approval Failed')
                            ->body($result['message'])
                            ->send();
                    }
                }),

            // ── STEP 1c: REJECT LEASE ────────────────────────────────────────
            Action::make('rejectLease')
                ->label('Reject Lease')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->workflow_state === 'pending_landlord_approval')
                /** @phpstan-ignore-next-line */
                ->schema([
                    TextInput::make('rejection_reason')
                        ->label('Reason for Rejection')
                        ->placeholder('e.g., Rent amount too high, wrong dates...')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('comments')
                        ->label('Additional Comments (Optional)')
                        ->placeholder('Explain what needs to be changed...')
                        ->rows(3)
                        ->maxLength(1000),
                ])
                ->modalHeading('Reject This Lease')
                ->modalDescription('The landlord is rejecting this lease. A reason is required. The tenant will be notified.')
                ->modalSubmitActionLabel('Reject Lease')
                ->action(function (array $data) {
                    $result = LandlordApprovalService::rejectLease(
                        $this->record,
                        $data['rejection_reason'],
                        $data['comments'] ?? null,
                        'email',
                    );
                    if ($result['success']) {
                        Notification::make()->warning()
                            ->title('Lease Rejected')
                            ->body('The tenant has been notified. Edit the lease and re-submit for approval.')
                            ->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                    } else {
                        Notification::make()->danger()
                            ->title('Rejection Failed')
                            ->body($result['message'])
                            ->send();
                    }
                }),

            // ── RESOLVE / CANCEL DISPUTE ─────────────────────────────────────
            ResolveDisputeAction::make(),
            CancelDisputedLeaseAction::make(),

            // ── STEP 2: SEND DIGITAL SIGNING LINK (first send) ──────────────
            // Visible for:
            //   • Landlord-route leases that have been approved (normal path)
            //   • Manager-route leases still in draft (approval step is skipped;
            //     clicking this auto-approves and sends in one action)
            Action::make('sendDigital')
                ->label(fn () => $this->record->usesManagerRoute() && $this->record->workflow_state === 'draft'
                    ? 'Approve & Send Signing Link'
                    : 'Send Signing Link to Tenant')
                ->icon('heroicon-o-device-phone-mobile')
                ->color('info')
                ->visible(
                    fn () => (
                        $this->record->workflow_state === 'approved'
                        || ($this->record->usesManagerRoute() && $this->record->workflow_state === 'draft')
                    ) && $this->record->signing_mode === 'digital',
                )
                ->modalHeading(fn () => $this->record->usesManagerRoute() && $this->record->workflow_state === 'draft'
                    ? 'Approve & Send Signing Link to Tenant'
                    : 'Send Signing Link to Tenant')
                ->modalDescription(
                    fn () => $this->record->usesManagerRoute() && $this->record->workflow_state === 'draft'
                        ? 'This will approve the lease internally (no landlord approval required for manager-route leases) and immediately send the tenant a secure digital signing link (valid 72 hours).'
                        : 'The tenant will receive a secure link to digitally sign their lease (valid 72 hours). They will open the link, request a 6-digit OTP, verify it, and draw their signature.',
                )
                ->modalSubmitActionLabel('Send Now')
                /** @phpstan-ignore-next-line */
                ->schema([
                    Select::make('send_method')
                        ->label('Send Via')
                        ->options(function () {
                            $phone = $this->record->tenant?->mobile_number
                                ?: config('services.sms_redirect_to', '');
                            $email = $this->record->tenant?->email_address
                                ?: config('mail.redirect_to', '');

                            return [
                                'sms'   => '📱 SMS' . ($phone ? ' — ' . $phone : ''),
                                'email' => '✉️ Email' . ($email ? ' — ' . $email : ''),
                                'both'  => '📱 + ✉️ Both SMS & Email',
                            ];
                        })
                        ->default('both')
                        ->required()
                        ->helperText('Choose how to send the signing link to the tenant'),
                ])
                ->action(function (array $data) {
                    try {
                        $method = $data['send_method'] ?? 'sms';

                        // Manager-route leases skip landlord approval — silently approve first
                        // so DigitalSigningService::initiate() can transition to SENT_DIGITAL.
                        if ($this->record->usesManagerRoute() && $this->record->workflow_state === 'draft') {
                            $this->record->transitionTo(LeaseWorkflowState::APPROVED);
                        }

                        $this->record->sendDigitalSigningLink($method);

                        $sentTo = match ($method) {
                            'sms' => 'SMS to ' . ($this->record->tenant?->mobile_number ?? 'tenant'),
                            'email' => 'email to ' . ($this->record->tenant?->email_address ?? 'tenant'),
                            'both' => 'SMS & email to ' . ($this->record->tenant?->names ?? 'tenant'),
                            default => 'tenant',
                        };

                        Notification::make()->success()
                            ->title('Signing Link Sent ✅')
                            ->body("Link sent via {$sentTo}. Status is now \"Signing Link Sent\". The tenant will receive an OTP when they open the link.")
                            ->duration(7000)
                            ->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (Exception $e) {
                        Notification::make()->danger()
                            ->title('Failed to Send Signing Link')
                            ->body('Error: ' . $e->getMessage())
                            ->send();
                    }
                }),

            // ── RESEND LINK (tenant / advocate / landlord) ───────────────────
            Action::make('resendSigningLink')
                ->label('Resend Link')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(
                    fn () => in_array($this->record->workflow_state, [
                        'sent_digital',
                        'pending_otp',
                        'pending_tenant_signature',
                        'tenant_signed',
                        'pending_advocate',
                        'with_lawyer',
                        'pending_landlord_pm',
                    ]) && $this->record->signing_mode === 'digital',
                )
                ->modalHeading('Resend Link')
                ->modalDescription('Send a fresh link to the relevant party (tenant, advocate, or landlord). Use this if the link expired or was not received.')
                ->modalSubmitActionLabel('Resend Now')
                /** @phpstan-ignore-next-line */
                ->schema([
                    Select::make('send_method')
                        ->label('Send Via')
                        ->options(function () {
                            $phone = $this->record->tenant?->mobile_number
                                ?: config('services.sms_redirect_to', '');
                            $email = $this->record->tenant?->email_address
                                ?: config('mail.redirect_to', '');

                            return [
                                'sms'   => '📱 SMS' . ($phone ? ' — ' . $phone : ''),
                                'email' => '✉️ Email' . ($email ? ' — ' . $email : ''),
                                'both'  => '📱 + ✉️ Both SMS & Email',
                            ];
                        })
                        ->default('both')
                        ->required(),
                ])
                ->action(function (array $data) {
                    try {
                        $lease = $this->record;
                        $state = $lease->workflow_state;
                        $method = $data['send_method'] ?? 'sms';

                        // 1. Tenant resend (digital signing link)
                        if (in_array($state, [
                            'sent_digital',
                            'pending_otp',
                            'pending_tenant_signature',
                            'tenant_signed',
                        ], true)) {
                            $lease->resendDigitalSigningLink($method);

                            $sentTo = match ($method) {
                                'sms'   => 'SMS to ' . ($lease->tenant?->mobile_number ?? 'tenant'),
                                'email' => 'email to ' . ($lease->tenant?->email_address ?? 'tenant'),
                                'both'  => 'SMS & email to ' . ($lease->tenant?->names ?? 'tenant'),
                                default => 'tenant',
                            };

                            Notification::make()->success()
                                ->title('Link resent to Tenant ✅')
                                ->body("New link sent via {$sentTo}.")
                                ->send();

                            $this->redirect($this->getResource()::getUrl('view', ['record' => $lease]));

                            return;
                        }

                        // 2. Advocate resend (lawyer portal link, supports guest advocates)
                        if (in_array($state, ['pending_advocate', 'with_lawyer'], true)) {
                            $tracking = $lease->lawyerTrackings()
                                ->withLawyer()
                                ->latest('sent_at')
                                ->first();

                            if (! $tracking) {
                                throw new \Exception('Advocate tracking record not found.');
                            }

                            $tracking->update([
                                'lawyer_link_expires_at' => now()->addDays(3),
                            ]);

                            $lawyer = $tracking->lawyer;
                            // Attempt to find an email from linked lawyer or lease (guest advocate)
                            $email = $lawyer?->email
                                ?? $lease->tenant_advocate_email
                                ?? null;

                            if (! $email) {
                                throw new \Exception('No email address found for the advocate (guest or linked).');
                            }

                            $notification = new LeaseSentToLawyerNotification(
                                $lease,
                                $lawyer ?? new \App\Models\Lawyer(), // allow null-like, not used for routing when guest
                                $tracking->fresh(),
                                false, // portal link (no PDF attachment)
                            );

                            if ($lawyer) {
                                $lawyer->notify($notification);
                            } else {
                                \Illuminate\Support\Facades\Notification::route('mail', $email)->notify($notification);
                            }

                            Notification::make()->success()
                                ->title('Link resent to Advocate ✅')
                                ->body('A fresh portal link has been emailed to the advocate.')
                                ->send();

                            $this->redirect($this->getResource()::getUrl('view', ['record' => $lease]));

                            return;
                        }

                        // 3. Landlord resend (approval portal link)
                        if (in_array($state, ['pending_landlord_pm', 'pending_landlord_approval'], true)) {
                            /** @var \App\Models\LeaseApproval|null $approval */
                            $approval = $lease->approvals()->pending()->latest('created_at')->first();
                            if (! $approval) {
                                throw new \Exception('Landlord approval record not found.');
                            }

                            // Regenerate token + extend expiry
                            $approval->generateToken();

                            if (! $lease->landlord) {
                                throw new \Exception('No landlord linked to this lease.');
                            }

                            $approvalUrl = $approval->publicUrl();
                            $lease->landlord->notify(new LeaseApprovalRequestedNotification($lease, $approvalUrl));

                            Notification::make()->success()
                                ->title('Link resent to Landlord ✅')
                                ->body('A fresh approval link has been emailed to the landlord.')
                                ->send();

                            $this->redirect($this->getResource()::getUrl('view', ['record' => $lease]));

                            return;
                        }

                        throw new \Exception("Cannot resend link for current state: {$state}");
                    } catch (\Exception $e) {
                        Notification::make()->danger()
                            ->title('Failed to resend link')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            // ── ASSIGN & SEND TO ADVOCATE (pending_advocate → with_lawyer) ───
            Action::make('assignAndSendToAdvocate')
                ->label('Assign & Send to Advocate')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(
                    fn () => $this->record->workflow_state === 'pending_advocate'
                        && auth()->user()?->can('send_to_lawyer'),
                )
                ->modalHeading('Assign & Send to Advocate')
                ->modalDescription('Select the advocate and add any instructions. They will receive an email with a secure link to download the lease and upload the stamped copy.')
                ->modalSubmitActionLabel('Send')
                /** @phpstan-ignore-next-line */
                ->schema([
                    Select::make('lawyer_id')
                        ->label('Advocate')
                        ->options(fn () => Lawyer::active()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    Textarea::make('advocate_instructions')
                        ->label('Instructions for advocate (optional)')
                        ->placeholder('e.g. Please witness Section 7 and return by Friday...')
                        ->rows(3)
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    $lawyer = Lawyer::find($data['lawyer_id']);
                    if (! $lawyer) {
                        Notification::make()->danger()->title('Invalid advocate')->send();
                        return;
                    }
                    $notes = $data['advocate_instructions'] ?? null;

                    $tracking = LeaseLawyerTracking::create([
                        'lease_id' => $this->record->id,
                        'lawyer_id' => $lawyer->id,
                        'sent_method' => 'email',
                        'sent_by' => auth()->id(),
                        'sent_notes' => $notes,
                        'status' => 'sent',
                        'sent_at' => now(),
                    ]);

                    $token = LeaseLawyerTracking::generateToken();
                    $tracking->update([
                        'lawyer_link_token' => $token,
                        'lawyer_link_expires_at' => now()->addDays(14),
                        'sent_via_portal_link' => true,
                    ]);

                    try {
                        \Illuminate\Support\Facades\Notification::route('mail', $lawyer->email)
                            ->notify(new LeaseSentToLawyerNotification(
                                $this->record,
                                $lawyer,
                                $tracking->fresh(),
                                false,
                            ));
                    } catch (Exception $e) {
                        report($e);
                        Notification::make()->warning()
                            ->title('Sent to advocate')
                            ->body('Tracking recorded, but email could not be sent: ' . $e->getMessage())
                            ->send();
                    }

                    $this->record->transitionTo(LeaseWorkflowState::WITH_LAWYER);

                    Notification::make()->success()
                        ->title('Sent to Advocate')
                        ->body('Lease assigned to ' . $lawyer->name . ($lawyer->firm ? " ({$lawyer->firm})" : '') . '. They have been emailed a secure portal link for download and upload.')
                        ->send();
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),

            // ── SEND TO LAWYER (tenant_signed / pending_advocate / pending_witness → with_lawyer) ───
            Action::make('sendToLawyer')
                ->label('Send to Lawyer')
                ->icon('heroicon-o-scale')
                ->color('gray')
                ->visible(
                    fn () => in_array($this->record->workflow_state, [
                        'tenant_signed',    // direct path (if state lands here)
                        'pending_advocate', // commercial: next after tenant signs
                        'pending_witness',  // residential_major: next after tenant signs
                    ]) && auth()->user()?->can('send_to_lawyer'),
                )
                ->modalHeading('Send Lease to Lawyer')
                ->modalDescription('Select the advocate and how to send the lease. They can receive the PDF by email or a secure link to download and upload the stamped copy.')
                ->modalSubmitActionLabel('Send')
                /** @phpstan-ignore-next-line */
                ->schema([
                    Select::make('lawyer_id')
                        ->label('Lawyer / Advocate')
                        ->options(fn () => Lawyer::active()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    Select::make('send_method')
                        ->label('Send via')
                        ->options([
                            'email_pdf' => 'Email with PDF attached',
                            'email_link' => 'Email with portal link (lawyer downloads & uploads stamped PDF)',
                            'physical' => 'Physical (hand delivery / courier)',
                        ])
                        ->default('email_link')
                        ->required(),
                    Textarea::make('sent_notes')
                        ->label('Notes (optional)')
                        ->placeholder('Instructions or reference for the advocate...')
                        ->rows(2)
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    $lawyer = Lawyer::find($data['lawyer_id']);
                    if (! $lawyer) {
                        Notification::make()->danger()->title('Invalid lawyer')->send();
                        return;
                    }
                    $sendMethod = $data['send_method'] ?? 'email_link';
                    $notes = $data['sent_notes'] ?? null;

                    $tracking = LeaseLawyerTracking::create([
                        'lease_id' => $this->record->id,
                        'lawyer_id' => $lawyer->id,
                        'sent_method' => $sendMethod === 'physical' ? 'physical' : 'email',
                        'sent_by' => auth()->id(),
                        'sent_notes' => $notes,
                        'status' => 'sent',
                        'sent_at' => now(),
                    ]);

                    if ($sendMethod === 'email_link') {
                        $token = LeaseLawyerTracking::generateToken();
                        $tracking->update([
                            'lawyer_link_token' => $token,
                            'lawyer_link_expires_at' => now()->addDays(14),
                            'sent_via_portal_link' => true,
                        ]);
                    }

                    if ($sendMethod === 'email_pdf' || $sendMethod === 'email_link') {
                        try {
                            \Illuminate\Support\Facades\Notification::route('mail', $lawyer->email)
                                ->notify(new LeaseSentToLawyerNotification(
                                    $this->record,
                                    $lawyer,
                                    $tracking->fresh(),
                                    $sendMethod === 'email_pdf',
                                ));
                        } catch (Exception $e) {
                            report($e);
                            Notification::make()->warning()
                                ->title('Sent to lawyer')
                                ->body('Tracking recorded, but email could not be sent: ' . $e->getMessage())
                                ->send();
                        }
                    }

                    $this->record->transitionTo(LeaseWorkflowState::WITH_LAWYER);

                    Notification::make()->success()
                        ->title('Sent to Lawyer')
                        ->body('Lease sent to ' . $lawyer->name . ($lawyer->firm ? " ({$lawyer->firm})" : '') . '.')
                        ->send();
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),

            // ── MARK RETURNED FROM LAWYER (with_lawyer → pending_upload) ─────
            Action::make('markReturnedFromLawyer')
                ->label('Mark Returned from Lawyer')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(
                    fn () => $this->record->workflow_state === 'with_lawyer'
                        && $this->record->signing_mode !== 'digital'
                        && $this->record->lawyerTrackings()->where('status', 'sent')->exists()
                        && auth()->user()?->can('receive_from_lawyer'),
                )
                ->modalHeading('Mark Returned from Lawyer')
                ->modalDescription('Record that the advocate has returned the lease, along with their certification details for the legal record.')
                ->modalSubmitActionLabel('Mark Returned')
                /** @phpstan-ignore-next-line */
                ->schema([
                    Select::make('returned_method')
                        ->label('Returned via')
                        ->options(['email' => 'Email', 'physical' => 'Physical'])
                        ->required(),

                    // ── Advocate Certification (Track 2) ──────────────────────
                    Select::make('certification_type')
                        ->label('What did the advocate do?')
                        ->options([
                            'review'       => 'Review Only — advised on content, no signature',
                            'witness'      => 'Witness — signed as a witness on the document',
                            'attestation'  => 'Attestation — formally attested / certified the document',
                            'registration' => 'Registration — prepared and submitted for Lands Registry',
                        ])
                        ->required()
                        ->helperText('This is your legal certification record (Track 2).'),

                    TextInput::make('advocate_lsk_number')
                        ->label('Advocate LSK Number')
                        ->placeholder('e.g. LSK/2024/01234')
                        ->helperText('LSK practising certificate number — required for attestation or registration')
                        ->maxLength(50),

                    \Filament\Forms\Components\DatePicker::make('certified_at')
                        ->label('Date of Certification')
                        ->default(now()->format('Y-m-d'))
                        ->maxDate(now())
                        ->helperText('Date the advocate signed or certified the document'),

                    FileUpload::make('stamped_pdf')
                        ->label('Attested / Stamped PDF (optional)')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(20480)
                        ->disk('local')
                        ->directory('temp-lawyer-returns')
                        ->helperText('Upload the scanned physical copy with advocate signature if available'),

                    Textarea::make('returned_notes')
                        ->label('Notes (optional)')
                        ->rows(2)
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    $tracking = $this->record->lawyerTrackings()->where('status', 'sent')->latest()->first();
                    if (! $tracking) {
                        Notification::make()->danger()->title('No tracking found')->send();
                        return;
                    }

                    $notes = $data['returned_notes'] ?? null;
                    $physicalDocId = null;

                    if (! empty($data['stamped_pdf'])) {
                        $fullPath = storage_path('app/' . $data['stamped_pdf']);
                        if (file_exists($fullPath)) {
                            $uploadService = new DocumentUploadService();
                            $file = new \Illuminate\Http\UploadedFile(
                                $fullPath,
                                basename($fullPath),
                                mime_content_type($fullPath),
                                null,
                                true,
                            );
                            $doc = $uploadService->upload(
                                $file,
                                $this->record->id,
                                'lawyer_stamped',
                                'Attested lease from advocate – ' . ($this->record->reference_number ?? ''),
                                $notes,
                                now()->format('Y-m-d'),
                                auth()->id(),
                            );
                            if ($this->record->unit_code) {
                                $doc->update(['unit_code' => $this->record->unit_code]);
                            }
                            $physicalDocId = $doc->id;
                            @unlink($fullPath);
                        }
                    }

                    $tracking->markAsReturned($data['returned_method'], auth()->id(), $notes);

                    // Record advocate certification (Track 2)
                    $tracking->recordCertification(
                        type: $data['certification_type'],
                        lskNumber: $data['advocate_lsk_number'] ?? null,
                        certifiedAt: ! empty($data['certified_at']) ? \Carbon\Carbon::parse($data['certified_at']) : now(),
                        physicalCopyDocumentId: $physicalDocId,
                    );

                    $this->record->transitionTo(LeaseWorkflowState::PENDING_UPLOAD);

                    Notification::make()->success()
                        ->title('Marked Returned from Lawyer')
                        ->body('Advocate certification recorded. Lease status set to Pending Upload.')
                        ->send();
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),

            // ── RECORD WITNESS ────────────────────────────────────────────────
            Action::make('recordWitness')
                ->label('Record Witness')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->visible(
                    fn () => in_array($this->record->workflow_state, [
                        'tenant_signed', 'with_lawyer', 'pending_upload', 'pending_deposit', 'active',
                    ]) && auth()->user()?->canManageLeases(),
                )
                ->modalHeading('Record Witness Declaration')
                ->modalDescription('Record the identity of the person who was physically present and witnessed the signing. This creates the formal witness trail for the legal record.')
                ->modalSubmitActionLabel('Save Witness Record')
                /** @phpstan-ignore-next-line */
                ->schema([
                    Select::make('witnessed_party')
                        ->label('Who did this person witness signing?')
                        ->options([
                            'tenant' => 'Tenant (Lessee) — the person signing as Lessee',
                            'lessor' => 'Lessor / Property Manager — signing on behalf of Chabrin',
                        ])
                        ->required(),

                    Select::make('witness_type')
                        ->label('Type of Witness')
                        ->options([
                            'staff'    => 'Chabrin Staff Member',
                            'advocate' => 'LSK Advocate',
                            'external' => 'External Witness',
                        ])
                        ->default('staff')
                        ->required()
                        ->live(),

                    TextInput::make('witnessed_by_name')
                        ->label('Witness Full Name')
                        ->default(fn () => Auth::user()?->name ?? '')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('witnessed_by_title')
                        ->label('Witness Title / Role')
                        ->placeholder('e.g. Lease Officer, Chabrin Agencies Ltd')
                        ->default(fn () => 'Chabrin Agencies Ltd')
                        ->maxLength(255),

                    TextInput::make('lsk_number')
                        ->label('LSK Number (if advocate)')
                        ->placeholder('e.g. LSK/2024/01234')
                        ->maxLength(50)
                        ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('witness_type') === 'advocate'),

                    Textarea::make('notes')
                        ->label('Notes (optional)')
                        ->rows(2)
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    LeaseWitness::create([
                        'lease_id'             => $this->record->id,
                        'witnessed_party'      => $data['witnessed_party'],
                        'witnessed_by_user_id' => auth()->id(),
                        'witnessed_by_name'    => $data['witnessed_by_name'],
                        'witnessed_by_title'   => $data['witnessed_by_title'] ?? null,
                        'witness_type'         => $data['witness_type'],
                        'lsk_number'           => $data['lsk_number'] ?? null,
                        'witnessed_at'         => now(),
                        'ip_address'           => request()->ip(),
                        'notes'                => $data['notes'] ?? null,
                    ]);

                    \App\Models\LeaseAuditLog::create([
                        'lease_id'   => $this->record->id,
                        'action'     => 'witness_recorded',
                        'performed_by' => auth()->id(),
                        'details'    => json_encode([
                            'witnessed_party'   => $data['witnessed_party'],
                            'witness_name'      => $data['witnessed_by_name'],
                            'witness_type'      => $data['witness_type'],
                        ]),
                    ]);

                    Notification::make()->success()
                        ->title('Witness Recorded')
                        ->body($data['witnessed_by_name'] . ' recorded as witness for the ' . ($data['witnessed_party'] === 'tenant' ? 'Tenant' : 'Lessor') . '.')
                        ->send();
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),

            // ── SEND LANDLORD SIGNING LINK (Route 1 — after first advocate cert) ──
            // Visible on landlord-route leases when it's time for the landlord to sign as lessor.
            Action::make('sendLandlordSigningLink')
                ->label('Send Landlord Signing Link')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(
                    fn () => $this->record->usesLandlordRoute()
                        && $this->record->workflow_state === 'pending_landlord_pm'
                        && auth()->user()?->canManageLeases(),
                )
                ->requiresConfirmation()
                ->modalHeading('Send Signing Link to Landlord')
                ->modalDescription(
                    fn () => 'This will send a secure signing link to '
                        . ($this->record->landlord?->names ?? 'the landlord')
                        . ' so they can sign the lease as the lessor party (with witness). An email and SMS will be sent.',
                )
                ->modalSubmitActionLabel('Yes, Send Link')
                ->action(function () {
                    $lease = $this->record;
                    if (! $lease->landlord) {
                        Notification::make()->danger()
                            ->title('No Landlord Linked')
                            ->body('Please link a landlord to this lease before sending a signing link.')
                            ->send();
                        return;
                    }
                    $result = \App\Services\LandlordApprovalService::requestApproval($lease, 'both');
                    if ($result['success']) {
                        Notification::make()->success()
                            ->title('Signing Link Sent')
                            ->body('The landlord has been notified and can now sign the lease.')
                            ->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $lease]));
                    } else {
                        Notification::make()->danger()
                            ->title('Could Not Send Link')
                            ->body($result['message'] ?? 'An error occurred.')
                            ->send();
                    }
                }),

            // ── COUNTERSIGN & ACTIVATE (Route 2 — manager countersigns after first advocate cert) ───
            Action::make('countersignActivate')
                ->label('Countersign & Activate Lease')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(
                    // Only shown on manager-route leases — landlord-route uses the landlord portal instead.
                    fn () => $this->record->usesManagerRoute()
                        && in_array($this->record->workflow_state, [
                            'tenant_signed',
                            'pending_upload',        // lawyer returned signed copy via portal
                            'pending_landlord_pm',   // after first advocate cert
                            'pending_deposit',       // legacy: after advocate signs
                        ], true)
                        && ! $this->record->countersigned_at
                        && auth()->user()?->canManageLeases(),
                )
                ->modalHeading('Countersign & Activate Lease')
                ->modalDescription(
                    fn () => 'Countersign lease '
                        . ($this->record->reference_number ?? '')
                        . ' for ' . ($this->record->tenant?->names ?? 'the tenant')
                        . '. Draw your signature below or use your saved one. The tenant will receive the executed lease by email.',
                )
                ->modalWidth('2xl')
                ->stickyModalHeader()
                ->stickyModalFooter()
                ->modalSubmitActionLabel('Countersign & Activate')
                ->modalSubmitAction(
                    fn ($action) => $action->color('success')->icon('heroicon-o-check-badge'),
                )
                /** @phpstan-ignore-next-line */
                ->schema([
                    TextInput::make('countersigned_by')
                        ->label('Your Full Name')
                        ->default(fn () => Auth::user()?->name ?? '')
                        ->required()
                        ->maxLength(255)
                        ->helperText('This name will be printed on the lease document.'),

                    // ── Signature mode selector ───────────────────────────────
                    // 'draw'  → manager draws on canvas pad (always available)
                    // 'saved' → manager uses their stored signature (only if uploaded)
                    // This is a real Filament Select so its value reliably reaches $data.
                    \Filament\Forms\Components\Select::make('signature_mode')
                        ->label('Signature Method')
                        ->options(function () {
                            $opts = ['draw' => '✏️ Draw on pad now'];
                            if (auth()->user()?->signature_image_encrypted) {
                                $opts['saved'] = '✅ Use my saved signature';
                            }

                            return $opts;
                        })
                        ->default(function () {
                            return auth()->user()?->signature_image_encrypted ? 'saved' : 'draw';
                        })
                        ->required()
                        ->live()
                        ->selectablePlaceholder(false)
                        ->helperText('Choose how you want to sign this lease.'),

                    // ── Canvas pad (shown when mode = draw) ───────────────────
                    // The drawn PNG is pushed into the Livewire property
                    // $canvasPngB64 via $wire.set() on every stroke-end AND
                    // again on the submit button click, so the value is always
                    // fresh when the server-side action reads $this->canvasPngB64.
                    \Filament\Forms\Components\Placeholder::make('signature_pad_ui')
                        ->label('Draw Your Signature')
                        ->visible(fn ($get) => $get('signature_mode') === 'draw')
                        ->content(function () {
                            return new \Illuminate\Support\HtmlString(
                                <<<'HTML'
<div
    x-data="{
        drawing: false,
        lastX: 0, lastY: 0,
        hasStrokes: false,
        startDraw(e) {
            this.drawing = true; this.hasStrokes = true;
            const p = this.getPos(e); this.lastX = p.x; this.lastY = p.y;
        },
        draw(e) {
            if (!this.drawing) return;
            e.preventDefault();
            const ctx = $refs.sigCanvas.getContext('2d');
            const p = this.getPos(e);
            ctx.beginPath(); ctx.moveTo(this.lastX, this.lastY);
            ctx.lineTo(p.x, p.y);
            ctx.strokeStyle = '#1a365d'; ctx.lineWidth = 2.5;
            ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.stroke();
            this.lastX = p.x; this.lastY = p.y;
        },
        stopDraw() {
            this.drawing = false;
            this.syncToLivewire();
        },
        getPos(e) {
            const rect = $refs.sigCanvas.getBoundingClientRect();
            const src = e.touches ? e.touches[0] : e;
            return { x: src.clientX - rect.left, y: src.clientY - rect.top };
        },
        clearPad() {
            const c = $refs.sigCanvas;
            c.getContext('2d').clearRect(0, 0, c.width, c.height);
            this.hasStrokes = false;
            $wire.set('canvasPngB64', '');
        },
        syncToLivewire() {
            if (!this.hasStrokes) return;
            const b64 = $refs.sigCanvas.toDataURL('image/png').split(',')[1];
            $wire.set('canvasPngB64', b64);
        }
    }"
    @mouseup.window="stopDraw()"
    @touchend.window="stopDraw()"
>
    <div style="background:linear-gradient(135deg,#faf8f4 0%,#fff9e8 100%);border:1.5px solid rgba(218,165,32,0.35);border-left:5px solid #DAA520;border-radius:10px;padding:4px;">
        <canvas x-ref="sigCanvas" width="600" height="160"
            style="display:block;width:100%;height:160px;cursor:crosshair;border-radius:7px;background:#fff;touch-action:none;"
            @mousedown="startDraw($event)"
            @mousemove="draw($event)"
            @mouseup="stopDraw()"
            @touchstart.prevent="startDraw($event)"
            @touchmove.prevent="draw($event)"
            @touchend="stopDraw()">
        </canvas>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px;">
        <span style="font-size:11px;color:#9ca3af;">✍️ Sign inside the box above using your mouse or finger</span>
        <button type="button" @click="clearPad()"
            style="font-size:11px;color:#b8960a;font-weight:600;background:none;border:none;cursor:pointer;text-decoration:underline;">
            Clear
        </button>
    </div>
</div>
HTML
                            );
                        }),

                    // ── Saved signature preview (shown when mode = saved) ─────
                    \Filament\Forms\Components\Placeholder::make('saved_sig_preview')
                        ->label('Your Saved Signature')
                        ->visible(fn ($get) => $get('signature_mode') === 'saved')
                        ->content(function () {
                            $dataUri = auth()->user()?->signature_image_data_uri ?? '';

                            return new \Illuminate\Support\HtmlString(
                                '<div style="background:linear-gradient(135deg,#faf8f4 0%,#fff9e8 100%);'
                                . 'border:1.5px solid rgba(218,165,32,0.35);border-left:5px solid #DAA520;'
                                . 'border-radius:10px;padding:16px 20px;">'
                                . '<p style="font-size:12px;color:#92700a;margin:0 0 10px;font-weight:600;'
                                . 'text-transform:uppercase;letter-spacing:.5px;">Signature on file</p>'
                                . ($dataUri
                                    ? '<img src="' . e($dataUri) . '" alt="Saved signature" '
                                    . 'style="max-height:80px;border:1px solid #e2d9c8;border-radius:6px;background:#fff;padding:4px;" />'
                                    : '<p style="font-size:12px;color:#6b7280;">No signature image on file.</p>')
                                . '<p style="font-size:11px;color:#6b7280;margin:10px 0 0;">'
                                . 'This signature will be stamped on the executed lease document.</p>'
                                . '</div>'
                            );
                        }),

                    // Save-to-profile toggle (only relevant when drawing)
                    \Filament\Forms\Components\Toggle::make('save_signature_to_profile')
                        ->label('Save this drawn signature to my profile for future use')
                        ->visible(fn ($get) => $get('signature_mode') === 'draw')
                        ->default(false)
                        ->helperText('Your drawn signature will be stored encrypted on your account.'),

                    Textarea::make('countersign_notes')
                        ->label('Notes (Optional)')
                        ->placeholder('e.g. Deposit received, keys handed over...')
                        ->rows(2)
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    try {
                        $user = auth()->user();
                        $mode = $data['signature_mode'] ?? 'draw';

                        if ($mode === 'saved') {
                            // ── Use the manager's saved uploaded signature ────
                            if (! $user?->signature_image_encrypted) {
                                Notification::make()->danger()
                                    ->title('No Saved Signature')
                                    ->body('No saved signature found on your profile. Please switch to "Draw on pad" instead.')
                                    ->send();

                                return;
                            }
                            DigitalSigningService::stampManagerSignature($this->record, $user);
                        } else {
                            // ── Canvas pad draw ───────────────────────────────
                            // Alpine's stopDraw() calls $wire.set('canvasPngB64', b64)
                            // after every stroke, so by the time the action fires
                            // $this->canvasPngB64 already holds the latest PNG.
                            $padPng = trim($this->canvasPngB64);

                            if ($padPng === '') {
                                Notification::make()->danger()
                                    ->title('Signature Required')
                                    ->body('Please draw your signature in the box before submitting.')
                                    ->send();

                                return;
                            }

                            $saveToProfile = (bool) ($data['save_signature_to_profile'] ?? false);
                            if (method_exists(DigitalSigningService::class, 'stampManagerSignatureFromPng')) {
                                DigitalSigningService::stampManagerSignatureFromPng($this->record, $user, $padPng, $saveToProfile);
                            } else {
                                $pngBytes = base64_decode($padPng, true);
                                if ($pngBytes === false || $pngBytes === '') {
                                    Notification::make()->danger()->title('Invalid signature')->body('The drawn signature could not be read. Please try again.')->send();
                                    return;
                                }
                                if ($saveToProfile && $user) {
                                    $user->setSignatureImageAttribute($pngBytes);
                                    $user->save();
                                }
                                $verificationHash = hash('sha256', (string) $this->record->id . (string) $user->id . now()->timestamp . $pngBytes);
                                $dataUri = 'data:image/png;base64,' . base64_encode($pngBytes);
                                DigitalSignature::create([
                                    'lease_id' => $this->record->id,
                                    'tenant_id' => null,
                                    'signer_type' => 'manager',
                                    'signed_by_user_id' => $user->id,
                                    'signed_by_name' => $user->name ?? 'Property Manager',
                                    'signature_data' => $dataUri,
                                    'signature_type' => 'canvas',
                                    'ip_address' => request()->ip(),
                                    'user_agent' => request()->userAgent(),
                                    'signed_at' => now(),
                                    'is_verified' => true,
                                    'verification_hash' => $verificationHash,
                                ]);
                                $this->record->auditLogs()->create([
                                    'action' => 'manager_countersigned',
                                    'old_state' => $this->record->workflow_state,
                                    'new_state' => 'active',
                                    'user_id' => $user->id,
                                    'user_role_at_time' => $user->role ?? 'manager',
                                    'ip_address' => request()->ip(),
                                    'additional_data' => ['verification_hash_prefix' => substr($verificationHash, 0, 16)],
                                    'description' => $saveToProfile ? 'Manager countersigned with drawn signature (saved to profile)' : 'Manager countersigned with drawn signature',
                                ]);
                            }
                        }

                        // Reset the canvas state after a successful countersign
                        $this->canvasPngB64 = '';

                        $this->record->update([
                            'countersigned_by'  => $data['countersigned_by'] ?? $user->name,
                            'countersigned_at'  => now(),
                            'countersign_notes' => $data['countersign_notes'] ?? null,
                        ]);

                        // Manager route: manager is always 'manager' signer role
                        \App\Services\SigningWorkflowService::advanceAfterSignature(
                            $this->record,
                            \App\Services\SigningWorkflowService::SIGNER_MANAGER,
                        );

                        // ── Final PDF multi-signature pass ─────────────────────
                        $lease = $this->record->fresh();

                        $lesseeWitnessPath = LeaseWitness::where('lease_id', $lease->id)
                            ->where('witnessed_party', 'tenant')
                            ->latest('witnessed_at')
                            ->value('witness_signature_path');

                        $lessorWitnessPath = LeaseWitness::where('lease_id', $lease->id)
                            ->where('witnessed_party', 'lessor')
                            ->latest('witnessed_at')
                            ->value('witness_signature_path');

                        $images = array_filter([
                            'lessee_witness' => $lesseeWitnessPath ? storage_path('app/' . $lesseeWitnessPath) : null,
                            'lessor_witness' => $lessorWitnessPath ? storage_path('app/' . $lessorWitnessPath) : null,
                        ]);

                        if (! empty($images) && $lease->leaseTemplate?->pdf_coordinate_map) {
                            $coordinates = (array) $lease->leaseTemplate->pdf_coordinate_map;

                            $sourcePdfPath = $lease->signed_pdf_path
                                ? storage_path('app/' . $lease->signed_pdf_path)
                                : ($lease->generated_pdf_path ? storage_path('app/' . $lease->generated_pdf_path) : null);

                            if ($sourcePdfPath && file_exists($sourcePdfPath)) {
                                $outputRelative = $lease->signed_pdf_path
                                    ?: 'executed-leases/lease-' . $lease->id . '-executed.pdf';
                                $outputPath = storage_path('app/' . $outputRelative);

                                /** @var PdfOverlayService $pdfOverlay */
                                $pdfOverlay = app(PdfOverlayService::class);
                                $pdfOverlay->stampMultipleSignatures($sourcePdfPath, $images, $coordinates, $outputPath);

                                if (! $lease->signed_pdf_path) {
                                    $lease->update(['signed_pdf_path' => $outputRelative]);
                                }
                            }
                        }

                        $state = $lease->workflow_state;
                        $isActive = $state === 'active';
                        Notification::make()
                            ->success()
                            ->title($isActive ? 'Lease Activated ✅' : 'Countersigned')
                            ->body(
                                $isActive
                                    ? 'Lease ' . ($this->record->reference_number ?? '') . ' is now ACTIVE. '
                                        . ($this->record->tenant?->names ?? 'The tenant')
                                        . ' will receive their copy by email shortly.'
                                    : 'Lease advanced to ' . str_replace('_', ' ', $state) . '. Next party will be notified.'
                            )
                            ->duration(10000)
                            ->send();

                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (\App\Exceptions\LeaseSigningException $e) {
                        Notification::make()
                            ->danger()
                            ->title('Cannot Countersign')
                            ->body($e->getMessage())
                            ->send();
                    } catch (Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Activation Failed')
                            ->body('Error: ' . $e->getMessage())
                            ->send();
                    }
                }),

            // ── PHYSICAL SIGNING: PRINT ──────────────────────────────────────
            Action::make('print')
                ->label('Print Lease')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->visible(
                    fn () => $this->record->workflow_state === 'approved'
                        && $this->record->signing_mode === 'physical',
                )
                ->requiresConfirmation()
                ->modalHeading('Print Physical Lease')
                ->modalDescription('This will mark the lease as printed and log the print job.')
                ->modalSubmitActionLabel('Print Now')
                ->action(fn () => $this->record->markAsPrinted()),

            // ── SEND TO LAWYER ────────────────────────────────────────────────
            // Visible when tenant has signed AND requires_lawyer is true,
            // OR when tenant has signed and user opts to involve a lawyer anyway.
            Action::make('sendToLawyer')
                ->label('Send to Lawyer')
                ->icon('heroicon-o-scale')
                ->color('indigo')
                ->visible(
                    fn () => $this->record->workflow_state === 'tenant_signed'
                        && auth()->user()?->canManageLeases()
                        && Lawyer::active()->exists(),
                )
                ->modalHeading('Send Lease to Lawyer for Review')
                ->modalDescription(
                    fn () => 'Send lease '
                        . ($this->record->reference_number ?? '')
                        . ' to a lawyer for legal review and stamping. '
                        . 'Turnaround time will be tracked. You will be alerted if the lawyer exceeds the expected '
                        . config('lease.lawyer.expected_turnaround_days', 7) . '-day turnaround.',
                )
                ->modalWidth('lg')
                ->modalSubmitActionLabel('Send to Lawyer')
                ->modalSubmitAction(
                    fn ($action) => $action->color('indigo')->icon('heroicon-o-scale'),
                )
                /** @phpstan-ignore-next-line */
                ->schema([
                    Select::make('lawyer_id')
                        ->label('Assign Lawyer')
                        ->options(
                            fn () => Lawyer::active()
                                ->orderBy('firm')
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn ($l) => [
                                    $l->id => ($l->firm ? "[{$l->firm}] " : '') . $l->name
                                        . ($l->specialization ? " — {$l->specialization}" : ''),
                                ])
                        )
                        ->required()
                        ->searchable()
                        ->helperText('Only active lawyers are shown. Add lawyers under Settings → Lawyers.'),

                    Select::make('sent_method')
                        ->label('How are you sending this to the lawyer?')
                        ->options([
                            'email'    => '✉️ Email — send PDF by email',
                            'physical' => '🏢 Physical — hand over or courier physical document',
                        ])
                        ->required()
                        ->default('email')
                        ->helperText('Select the method you will use to send the lease document.'),

                    Textarea::make('sent_notes')
                        ->label('Notes (Optional)')
                        ->placeholder('e.g. Sent to john@lawfirm.com, email ref #2026-03-001...')
                        ->rows(2)
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    try {
                        $lawyer = Lawyer::findOrFail($data['lawyer_id']);
                        $user   = auth()->user();

                        // Create tracking record
                        $tracking = LeaseLawyerTracking::create([
                            'lease_id'    => $this->record->id,
                            'lawyer_id'   => $lawyer->id,
                            'sent_method' => $data['sent_method'],
                            'sent_at'     => now(),
                            'sent_by'     => $user->id,
                            'sent_notes'  => $data['sent_notes'] ?? null,
                            'status'      => 'sent',
                        ]);

                        // Transition lease to WITH_LAWYER state
                        $this->record->transitionTo(LeaseWorkflowState::WITH_LAWYER);

                        // Audit log
                        $this->record->auditLogs()->create([
                            'action'            => 'sent_to_lawyer',
                            'old_state'         => 'tenant_signed',
                            'new_state'         => 'with_lawyer',
                            'user_id'           => $user->id,
                            'user_role_at_time' => $user->getRoleNames()->first() ?? 'manager',
                            'ip_address'        => request()->ip(),
                            'additional_data'   => [
                                'lawyer_id'     => $lawyer->id,
                                'lawyer_name'   => $lawyer->display_name,
                                'sent_method'   => $data['sent_method'],
                                'tracking_id'   => $tracking->id,
                            ],
                            'description' => "Sent to {$lawyer->display_name} via {$data['sent_method']}",
                        ]);

                        $sentVia = $data['sent_method'] === 'email' ? 'email' : 'physical handover';

                        Notification::make()
                            ->success()
                            ->title('Lease Sent to Lawyer ⚖️')
                            ->body(
                                "Sent to {$lawyer->name}"
                                . ($lawyer->firm ? " ({$lawyer->firm})" : '')
                                . " via {$sentVia}. "
                                . 'Turnaround tracking is now active. Expected return within '
                                . config('lease.lawyer.expected_turnaround_days', 7) . ' working days.'
                            )
                            ->duration(8000)
                            ->send();

                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (Exception $e) {
                        Notification::make()->danger()
                            ->title('Failed to Send to Lawyer')
                            ->body('Error: ' . $e->getMessage())
                            ->send();
                    }
                }),

            // ── MARK RETURNED FROM LAWYER ─────────────────────────────────────
            Action::make('markReturnedFromLawyer')
                ->label('Mark as Returned from Lawyer')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->visible(
                    fn () => $this->record->workflow_state === 'with_lawyer'
                        && auth()->user()?->canManageLeases(),
                )
                ->modalHeading('Confirm Lease Returned from Lawyer')
                ->modalDescription(
                    fn () => 'Record the receipt of lease '
                        . ($this->record->reference_number ?? '')
                        . ' back from the lawyer. The system will calculate the turnaround time '
                        . 'and advance the lease to the next step.',
                )
                ->modalWidth('lg')
                ->modalSubmitActionLabel('Confirm Return')
                ->modalSubmitAction(
                    fn ($action) => $action->color('success')->icon('heroicon-o-check'),
                )
                /** @phpstan-ignore-next-line */
                ->schema([
                    Select::make('return_method')
                        ->label('How did the lawyer return it?')
                        ->options([
                            'email'    => '✉️ Email — lawyer emailed the stamped PDF',
                            'physical' => '🏢 Physical — lawyer returned physical document',
                        ])
                        ->required()
                        ->default('email')
                        ->helperText(
                            'If returned physically, you will need to scan and upload the document in the next step.',
                        ),

                    Select::make('next_state')
                        ->label('Next Step After Receipt')
                        ->options([
                            'pending_upload'  => '📤 Pending Upload — need to scan & upload physical document',
                            'pending_deposit' => '💰 Pending Deposit — document ready, awaiting deposit confirmation',
                        ])
                        ->required()
                        ->default('pending_deposit')
                        ->helperText(
                            'Choose "Pending Upload" if the lawyer returned a physical document that needs scanning. '
                            . 'Choose "Pending Deposit" if the document is already in the system.',
                        ),

                    Textarea::make('returned_notes')
                        ->label('Notes (Optional)')
                        ->placeholder('e.g. Received signed and stamped copy, all clauses intact...')
                        ->rows(2)
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    try {
                        $user = auth()->user();

                        // Find the most recent open tracking record for this lease
                        $tracking = LeaseLawyerTracking::where('lease_id', $this->record->id)
                            ->whereIn('status', ['pending', 'sent'])
                            ->latest()
                            ->first();

                        if ($tracking) {
                            $tracking->markAsReturned(
                                $data['return_method'],
                                $user->id,
                                $data['returned_notes'] ?? null,
                            );
                        }

                        $newState = $data['next_state'] === 'pending_upload'
                            ? LeaseWorkflowState::PENDING_UPLOAD
                            : LeaseWorkflowState::PENDING_DEPOSIT;

                        $this->record->transitionTo($newState);

                        // Audit log
                        $this->record->auditLogs()->create([
                            'action'            => 'received_from_lawyer',
                            'old_state'         => 'with_lawyer',
                            'new_state'         => $newState->value,
                            'user_id'           => $user->id,
                            'user_role_at_time' => $user->getRoleNames()->first() ?? 'manager',
                            'ip_address'        => request()->ip(),
                            'additional_data'   => [
                                'return_method'    => $data['return_method'],
                                'next_state'       => $newState->value,
                                'tracking_id'      => $tracking?->id,
                                'turnaround_days'  => $tracking?->turnaround_days,
                            ],
                            'description' => 'Received from lawyer via ' . $data['return_method'],
                        ]);

                        $turnaroundMsg = $tracking?->turnaround_days !== null
                            ? " Turnaround: {$tracking->turnaround_days} day(s)."
                            : '';

                        Notification::make()
                            ->success()
                            ->title('Received from Lawyer ✅')
                            ->body(
                                'Lease marked as returned from lawyer.'
                                . $turnaroundMsg
                                . ' Next step: '
                                . ($newState === LeaseWorkflowState::PENDING_UPLOAD
                                    ? 'Upload the scanned document.'
                                    : 'Confirm security deposit.')
                            )
                            ->duration(8000)
                            ->send();

                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (Exception $e) {
                        Notification::make()->danger()
                            ->title('Failed to Record Return')
                            ->body('Error: ' . $e->getMessage())
                            ->send();
                    }
                }),

            // ── MORE ACTIONS DROPDOWN (secondary/utility actions) ────────────
            ActionGroup::make([

                Action::make('previewPdf')
                    ->label('Preview PDF')
                    ->icon('heroicon-o-eye')
                    ->url(fn () => route('lease.preview', $this->record))
                    ->openUrlInNewTab(),

                Action::make('generatePdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn () => route('lease.download', $this->record))
                    ->openUrlInNewTab(),

                Action::make('sendViaEmail')
                    ->label('Email to Tenant')
                    ->icon('heroicon-o-envelope')
                    ->visible(
                        fn () => $this->record->tenant?->email_address
                            && auth()->user()?->canManageLeases(),
                    )
                    /** @phpstan-ignore-next-line */
                    ->schema([
                        Textarea::make('custom_message')
                            ->label('Custom Message (optional)')
                            ->placeholder('Add a personal message to the tenant...')
                            ->rows(3),
                        Toggle::make('attach_pdf')
                            ->label('Attach Lease PDF')
                            ->default(true),
                    ])
                    ->modalHeading('Email Lease to Tenant')
                    ->modalDescription(
                        fn () => 'Will send to: '
                            . ($this->record->tenant?->names ?? 'Tenant')
                            . ' — ' . ($this->record->tenant?->email_address ?? 'N/A'),
                    )
                    ->action(function (array $data) {
                        $tenant = $this->record->tenant;
                        if (! $tenant || ! $tenant->email_address) {
                            Notification::make()->danger()
                                ->title('No Email Address')
                                ->body('This tenant does not have an email address on record.')
                                ->send();

                            return;
                        }
                        $tenant->notify(new \App\Notifications\LeaseDocumentEmailNotification(
                            lease: $this->record,
                            customMessage: $data['custom_message'] ?? '',
                            attachPdf: $data['attach_pdf'] ?? true,
                        ));
                        if (class_exists(\App\Services\TenantEventService::class)) {
                            \App\Services\TenantEventService::log(
                                tenant: $tenant,
                                type: \App\Enums\TenantEventType::EMAIL,
                                title: 'Lease emailed',
                                body: ['description' => 'Lease ' . ($this->record->reference_number ?? '') . ' sent via email by ' . auth()->user()->name],
                                options: ['performed_by' => auth()->id()],
                            );
                        }
                        Notification::make()->success()
                            ->title('Email Sent')
                            ->body('Lease emailed to ' . $tenant->names . '.')
                            ->send();
                    }),

                Action::make('uploadDocument')
                    ->label('Upload Document')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->visible(fn () => auth()->user()?->canManageLeases())
                    /** @phpstan-ignore-next-line */
                    ->schema([
                        Select::make('document_type')
                            ->label('Document Type')
                            ->options([
                                'signed_physical_lease' => 'Signed Physical Lease',
                                'original_signed' => 'Original Signed Lease',
                                'amendment' => 'Amendment',
                                'addendum' => 'Addendum',
                                'notice' => 'Notice',
                                'id_copy' => 'Tenant ID Copy',
                                'deposit_receipt' => 'Deposit Receipt',
                                'other' => 'Other Document',
                            ])
                            ->default('signed_physical_lease')
                            ->required(),
                        TextInput::make('title')
                            ->label('Document Title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Signed Lease — John Doe — Unit 314E-01'),
                        DatePicker::make('document_date')
                            ->label('Original Document Date')
                            ->helperText('Date printed on the physical document')
                            ->required(),
                        Textarea::make('description')
                            ->label('Notes (Optional)')
                            ->rows(2)
                            ->maxLength(500),
                        FileUpload::make('file')
                            ->label('Scanned File')
                            ->required()
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(10240)
                            ->disk('local')
                            ->directory('temp-uploads')
                            ->helperText('PDF or scanned image (max 10MB)'),
                    ])
                    ->modalHeading('Upload Document')
                    ->modalSubmitActionLabel('Upload & Save')
                    ->action(function (array $data) {
                        $uploadService = new DocumentUploadService;
                        $filePath = $data['file'];
                        $fullPath = storage_path('app/' . $filePath);
                        if (! file_exists($fullPath)) {
                            Notification::make()->danger()->title('Upload Failed')->body('File not found.')->send();

                            return;
                        }
                        $file = new \Illuminate\Http\UploadedFile(
                            $fullPath,
                            basename($filePath),
                            mime_content_type($fullPath),
                            null,
                            true,
                        );

                        try {
                            $document = $uploadService->upload(
                                $file,
                                $this->record->id,
                                $data['document_type'],
                                $data['title'],
                                $data['description'] ?? null,
                                $data['document_date'] ?? null,
                            );
                            if ($this->record->unit_code) {
                                $document->update(['unit_code' => $this->record->unit_code]);
                            }
                            @unlink($fullPath);
                            $message = 'Document uploaded successfully.';
                            if ($document->is_compressed && $document->compression_ratio) {
                                $message .= " Compressed by {$document->compression_ratio}%.";
                            }
                            Notification::make()->success()->title('Document Uploaded')->body($message)->send();
                        } catch (Exception $e) {
                            Notification::make()->danger()->title('Upload Failed')->body($e->getMessage())->send();
                        }
                    }),

            ])
                ->label('More')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray')
                ->button(),
        ];
    }
}
