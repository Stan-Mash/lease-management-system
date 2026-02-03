<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeaseDocumentResource\Pages;

use App\Enums\DocumentStatus;
use App\Filament\Resources\LeaseDocumentResource;
use App\Models\Lease;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewLeaseDocument extends ViewRecord
{
    protected static string $resource = LeaseDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn (): bool => $this->record->can_edit),

            Actions\Action::make('download')
                ->label('Download')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn (): ?string => $this->record->getDownloadUrl())
                ->openUrlInNewTab(),

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
                                        ($lease->tenant?->full_name ?? 'Unknown') . ' - ' .
                                        ($lease->unit?->unit_number ?? 'Unknown')
                                ]);
                        })
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $lease = Lease::find($data['lease_id']);
                    if ($lease && $this->record->linkToLease($lease, auth()->user())) {
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

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Document Details')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('title')
                                    ->label('Title'),
                                Infolists\Components\TextEntry::make('document_type')
                                    ->label('Type')
                                    ->formatStateUsing(fn (string $state): string =>
                                        \App\Models\LeaseDocument::getDocumentTypes()[$state] ?? $state
                                    ),
                                Infolists\Components\TextEntry::make('status')
                                    ->badge(),
                            ]),

                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('zone.name')
                                    ->label('Zone'),
                                Infolists\Components\TextEntry::make('property.name')
                                    ->label('Property'),
                                Infolists\Components\TextEntry::make('document_year')
                                    ->label('Year'),
                            ]),

                        Infolists\Components\TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull()
                            ->placeholder('No description'),
                    ]),

                Infolists\Components\Section::make('File Information')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('original_filename')
                                    ->label('Original Filename'),
                                Infolists\Components\TextEntry::make('mime_type')
                                    ->label('File Type'),
                                Infolists\Components\TextEntry::make('file_size_for_humans')
                                    ->label('Original Size'),
                                Infolists\Components\TextEntry::make('quality')
                                    ->badge(),
                            ]),

                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\IconEntry::make('is_compressed')
                                    ->label('Compressed')
                                    ->boolean(),
                                Infolists\Components\TextEntry::make('compressed_size_for_humans')
                                    ->label('Compressed Size')
                                    ->placeholder('N/A'),
                                Infolists\Components\TextEntry::make('compression_ratio')
                                    ->label('Compression Ratio')
                                    ->suffix('%')
                                    ->placeholder('N/A'),
                                Infolists\Components\TextEntry::make('compression_savings')
                                    ->label('Space Saved')
                                    ->placeholder('N/A'),
                            ]),

                        Infolists\Components\TextEntry::make('file_hash')
                            ->label('File Hash (SHA-256)')
                            ->fontFamily('mono')
                            ->size('sm')
                            ->copyable()
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Workflow History')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('uploader.name')
                                    ->label('Uploaded By'),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Uploaded At')
                                    ->dateTime(),
                            ]),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('reviewer.name')
                                    ->label('Reviewed By')
                                    ->placeholder('Not reviewed'),
                                Infolists\Components\TextEntry::make('reviewed_at')
                                    ->label('Reviewed At')
                                    ->dateTime()
                                    ->placeholder('Not reviewed'),
                            ]),

                        Infolists\Components\TextEntry::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->visible(fn ($record) => $record->status === DocumentStatus::REJECTED)
                            ->columnSpanFull()
                            ->color('danger'),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('linker.name')
                                    ->label('Linked By')
                                    ->placeholder('Not linked'),
                                Infolists\Components\TextEntry::make('linked_at')
                                    ->label('Linked At')
                                    ->dateTime()
                                    ->placeholder('Not linked'),
                            ]),

                        Infolists\Components\TextEntry::make('lease.reference_number')
                            ->label('Linked Lease')
                            ->placeholder('Not linked')
                            ->url(fn ($record) => $record->lease_id
                                ? route('filament.admin.resources.leases.view', $record->lease_id)
                                : null
                            ),
                    ]),

                Infolists\Components\Section::make('Notes')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('')
                            ->placeholder('No notes')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }
}
