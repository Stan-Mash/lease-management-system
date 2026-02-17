<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\DocumentQuality;
use App\Enums\DocumentSource;
use App\Enums\DocumentStatus;
use App\Filament\Resources\LeaseDocumentResource\Pages;
use App\Models\DocumentAudit;
use App\Models\Lease;
use App\Models\LeaseDocument;
use App\Models\Property;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class LeaseDocumentResource extends Resource
{
    protected static ?string $model = LeaseDocument::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Document Vault';

    protected static string|UnitEnum|null $navigationGroup = 'Lease Portfolio';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $slug = 'documents';

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getModel()::pendingReview()->count();
        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $count = static::getModel()::pendingReview()->count();
        return $count > 10 ? 'danger' : ($count > 0 ? 'warning' : 'success');
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Pending review';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Document Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('zone_id')
                                    ->label('Zone')
                                    ->relationship('zone', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('property_id', null)),

                                Select::make('property_id')
                                    ->label('Property')
                                    ->options(function (Get $get) {
                                        $zoneId = $get('zone_id');
                                        if (!$zoneId) {
                                            return [];
                                        }
                                        return Property::where('zone_id', $zoneId)
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->required(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('document_type')
                                    ->label('Document Type')
                                    ->options(LeaseDocument::getDocumentTypes())
                                    ->required(),

                                TextInput::make('document_year')
                                    ->label('Document Year')
                                    ->numeric()
                                    ->minValue(1990)
                                    ->maxValue(date('Y'))
                                    ->default(date('Y')),
                            ]),

                        TextInput::make('title')
                            ->label('Document Title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Lease Agreement - John Doe - Unit 5A'),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->placeholder('Optional notes about this document'),
                    ]),

                Section::make('Upload')
                    ->schema([
                        FileUpload::make('file_path')
                            ->label('Document File')
                            ->required()
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'image/jpeg',
                                'image/png',
                                'image/tiff',
                            ])
                            ->maxSize(25 * 1024) // 25MB
                            ->directory('lease-documents/uploads')
                            ->preserveFilenames()
                            ->downloadable()
                            ->openable()
                            ->helperText('Accepted: PDF, DOC, DOCX, JPG, PNG, TIFF. Max size: 25MB'),

                        Grid::make(2)
                            ->schema([
                                Select::make('quality')
                                    ->label('Document Quality')
                                    ->options(DocumentQuality::class)
                                    ->required()
                                    ->helperText('Rate the scan quality'),

                                Select::make('source')
                                    ->label('Source')
                                    ->options(DocumentSource::class)
                                    ->default(DocumentSource::SCANNED)
                                    ->required(),
                            ]),

                        DatePicker::make('document_date')
                            ->label('Date on Document')
                            ->helperText('The date shown on the physical document'),
                    ]),

                Section::make('Additional Notes')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->placeholder('Any additional notes for the reviewer'),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn (LeaseDocument $record): string => $record->title),

                Tables\Columns\TextColumn::make('zone.name')
                    ->label('Zone')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('property.property_name')
                    ->label('Property')
                    ->sortable()
                    ->searchable()
                    ->limit(20)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('document_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => LeaseDocument::getDocumentTypes()[$state] ?? $state),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),

                Tables\Columns\TextColumn::make('quality')
                    ->label('Quality')
                    ->badge()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('file_size_for_humans')
                    ->label('Size')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_compressed')
                    ->label('Compressed')
                    ->boolean()
                    ->trueIcon('heroicon-o-archive-box-arrow-down')
                    ->falseIcon('heroicon-o-minus')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Uploaded By')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->description(fn (LeaseDocument $record): string => $record->created_at->diffForHumans()),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->since()
                    ->sortable()
                    ->badge()
                    ->color(fn (LeaseDocument $record): string =>
                        $record->updated_at->isToday() ? 'success' :
                        ($record->updated_at->isCurrentWeek() ? 'info' : 'gray')
                    )
                    ->tooltip(fn (LeaseDocument $record): string => $record->updated_at->format('M j, Y H:i:s'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('lease.reference_number')
                    ->label('Linked Lease')
                    ->searchable()
                    ->placeholder('Not linked')
                    ->toggleable(),

                // Full-text search columns (hidden but searchable)
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('original_filename')
                    ->label('Original File')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(DocumentStatus::class)
                    ->multiple()
                    ->label('Status'),

                Tables\Filters\SelectFilter::make('quality')
                    ->options(DocumentQuality::class)
                    ->multiple()
                    ->label('Quality'),

                Tables\Filters\SelectFilter::make('zone_id')
                    ->relationship('zone', 'name')
                    ->label('Zone')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('source')
                    ->options(DocumentSource::class)
                    ->label('Source'),

                Tables\Filters\SelectFilter::make('document_type')
                    ->options(LeaseDocument::getDocumentTypes())
                    ->label('Document Type'),

                Tables\Filters\Filter::make('unlinked')
                    ->query(fn (Builder $query): Builder => $query->whereNull('lease_id'))
                    ->label('Unlinked Documents')
                    ->toggle(),

                Tables\Filters\Filter::make('needs_attention')
                    ->query(fn (Builder $query): Builder => $query->needsAttention())
                    ->label('Quality Issues')
                    ->toggle(),

                Tables\Filters\Filter::make('my_uploads')
                    ->query(fn (Builder $query): Builder => $query->where('uploaded_by', auth()->id()))
                    ->label('My Uploads')
                    ->toggle(),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        DatePicker::make('uploaded_from')
                            ->label('Uploaded From'),
                        DatePicker::make('uploaded_until')
                            ->label('Uploaded Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['uploaded_from'], fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['uploaded_until'], fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['uploaded_from'] ?? null) {
                            $indicators[] = 'From ' . $data['uploaded_from'];
                        }
                        if ($data['uploaded_until'] ?? null) {
                            $indicators[] = 'Until ' . $data['uploaded_until'];
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make()
                    ->visible(fn (LeaseDocument $record): bool => $record->can_edit),

                Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (LeaseDocument $record): ?string => $record->getDownloadUrl())
                    ->openUrlInNewTab(),

                // Inline preview modal — shows document in an iframe within a slide-over
                Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn (LeaseDocument $record): string => 'Preview: ' . $record->title)
                    ->modalContent(fn (LeaseDocument $record) => view('filament.resources.lease-document-resource.components.inline-preview', [
                        'previewUrl' => $record->getPreviewUrl(),
                        'mimeType' => $record->mime_type,
                        'filename' => $record->original_filename,
                    ]))
                    ->modalWidth('7xl')
                    ->slideOver()
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->visible(fn (LeaseDocument $record): bool => $record->getPreviewUrl() !== null),

                Actions\DeleteAction::make()
                    ->visible(fn (LeaseDocument $record): bool => $record->can_delete),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    // Bulk link to lease — select approved documents and link them all to a lease
                    Actions\BulkAction::make('bulkLinkToLease')
                        ->label('Link to Lease')
                        ->icon('heroicon-o-link')
                        ->color('primary')
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Link Selected Documents to Lease')
                        ->modalDescription('Select a lease to link the chosen documents to. Only approved documents will be linked.')
                        ->form([
                            Select::make('lease_id')
                                ->label('Select Lease')
                                ->options(function () {
                                    return Lease::query()
                                        ->with(['tenant', 'unit'])
                                        ->latest()
                                        ->limit(200)
                                        ->get()
                                        ->mapWithKeys(fn ($lease) => [
                                            $lease->id => $lease->reference_number . ' - ' .
                                                ($lease->tenant?->names ?? 'Unknown') . ' - ' .
                                                ($lease->unit?->unit_number ?? 'Unknown'),
                                        ]);
                                })
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $lease = Lease::find($data['lease_id']);
                            if (!$lease) {
                                Notification::make()
                                    ->title('Lease not found')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $linked = 0;
                            $skipped = 0;

                            foreach ($records as $document) {
                                if ($document->status === DocumentStatus::APPROVED) {
                                    if ($document->linkToLease($lease, auth()->user())) {
                                        $document->logAudit(
                                            DocumentAudit::ACTION_LINK,
                                            'Bulk linked to lease ' . $lease->reference_number . ' by ' . auth()->user()->name,
                                            newValues: ['lease_id' => $lease->id]
                                        );
                                        $linked++;
                                    } else {
                                        $skipped++;
                                    }
                                } else {
                                    $skipped++;
                                }
                            }

                            Notification::make()
                                ->title("Bulk Link Complete")
                                ->body("{$linked} document(s) linked to lease {$lease->reference_number}." .
                                    ($skipped > 0 ? " {$skipped} skipped (not approved or already linked)." : ''))
                                ->success()
                                ->send();
                        })
                        ->visible(fn (): bool => auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager'])),

                    // Bulk approve
                    Actions\BulkAction::make('bulkApprove')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Approve Selected Documents')
                        ->modalDescription('Are you sure you want to approve all selected documents?')
                        ->action(function (Collection $records): void {
                            $approved = 0;
                            foreach ($records as $document) {
                                if ($document->canBeReviewedBy(auth()->user()) && $document->approve(auth()->user())) {
                                    $document->logAudit(
                                        DocumentAudit::ACTION_APPROVE,
                                        'Bulk approved by ' . auth()->user()->name
                                    );
                                    $approved++;
                                }
                            }

                            Notification::make()
                                ->title("{$approved} document(s) approved")
                                ->success()
                                ->send();
                        })
                        ->visible(fn (): bool => auth()->user()?->hasAnyRole(['super_admin', 'admin', 'it_officer'])),

                    Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->hasAnyRole(['super_admin', 'admin'])),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->poll('30s')
            ->searchPlaceholder('Search by title, description, filename, property, uploader, or lease reference...');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaseDocuments::route('/'),
            'my-uploads' => Pages\MyUploads::route('/my-uploads'),
            'upload' => Pages\DocumentUploadCenter::route('/upload'),
            'review' => Pages\ReviewQueue::route('/review'),
            'view' => Pages\ViewLeaseDocument::route('/{record}'),
            'edit' => Pages\EditLeaseDocument::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();
        if (!$user) {
            return $query;
        }

        // Field officers: only see documents for their assigned leases
        if ($user->role === 'field_officer') {
            return $query->whereHas('lease', fn ($q) => $q->where('assigned_field_officer_id', $user->id));
        }

        // Zone-restricted users: filter by zone
        if ($user->hasZoneRestriction() && $user->zone_id) {
            return $query->where('zone_id', $user->zone_id);
        }

        return $query;
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        return $user && !in_array($user->role, ['auditor', 'internal_auditor']);
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        return $user && $user->role !== 'field_officer' && !in_array($user->role, ['auditor', 'internal_auditor']);
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();
        return $user && in_array($user->role, ['super_admin', 'admin', 'property_manager']);
    }
}
