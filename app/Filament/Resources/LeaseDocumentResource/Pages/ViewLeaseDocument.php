<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeaseDocumentResource\Pages;

use App\Enums\DocumentStatus;
use App\Filament\Resources\LeaseDocumentResource;
use App\Models\DocumentAudit;
use App\Models\Lease;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class ViewLeaseDocument extends ViewRecord
{
    protected static string $resource = LeaseDocumentResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Document Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Text::make($this->record->title ?? 'Untitled')
                                    ->weight('bold'),
                                Text::make(\App\Models\LeaseDocument::getDocumentTypes()[$this->record->document_type] ?? ucfirst($this->record->document_type ?? '')),
                                Text::make($this->record->status?->getLabel() ?? 'Unknown')
                                    ->badge()
                                    ->color($this->record->status?->getColor() ?? 'gray'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                Text::make('Zone: ' . ($this->record->zone?->name ?? 'N/A')),
                                Text::make('Property: ' . ($this->record->property?->name ?? 'N/A')),
                                Text::make('Year: ' . ($this->record->document_year ?? 'N/A')),
                            ]),
                        Grid::make(3)
                            ->schema([
                                Text::make($this->record->quality?->getLabel() ?? 'Unknown')
                                    ->badge()
                                    ->color($this->record->quality?->getColor() ?? 'gray'),
                                Text::make($this->record->source?->getLabel() ?? 'Unknown')
                                    ->badge()
                                    ->color('info'),
                                Text::make('Uploaded: ' . ($this->record->created_at?->format('M j, Y g:i A') ?? 'Unknown')),
                            ]),
                    ]),

                Section::make('Document Integrity & Security')
                    ->icon('heroicon-o-shield-check')
                    ->description('Cryptographic hash ensures document authenticity and tamper detection')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Group::make([
                                    Text::make('SHA-256 Hash')
                                        ->weight('semibold')
                                        ->size('sm'),
                                    Text::make($this->record->file_hash ?? 'No hash generated')
                                        ->fontFamily('mono')
                                        ->copyable()
                                        ->size('sm'),
                                    Text::make('Short: ' . ($this->record->short_hash ?? 'N/A'))
                                        ->fontFamily('mono')
                                        ->size('xs')
                                        ->color('gray'),
                                ]),
                                Group::make([
                                    Text::make('Integrity Status')
                                        ->weight('semibold')
                                        ->size('sm'),
                                    Text::make($this->getIntegrityStatusLabel())
                                        ->badge()
                                        ->color($this->getIntegrityStatusColor()),
                                    Text::make('Last Verified: ' . ($this->record->last_integrity_check?->format('M j, Y g:i A') ?? 'Never'))
                                        ->size('xs')
                                        ->color('gray'),
                                    Text::make('Version: v' . ($this->record->version ?? 1))
                                        ->badge()
                                        ->color('info'),
                                ]),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('File Details')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                Text::make('Filename: ' . ($this->record->original_filename ?? 'Unknown')),
                                Text::make('Type: ' . ($this->record->mime_type ?? 'Unknown')),
                                Text::make('Size: ' . ($this->record->file_size_for_humans ?? 'Unknown')),
                                Text::make($this->record->is_compressed ? 'Compressed' : 'Not Compressed')
                                    ->badge()
                                    ->color($this->record->is_compressed ? 'success' : 'gray'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Workflow History')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Text::make('Uploaded By: ' . ($this->record->uploader?->name ?? 'Unknown')),
                                Text::make('Reviewed By: ' . ($this->record->reviewer?->name ?? 'Not reviewed')),
                                Text::make('Reviewed At: ' . ($this->record->reviewed_at?->format('M j, Y g:i A') ?? 'Not reviewed')),
                            ]),
                        ...$this->getWorkflowNotes(),
                    ])
                    ->collapsible(),

                Section::make('Audit Trail')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->description('Complete history of all actions on this document')
                    ->schema([
                        View::make('filament.resources.lease-document-resource.components.audit-trail')
                            ->viewData([
                                'audits' => $this->record->auditTrail()->with('user')->limit(20)->get(),
                            ]),
                    ])
                    ->collapsible(),

                ...$this->getVersionHistorySection(),
            ]);
    }

    protected function getIntegrityStatusLabel(): string
    {
        if ($this->record->integrity_status === null) {
            return 'Not Verified';
        }
        return $this->record->integrity_status ? 'Verified' : 'FAILED';
    }

    protected function getIntegrityStatusColor(): string
    {
        if ($this->record->integrity_status === null) {
            return 'gray';
        }
        return $this->record->integrity_status ? 'success' : 'danger';
    }

    protected function getWorkflowNotes(): array
    {
        $components = [];

        if ($this->record->status === DocumentStatus::REJECTED && $this->record->rejection_reason) {
            $components[] = Text::make('Rejection Reason: ' . $this->record->rejection_reason)
                ->color('danger');
        }

        if ($this->record->notes) {
            $components[] = Text::make('Notes: ' . $this->record->notes)
                ->color('gray');
        }

        return $components;
    }

    protected function getVersionHistorySection(): array
    {
        if ($this->record->version <= 1 && !$this->record->versions()->exists()) {
            return [];
        }

        return [
            Section::make('Version History')
                ->icon('heroicon-o-document-duplicate')
                ->description('Track document revisions and replacements')
                ->schema([
                    View::make('filament.resources.lease-document-resource.components.version-history')
                        ->viewData([
                            'versions' => $this->record->getAllVersions(),
                            'currentId' => $this->record->id,
                        ]),
                ])
                ->collapsible()
                ->collapsed(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('verifyIntegrity')
                ->label('Verify Integrity')
                ->icon('heroicon-o-shield-check')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Verify Document Integrity')
                ->modalDescription('This will compare the stored hash with the current file to detect any tampering.')
                ->action(function (): void {
                    $isValid = $this->record->verifyIntegrity();

                    if ($isValid) {
                        Notification::make()
                            ->title('Integrity Verified')
                            ->body('Document hash matches. No tampering detected.')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Integrity Check Failed')
                            ->body('WARNING: Document hash does not match! File may have been modified.')
                            ->danger()
                            ->persistent()
                            ->send();
                    }

                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),

            Actions\EditAction::make()
                ->visible(fn (): bool => $this->record->can_edit),

            Actions\Action::make('download')
                ->label('Download')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn (): ?string => $this->record->getDownloadUrl())
                ->openUrlInNewTab(),

            Actions\Action::make('preview')
                ->label('Preview')
                ->icon('heroicon-o-eye')
                ->url(fn (): ?string => $this->record->getPreviewUrl())
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->record->getPreviewUrl() !== null),

            // Review Actions
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool =>
                    $this->record->status === DocumentStatus::PENDING_REVIEW
                    && $this->record->canBeReviewedBy(auth()->user())
                )
                ->requiresConfirmation()
                ->modalHeading('Approve Document')
                ->modalDescription('Are you sure you want to approve this document?')
                ->form([
                    Forms\Components\Textarea::make('notes')
                        ->label('Notes (optional)')
                        ->rows(2),
                ])
                ->action(function (array $data): void {
                    $this->record->approve(auth()->user(), $data['notes'] ?? null);
                    $this->record->logAudit(
                        DocumentAudit::ACTION_APPROVE,
                        'Document approved by ' . auth()->user()->name,
                        newValues: ['notes' => $data['notes'] ?? null]
                    );
                    Notification::make()
                        ->title('Document approved')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool =>
                    in_array($this->record->status, [DocumentStatus::PENDING_REVIEW, DocumentStatus::IN_REVIEW])
                    && $this->record->canBeReviewedBy(auth()->user())
                )
                ->requiresConfirmation()
                ->modalHeading('Reject Document')
                ->form([
                    Forms\Components\Select::make('reason_type')
                        ->label('Reason')
                        ->options([
                            'poor_quality' => 'Poor scan quality - please re-scan',
                            'wrong_document' => 'Wrong document type uploaded',
                            'incomplete' => 'Document is incomplete or cut off',
                            'duplicate' => 'This document already exists',
                            'unreadable' => 'Document is unreadable/illegible',
                            'other' => 'Other reason',
                        ])
                        ->required(),
                    Forms\Components\Textarea::make('rejection_reason')
                        ->label('Additional Details')
                        ->rows(2)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $reason = $data['reason_type'] . ': ' . $data['rejection_reason'];
                    $this->record->reject(auth()->user(), $reason);
                    $this->record->logAudit(
                        DocumentAudit::ACTION_REJECT,
                        'Document rejected by ' . auth()->user()->name,
                        newValues: ['reason' => $reason]
                    );
                    Notification::make()
                        ->title('Document rejected')
                        ->body('The uploader will be notified.')
                        ->warning()
                        ->send();
                }),

            Actions\Action::make('linkToLease')
                ->label('Link to Lease')
                ->icon('heroicon-o-link')
                ->color('primary')
                ->visible(fn (): bool => $this->record->status === DocumentStatus::APPROVED)
                ->form([
                    Forms\Components\Select::make('lease_id')
                        ->label('Select Lease')
                        ->options(function () {
                            return Lease::query()
                                ->with(['tenant', 'unit'])
                                ->latest()
                                ->limit(100)
                                ->get()
                                ->mapWithKeys(fn ($lease) => [
                                    $lease->id => $lease->reference_number . ' - ' .
                                        ($lease->tenant?->names ?? 'Unknown') . ' - ' .
                                        ($lease->unit?->unit_number ?? 'Unknown')
                                ]);
                        })
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $lease = Lease::find($data['lease_id']);
                    if ($lease && $this->record->linkToLease($lease, auth()->user())) {
                        $this->record->logAudit(
                            DocumentAudit::ACTION_LINK,
                            'Document linked to lease ' . $lease->reference_number,
                            newValues: ['lease_id' => $lease->id, 'reference' => $lease->reference_number]
                        );
                        Notification::make()
                            ->title('Document linked to lease')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Failed to link document')
                            ->danger()
                            ->send();
                    }
                }),

            Actions\DeleteAction::make()
                ->visible(fn (): bool => $this->record->can_delete),
        ];
    }

    protected function afterFill(): void
    {
        // Log view action
        $this->record->logAudit(
            DocumentAudit::ACTION_VIEW,
            'Document viewed by ' . auth()->user()->name
        );
    }
}
