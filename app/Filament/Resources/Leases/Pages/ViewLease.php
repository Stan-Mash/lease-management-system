<?php

namespace App\Filament\Resources\Leases\Pages;

use App\Filament\Resources\Leases\LeaseResource;
use App\Services\DocumentUploadService;
use App\Services\LandlordApprovalService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ViewLease extends ViewRecord
{
    protected static string $resource = LeaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => in_array($this->record->workflow_state, ['draft', 'received'])),

            // Request Landlord Approval
            Action::make('requestApproval')
                ->label('Request Approval')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(
                    fn () => $this->record->workflow_state === 'draft' &&
                    ! $this->record->hasPendingApproval() &&
                    $this->record->landlord_id,
                )
                ->requiresConfirmation()
                ->modalHeading('Request Landlord Approval')
                ->modalDescription('Send this lease to the landlord for review and approval.')
                ->modalSubmitActionLabel('Send Request')
                ->action(function () {
                    $result = LandlordApprovalService::requestApproval($this->record, 'email');

                    if ($result['success']) {
                        Notification::make()
                            ->success()
                            ->title('Approval Requested')
                            ->body('The landlord has been notified via email.')
                            ->send();
                    } else {
                        Notification::make()
                            ->danger()
                            ->title('Request Failed')
                            ->body($result['message'])
                            ->send();
                    }
                }),

            // Approve Lease (Landlord/Admin)
            Action::make('approveLease')
                ->label('Approve Lease')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(
                    fn () => $this->record->workflow_state === 'pending_landlord_approval' ||
                    ($this->record->workflow_state === 'draft' && ! $this->record->hasPendingApproval()),
                )
                /** @phpstan-ignore-next-line */
                ->schema([
                    Textarea::make('comments')
                        ->label('Approval Comments (Optional)')
                        ->placeholder('Add any comments about this approval...')
                        ->rows(3)
                        ->maxLength(1000),
                ])
                ->modalHeading('Approve Lease')
                ->modalDescription('Approve this lease agreement to proceed with tenant signing.')
                ->modalSubmitActionLabel('Approve')
                ->action(function (array $data) {
                    $result = LandlordApprovalService::approveLease(
                        $this->record,
                        $data['comments'] ?? null,
                        'email',
                    );

                    if ($result['success']) {
                        Notification::make()
                            ->success()
                            ->title('Lease Approved')
                            ->body('The tenant has been notified via email.')
                            ->send();

                        $this->refreshFormData(['workflow_state']);
                    } else {
                        Notification::make()
                            ->danger()
                            ->title('Approval Failed')
                            ->body($result['message'])
                            ->send();
                    }
                }),

            // Reject Lease (Landlord/Admin)
            Action::make('rejectLease')
                ->label('Reject Lease')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(
                    fn () => $this->record->workflow_state === 'pending_landlord_approval' ||
                    ($this->record->workflow_state === 'draft' && ! $this->record->hasPendingApproval()),
                )
                /** @phpstan-ignore-next-line */
                ->schema([
                    TextInput::make('rejection_reason')
                        ->label('Reason for Rejection')
                        ->placeholder('e.g., Rent amount too high')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('comments')
                        ->label('Additional Comments (Optional)')
                        ->placeholder('Provide details about required changes...')
                        ->rows(3)
                        ->maxLength(1000),
                ])
                ->modalHeading('Reject Lease')
                ->modalDescription('Reject this lease and provide feedback for revision.')
                ->modalSubmitActionLabel('Reject')
                ->action(function (array $data) {
                    $result = LandlordApprovalService::rejectLease(
                        $this->record,
                        $data['rejection_reason'],
                        $data['comments'] ?? null,
                        'email',
                    );

                    if ($result['success']) {
                        Notification::make()
                            ->warning()
                            ->title('Lease Rejected')
                            ->body('The tenant has been notified to revise the lease.')
                            ->send();

                        $this->refreshFormData(['workflow_state']);
                    } else {
                        Notification::make()
                            ->danger()
                            ->title('Rejection Failed')
                            ->body($result['message'])
                            ->send();
                    }
                }),

            Action::make('sendDigital')
                ->label('Send Digital Link')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(
                    fn () => $this->record->workflow_state === 'approved' &&
                    $this->record->signing_mode === 'digital',
                )
                ->requiresConfirmation()
                ->action(fn () => $this->record->sendDigitalSigningLink()),

            Action::make('print')
                ->label('Print Lease')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->visible(
                    fn () => $this->record->workflow_state === 'approved' &&
                    $this->record->signing_mode === 'physical',
                )
                ->action(fn () => $this->record->markAsPrinted()),

            Action::make('previewPdf')
                ->label('Preview PDF')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url(fn () => route('lease.preview', $this->record))
                ->openUrlInNewTab(),

            Action::make('generatePdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->url(fn () => route('lease.download', $this->record))
                ->openUrlInNewTab(),

            // Upload Scanned Physical Lease
            Action::make('uploadDocument')
                ->label('Upload Scanned Lease')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->form([
                    Select::make('document_type')
                        ->label('Document Type')
                        ->options([
                            'signed_physical_lease' => 'Signed Physical Lease (Historical)',
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
                        ->placeholder('e.g., Signed Lease - John Doe - Unit 314E-01'),
                    DatePicker::make('document_date')
                        ->label('Original Document Date')
                        ->helperText('Date on the physical document')
                        ->required(),
                    Textarea::make('description')
                        ->label('Notes (Optional)')
                        ->rows(2)
                        ->maxLength(500)
                        ->placeholder('e.g., Scanned from file cabinet A, folder 23'),
                    FileUpload::make('file')
                        ->label('Scanned File')
                        ->required()
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(10240) // 10MB
                        ->disk('local')
                        ->directory('temp-uploads')
                        ->helperText('PDF or scanned images (max 10MB). Images will be compressed automatically to save storage.'),
                ])
                ->modalHeading('Upload Scanned Physical Lease')
                ->modalDescription('Digitize historical signed leases from physical files for easy retrieval.')
                ->modalSubmitActionLabel('Upload & Save')
                ->action(function (array $data) {
                    $uploadService = new DocumentUploadService();

                    // Get the uploaded file path
                    $filePath = $data['file'];
                    $fullPath = storage_path('app/' . $filePath);

                    if (!file_exists($fullPath)) {
                        Notification::make()
                            ->danger()
                            ->title('Upload Failed')
                            ->body('Could not find uploaded file.')
                            ->send();
                        return;
                    }

                    $file = new \Illuminate\Http\UploadedFile(
                        $fullPath,
                        basename($filePath),
                        mime_content_type($fullPath),
                        null,
                        true
                    );

                    try {
                        $document = $uploadService->upload(
                            $file,
                            $this->record->id,
                            $data['document_type'],
                            $data['title'],
                            $data['description'] ?? null,
                            $data['document_date'] ?? null
                        );

                        // Clean up temp file
                        @unlink($fullPath);

                        $message = "Document uploaded successfully.";
                        if ($document->is_compressed && $document->compression_ratio) {
                            $message .= " Compressed by {$document->compression_ratio}%.";
                        }

                        Notification::make()
                            ->success()
                            ->title('Document Uploaded')
                            ->body($message)
                            ->send();

                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Upload Failed')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}
