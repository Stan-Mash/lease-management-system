<?php

namespace App\Filament\Resources\Leases\Pages;

use App\Enums\LeaseWorkflowState;
use App\Filament\Resources\Leases\Actions\CancelDisputedLeaseAction;
use App\Services\DigitalSigningService;
use App\Filament\Resources\Leases\Actions\ResolveDisputeAction;
use App\Filament\Resources\Leases\LeaseResource;
use App\Filament\Resources\Leases\Widgets\LeaseAuditTimelineWidget;
use App\Filament\Resources\Leases\Widgets\LeaseJourneyStepperWidget;
use App\Models\DigitalSignature;
use App\Services\DocumentUploadService;
use App\Services\LandlordApprovalService;
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

class ViewLease extends ViewRecord
{
    protected static string $resource = LeaseResource::class;

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

    protected function getHeaderWidgets(): array
    {
        return [
            LeaseJourneyStepperWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    protected function getFooterWidgets(): array
    {
        return [
            LeaseAuditTimelineWidget::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
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
                    fn () => $this->record->workflow_state === 'draft'
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
                    $result = LandlordApprovalService::requestApproval($this->record, 'email');
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
                    fn () => in_array($this->record->workflow_state, ['pending_landlord_approval', 'draft'])
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
                            ->persistent()
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
            Action::make('sendDigital')
                ->label('Send Signing Link to Tenant')
                ->icon('heroicon-o-device-phone-mobile')
                ->color('info')
                ->visible(
                    fn () => $this->record->workflow_state === 'approved'
                        && $this->record->signing_mode === 'digital',
                )
                ->modalHeading('Send Signing Link to Tenant')
                ->modalDescription(
                    fn () => 'The tenant will receive a secure link to digitally sign their lease (valid 72 hours). '
                        . 'They will open the link, request a 6-digit OTP, verify it, and draw their signature.',
                )
                ->modalSubmitActionLabel('Send Now')
                /** @phpstan-ignore-next-line */
                ->schema([
                    Select::make('send_method')
                        ->label('Send Via')
                        ->options(function () {
                            $options = [];
                            if ($this->record->tenant?->mobile_number) {
                                $options['sms'] = '📱 SMS — ' . ($this->record->tenant->mobile_number);
                            }
                            if ($this->record->tenant?->email_address) {
                                $options['email'] = '✉️ Email — ' . ($this->record->tenant->email_address);
                            }
                            if (count($options) === 2) {
                                $options['both'] = '📱 + ✉️ Both SMS & Email';
                            }

                            return $options;
                        })
                        ->default(function () {
                            if ($this->record->tenant?->mobile_number) {
                                return 'sms';
                            }
                            if ($this->record->tenant?->email_address) {
                                return 'email';
                            }

                            return 'both';
                        })
                        ->required()
                        ->helperText('Choose how to send the signing link to the tenant'),
                ])
                ->action(function (array $data) {
                    try {
                        $method = $data['send_method'] ?? 'sms';
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
                            ->persistent()
                            ->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (Exception $e) {
                        Notification::make()->danger()
                            ->title('Failed to Send Signing Link')
                            ->body('Error: ' . $e->getMessage())
                            ->send();
                    }
                }),

            // ── RESEND SIGNING LINK ──────────────────────────────────────────
            Action::make('resendSigningLink')
                ->label('Resend Signing Link')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(
                    fn () => in_array($this->record->workflow_state, [
                        'sent_digital', 'pending_otp', 'pending_tenant_signature', 'tenant_signed',
                    ]) && $this->record->signing_mode === 'digital',
                )
                ->modalHeading('Resend Signing Link')
                ->modalDescription('Send a fresh signing link to the tenant. Use this if the link expired or was not received.')
                ->modalSubmitActionLabel('Resend Now')
                /** @phpstan-ignore-next-line */
                ->schema([
                    Select::make('send_method')
                        ->label('Send Via')
                        ->options(function () {
                            $options = [];
                            if ($this->record->tenant?->mobile_number) {
                                $options['sms'] = '📱 SMS — ' . ($this->record->tenant->mobile_number);
                            }
                            if ($this->record->tenant?->email_address) {
                                $options['email'] = '✉️ Email — ' . ($this->record->tenant->email_address);
                            }
                            if (count($options) === 2) {
                                $options['both'] = '📱 + ✉️ Both SMS & Email';
                            }

                            return $options;
                        })
                        ->default(function () {
                            if ($this->record->tenant?->mobile_number) {
                                return 'sms';
                            }
                            if ($this->record->tenant?->email_address) {
                                return 'email';
                            }

                            return 'sms';
                        })
                        ->required(),
                ])
                ->action(function (array $data) {
                    try {
                        $method = $data['send_method'] ?? 'sms';
                        $this->record->resendDigitalSigningLink($method);

                        $sentTo = match ($method) {
                            'sms' => 'SMS to ' . ($this->record->tenant?->mobile_number ?? 'tenant'),
                            'email' => 'email to ' . ($this->record->tenant?->email_address ?? 'tenant'),
                            'both' => 'SMS & email to ' . ($this->record->tenant?->names ?? 'tenant'),
                            default => 'tenant',
                        };

                        Notification::make()->success()
                            ->title('Signing Link Resent ✅')
                            ->body("New link sent via {$sentTo}.")
                            ->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (Exception $e) {
                        Notification::make()->danger()
                            ->title('Failed to Resend')
                            ->body('Error: ' . $e->getMessage())
                            ->send();
                    }
                }),

            // ── COUNTERSIGN & ACTIVATE (after tenant has signed digitally) ───
            Action::make('countersignActivate')
                ->label('Countersign & Activate Lease')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(
                    fn () => $this->record->workflow_state === 'tenant_signed'
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

                    // ── Signature pad (always shown) ─────────────────────────
                    // pad_signature_b64 is the Filament Hidden field that holds
                    // the base64 PNG (draw tab) or "__use_saved__" (saved tab).
                    // We push data into it via a synthetic 'input' event so that
                    // Filament's wire:model binding picks it up before submission.
                    \Filament\Forms\Components\Placeholder::make('signature_pad_ui')
                        ->label('Signature')
                        ->content(function () {
                            $hasSaved     = (bool) auth()->user()?->signature_image_encrypted;
                            $savedDataUri = auth()->user()?->signature_image_data_uri ?? '';
                            $defaultTab   = $hasSaved ? 'saved' : 'draw';

                            $savedTabBtn = $hasSaved
                                ? '<button type="button"
                                        @click="switchTab(\'saved\')"
                                        :style="tab===\'saved\'
                                            ? \'background:#DAA520;color:#fff;border-color:#DAA520;\'
                                            : \'background:#fff;color:#1a365d;border-color:#e2d9c8;\'"
                                        style="padding:6px 18px;border-radius:6px;font-size:13px;font-weight:600;border:1.5px solid #e2d9c8;cursor:pointer;transition:all .2s;\">
                                        ✅ Use Saved Signature
                                   </button>'
                                : '';

                            $savedPanel = $hasSaved
                                ? '<div x-show="tab===\'saved\'" x-cloak style="margin-top:12px;">
                                       <div style="background:linear-gradient(135deg,#faf8f4 0%,#fff9e8 100%);border:1.5px solid rgba(218,165,32,0.35);border-left:5px solid #DAA520;border-radius:10px;padding:16px 20px;">
                                           <p style="font-size:12px;color:#92700a;margin:0 0 10px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Your Saved Signature</p>
                                           <img src="' . e($savedDataUri) . '" alt="Saved signature"
                                                style="max-height:72px;border:1px solid #e2d9c8;border-radius:6px;background:#fff;padding:4px;" />
                                           <p style="font-size:11px;color:#6b7280;margin:10px 0 0;">This signature will be stamped on the lease.</p>
                                       </div>
                                   </div>'
                                : '';

                            return new \Illuminate\Support\HtmlString(
                                <<<HTML
<div
    x-data="{
        tab: '{$defaultTab}',
        drawing: false,
        lastX: 0, lastY: 0,
        hasStrokes: false,
        switchTab(t) {
            this.tab = t;
            this.pushValue(t === 'draw' ? '' : '__use_saved__');
        },
        startDraw(e) {
            this.drawing = true; this.hasStrokes = true;
            const p = this.getPos(e); this.lastX = p.x; this.lastY = p.y;
        },
        draw(e) {
            if (!this.drawing) return;
            e.preventDefault();
            const ctx = \$refs.sigCanvas.getContext('2d');
            const p = this.getPos(e);
            ctx.beginPath(); ctx.moveTo(this.lastX, this.lastY);
            ctx.lineTo(p.x, p.y);
            ctx.strokeStyle = '#1a365d'; ctx.lineWidth = 2.5;
            ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.stroke();
            this.lastX = p.x; this.lastY = p.y;
        },
        stopDraw() { this.drawing = false; },
        getPos(e) {
            const rect = \$refs.sigCanvas.getBoundingClientRect();
            const src = e.touches ? e.touches[0] : e;
            return { x: src.clientX - rect.left, y: src.clientY - rect.top };
        },
        clearPad() {
            const c = \$refs.sigCanvas;
            c.getContext('2d').clearRect(0, 0, c.width, c.height);
            this.hasStrokes = false;
            this.pushValue('');
        },
        captureAndPush() {
            if (this.tab === 'saved') { this.pushValue('__use_saved__'); return; }
            if (!this.hasStrokes)    { this.pushValue(''); return; }
            const b64 = \$refs.sigCanvas.toDataURL('image/png').split(',')[1];
            this.pushValue(b64);
        },
        pushValue(val) {
            const el = document.getElementById('pad_signature_b64_input');
            if (el) { el.value = val; el.dispatchEvent(new Event('input', { bubbles: true })); }
        }
    }"
    @mouseup.window="stopDraw()"
    @touchend.window="stopDraw()"
    x-init="
        \$nextTick(() => {
            /* Intercept modal submit button click to capture signature first */
            const modal = \$el.closest('[x-data]');
            const submitBtn = document.querySelector('[data-action-id=countersignActivate] button[type=submit], button.fi-modal-submit-action');
            if (submitBtn) {
                submitBtn.addEventListener('click', () => captureAndPush(), true);
            }
            /* Fallback: listen for Livewire's submit on the form */
            const form = \$el.closest('form');
            if (form) { form.addEventListener('submit', () => captureAndPush(), true); }
        });
    "
>
    <!-- Tab bar -->
    <div style="display:flex;gap:8px;margin-bottom:12px;">
        <button type="button"
            @click="switchTab('draw')"
            :style="tab==='draw'
                ? 'background:#DAA520;color:#fff;border-color:#DAA520;'
                : 'background:#fff;color:#1a365d;border-color:#e2d9c8;'"
            style="padding:6px 18px;border-radius:6px;font-size:13px;font-weight:600;border:1.5px solid #e2d9c8;cursor:pointer;transition:all .2s;">
            ✏️ Draw Signature
        </button>
        {$savedTabBtn}
    </div>

    <!-- Draw pad -->
    <div x-show="tab==='draw'" style="position:relative;">
        <div style="background:linear-gradient(135deg,#faf8f4 0%,#fff9e8 100%);border:1.5px solid rgba(218,165,32,0.35);border-left:5px solid #DAA520;border-radius:10px;padding:4px;">
            <canvas x-ref="sigCanvas" width="600" height="150"
                style="display:block;width:100%;height:150px;cursor:crosshair;border-radius:7px;background:#fff;touch-action:none;"
                @mousedown="startDraw(\$event)"
                @mousemove="draw(\$event)"
                @mouseup="stopDraw()"
                @touchstart.prevent="startDraw(\$event)"
                @touchmove.prevent="draw(\$event)"
                @touchend="stopDraw()">
            </canvas>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px;">
            <span style="font-size:11px;color:#9ca3af;">✍️ Draw your signature above using mouse or finger</span>
            <button type="button" @click="clearPad()"
                style="font-size:11px;color:#b8960a;font-weight:600;background:none;border:none;cursor:pointer;text-decoration:underline;">
                Clear
            </button>
        </div>
    </div>

    {$savedPanel}
</div>
HTML
                            );
                        }),

                    // Wire-bound hidden field — Filament reads this in $data on action()
                    \Filament\Forms\Components\Hidden::make('pad_signature_b64')
                        ->extraInputAttributes(['id' => 'pad_signature_b64_input']),

                    // Save-to-profile toggle
                    \Filament\Forms\Components\Toggle::make('save_signature_to_profile')
                        ->label('Save drawn signature to my profile for future use')
                        ->default(false)
                        ->helperText('If ticked, your drawn signature will be encrypted and stored on your account for next time.'),

                    Textarea::make('countersign_notes')
                        ->label('Notes (Optional)')
                        ->placeholder('e.g. Deposit received, keys handed over...')
                        ->rows(2)
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    try {
                        $user = auth()->user();

                        // Determine which signature method to use
                        $padData = trim($data['pad_signature_b64'] ?? '');

                        if ($padData === '__use_saved__') {
                            // Manager chose to use their saved uploaded signature
                            if (! $user?->signature_image_encrypted) {
                                Notification::make()->danger()
                                    ->title('No Saved Signature')
                                    ->body('No saved signature found. Please draw your signature instead.')
                                    ->send();

                                return;
                            }
                            DigitalSigningService::stampManagerSignature($this->record, $user);
                        } elseif ($padData !== '') {
                            // Manager drew on the signature pad — base64 PNG
                            $saveToProfile = (bool) ($data['save_signature_to_profile'] ?? false);
                            DigitalSigningService::stampManagerSignatureFromPng(
                                $this->record,
                                $user,
                                $padData,
                                $saveToProfile,
                            );
                        } else {
                            // No signature supplied at all
                            Notification::make()->danger()
                                ->title('Signature Required')
                                ->body('Please draw your signature or select your saved signature before countersigning.')
                                ->send();

                            return;
                        }

                        $this->record->update([
                            'countersigned_by'  => $data['countersigned_by'] ?? $user->name,
                            'countersigned_at'  => now(),
                            'countersign_notes' => $data['countersign_notes'] ?? null,
                        ]);

                        $this->record->transitionTo(LeaseWorkflowState::ACTIVE);

                        Notification::make()
                            ->success()
                            ->title('Lease Activated ✅')
                            ->body(
                                'Lease ' . ($this->record->reference_number ?? '') . ' is now ACTIVE. '
                                . ($this->record->tenant?->names ?? 'The tenant')
                                . ' will receive their copy by email shortly.'
                            )
                            ->persistent()
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
                                type: 'email_sent',
                                title: 'Lease emailed',
                                description: 'Lease ' . ($this->record->reference_number ?? '') . ' sent via email by ' . auth()->user()->name,
                                performedBy: auth()->user(),
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
