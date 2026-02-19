<?php

namespace App\Filament\Resources\Leases\Pages;

use App\Filament\Resources\Leases\Actions\CancelDisputedLeaseAction;
use App\Filament\Resources\Leases\Actions\ResolveDisputeAction;
use App\Filament\Resources\Leases\LeaseResource;
use App\Services\DocumentUploadService;
use App\Services\LandlordApprovalService;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewLease extends ViewRecord
{
    protected static string $resource = LeaseResource::class;

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
                ->requiresConfirmation()
                ->modalHeading('Send SMS Signing Link to Tenant')
                ->modalDescription(
                    fn () => '📱 An SMS will be sent to '
                        . ($this->record->tenant?->names ?? 'the tenant')
                        . ' at ' . ($this->record->tenant?->mobile_number ?? '— no phone —')
                        . ".\n\nThe tenant will:"
                        . "\n 1. Receive an SMS with a secure link (valid 72 hours)"
                        . "\n 2. Open the link and request a 6-digit OTP code"
                        . "\n 3. Verify the OTP and read the full lease"
                        . "\n 4. Draw their digital signature"
                        . "\n\nSMS language: "
                        . ($this->record->tenant?->preferred_language === 'sw' ? '🇰🇪 Kiswahili' : '🇬🇧 English'),
                )
                ->modalSubmitActionLabel('📱 Send SMS Now')
                ->action(function () {
                    try {
                        $this->record->sendDigitalSigningLink();
                        Notification::make()->success()
                            ->title('SMS Sent! 📱')
                            ->body(
                                'Signing link sent to '
                                . ($this->record->tenant?->mobile_number ?? 'tenant')
                                . '. Status is now "Signing Link Sent". '
                                . 'The tenant will receive an OTP when they open the link.',
                            )
                            ->persistent()
                            ->send();
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (Exception $e) {
                        Notification::make()->danger()
                            ->title('SMS Failed to Send')
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
                        'sent_digital', 'pending_otp', 'pending_tenant_signature',
                    ]) && $this->record->signing_mode === 'digital',
                )
                ->requiresConfirmation()
                ->modalHeading('Resend Signing Link')
                ->modalDescription(
                    fn () => 'A new SMS will be sent to '
                        . ($this->record->tenant?->names ?? 'the tenant')
                        . ' at ' . ($this->record->tenant?->mobile_number ?? '—')
                        . '. Use this if the tenant says they didn\'t receive the link, or the link has expired.',
                )
                ->modalSubmitActionLabel('Resend SMS')
                ->action(function () {
                    try {
                        $this->record->sendDigitalSigningLink();
                        Notification::make()->success()
                            ->title('Signing Link Resent')
                            ->body('New SMS sent to ' . ($this->record->tenant?->mobile_number ?? 'tenant') . '.')
                            ->send();
                    } catch (Exception $e) {
                        Notification::make()->danger()
                            ->title('Failed to Resend')
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

            // ── PREVIEW / DOWNLOAD PDF ───────────────────────────────────────
            Action::make('previewPdf')
                ->label('Preview PDF')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn () => route('lease.preview', $this->record))
                ->openUrlInNewTab(),

            Action::make('generatePdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->url(fn () => route('lease.download', $this->record))
                ->openUrlInNewTab(),

            // ── EMAIL TO TENANT ──────────────────────────────────────────────
            Action::make('sendViaEmail')
                ->label('Email to Tenant')
                ->icon('heroicon-o-envelope')
                ->color('gray')
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

            // ── UPLOAD DOCUMENT ──────────────────────────────────────────────
            Action::make('uploadDocument')
                ->label('Upload Document')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
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
        ];
    }
}
