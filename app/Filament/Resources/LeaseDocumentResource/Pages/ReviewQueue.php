<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeaseDocumentResource\Pages;

use App\Enums\DocumentQuality;
use App\Enums\DocumentStatus;
use App\Filament\Resources\LeaseDocumentResource;
use App\Models\Lease;
use App\Models\LeaseDocument;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReviewQueue extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = LeaseDocumentResource::class;

    protected static string $view = 'filament.resources.lease-document-resource.pages.review-queue';

    protected static ?string $title = 'Document Review Queue';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                LeaseDocument::query()
                    ->whereIn('status', [DocumentStatus::PENDING_REVIEW, DocumentStatus::IN_REVIEW])
                    ->where('uploaded_by', '!=', auth()->id()) // Can't review own uploads
                    ->orderBy('created_at', 'asc') // Oldest first (FIFO)
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Document')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn (LeaseDocument $record): string => $record->title),

                Tables\Columns\TextColumn::make('zone.name')
                    ->label('Zone')
                    ->sortable(),

                Tables\Columns\TextColumn::make('property.name')
                    ->label('Property')
                    ->limit(20),

                Tables\Columns\TextColumn::make('document_type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string =>
                        LeaseDocument::getDocumentTypes()[$state] ?? $state
                    )
                    ->badge(),

                Tables\Columns\TextColumn::make('quality')
                    ->badge(),

                Tables\Columns\TextColumn::make('status')
                    ->badge(),

                Tables\Columns\TextColumn::make('file_size_for_humans')
                    ->label('Size'),

                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Uploaded By'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waiting Since')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('zone_id')
                    ->relationship('zone', 'name')
                    ->label('Zone')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('quality')
                    ->options(DocumentQuality::class)
                    ->label('Quality'),

                Tables\Filters\SelectFilter::make('document_type')
                    ->options(LeaseDocument::getDocumentTypes())
                    ->label('Type'),

                Tables\Filters\Filter::make('quality_issues')
                    ->query(fn (Builder $query): Builder => $query->needsAttention())
                    ->label('Quality Issues')
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->url(fn (LeaseDocument $record): ?string => $record->getPreviewUrl())
                    ->openUrlInNewTab()
                    ->visible(fn (LeaseDocument $record): bool => $record->getPreviewUrl() !== null),

                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (LeaseDocument $record): ?string => $record->getDownloadUrl())
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Document')
                    ->modalDescription(fn (LeaseDocument $record): string =>
                        "Approve \"{$record->title}\"?"
                    )
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes (optional)')
                            ->rows(2),
                    ])
                    ->action(function (LeaseDocument $record, array $data): void {
                        if ($record->approve(auth()->user(), $data['notes'] ?? null)) {
                            Notification::make()
                                ->title('Document approved')
                                ->success()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
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
                                'wrong_property' => 'Wrong property/zone selected',
                                'other' => 'Other reason',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('details')
                            ->label('Additional Details')
                            ->rows(2)
                            ->required(),
                    ])
                    ->action(function (LeaseDocument $record, array $data): void {
                        $reason = $data['reason_type'] . ': ' . $data['details'];
                        if ($record->reject(auth()->user(), $reason)) {
                            Notification::make()
                                ->title('Document rejected')
                                ->body('The uploader will be notified.')
                                ->warning()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('quickLink')
                    ->label('Approve & Link')
                    ->icon('heroicon-o-link')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('lease_id')
                            ->label('Select Lease')
                            ->options(function (LeaseDocument $record) {
                                // Show leases from same property first
                                return Lease::query()
                                    ->when($record->property_id, function ($query) use ($record) {
                                        $query->where('property_id', $record->property_id);
                                    })
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
                    ->action(function (LeaseDocument $record, array $data): void {
                        // First approve
                        if (!$record->approve(auth()->user())) {
                            Notification::make()
                                ->title('Failed to approve document')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Refresh the record
                        $record->refresh();

                        // Then link
                        $lease = Lease::find($data['lease_id']);
                        if ($lease && $record->linkToLease($lease, auth()->user())) {
                            Notification::make()
                                ->title('Document approved and linked')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Approved but failed to link')
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulkApprove')
                    ->label('Approve Selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Selected Documents')
                    ->modalDescription('Are you sure you want to approve all selected documents?')
                    ->action(function (\Illuminate\Support\Collection $records): void {
                        $approved = 0;
                        foreach ($records as $record) {
                            if ($record->approve(auth()->user())) {
                                $approved++;
                            }
                        }
                        Notification::make()
                            ->title("{$approved} document(s) approved")
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),

                Tables\Actions\BulkAction::make('bulkReject')
                    ->label('Reject Selected')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Selected Documents')
                    ->form([
                        Forms\Components\Select::make('reason_type')
                            ->label('Reason')
                            ->options([
                                'poor_quality' => 'Poor scan quality',
                                'wrong_document' => 'Wrong document type',
                                'incomplete' => 'Incomplete document',
                                'duplicate' => 'Duplicate document',
                                'other' => 'Other',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('details')
                            ->label('Details')
                            ->required(),
                    ])
                    ->action(function (\Illuminate\Support\Collection $records, array $data): void {
                        $rejected = 0;
                        $reason = $data['reason_type'] . ': ' . $data['details'];
                        foreach ($records as $record) {
                            if ($record->reject(auth()->user(), $reason)) {
                                $rejected++;
                            }
                        }
                        Notification::make()
                            ->title("{$rejected} document(s) rejected")
                            ->warning()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->emptyStateHeading('No documents pending review')
            ->emptyStateDescription('All documents have been reviewed. Great job!')
            ->emptyStateIcon('heroicon-o-check-badge')
            ->poll('30s');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to Documents')
                ->icon('heroicon-o-arrow-left')
                ->url(fn (): string => static::$resource::getUrl('index'))
                ->color('gray'),

            Actions\Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->resetTable()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LeaseDocumentResource\Widgets\ReviewQueueStats::class,
        ];
    }
}
