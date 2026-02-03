<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\DocumentQuality;
use App\Enums\DocumentSource;
use App\Enums\DocumentStatus;
use App\Filament\Resources\LeaseDocumentResource\Pages;
use App\Models\LeaseDocument;
use App\Models\Property;
use App\Models\Zone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeaseDocumentResource extends Resource
{
    protected static ?string $model = LeaseDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-up';

    protected static ?string $navigationLabel = 'Document Upload';

    protected static ?string $navigationGroup = 'Document Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::pendingReview()->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $count = static::getModel()::pendingReview()->count();
        return $count > 10 ? 'danger' : ($count > 0 ? 'warning' : 'success');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Document Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('zone_id')
                                    ->label('Zone')
                                    ->relationship('zone', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set) => $set('property_id', null)),

                                Forms\Components\Select::make('property_id')
                                    ->label('Property')
                                    ->options(function (Forms\Get $get) {
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

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('document_type')
                                    ->label('Document Type')
                                    ->options(LeaseDocument::getDocumentTypes())
                                    ->required(),

                                Forms\Components\TextInput::make('document_year')
                                    ->label('Document Year')
                                    ->numeric()
                                    ->minValue(1990)
                                    ->maxValue(date('Y'))
                                    ->default(date('Y')),
                            ]),

                        Forms\Components\TextInput::make('title')
                            ->label('Document Title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Lease Agreement - John Doe - Unit 5A'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->placeholder('Optional notes about this document'),
                    ]),

                Forms\Components\Section::make('Upload')
                    ->schema([
                        Forms\Components\FileUpload::make('file_path')
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

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('quality')
                                    ->label('Document Quality')
                                    ->options(DocumentQuality::class)
                                    ->required()
                                    ->helperText('Rate the scan quality'),

                                Forms\Components\Select::make('source')
                                    ->label('Source')
                                    ->options(DocumentSource::class)
                                    ->default(DocumentSource::SCANNED)
                                    ->required(),
                            ]),

                        Forms\Components\DatePicker::make('document_date')
                            ->label('Date on Document')
                            ->helperText('The date shown on the physical document'),
                    ]),

                Forms\Components\Section::make('Additional Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
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
                    ->toggleable(),

                Tables\Columns\TextColumn::make('property.name')
                    ->label('Property')
                    ->sortable()
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
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('lease.reference_number')
                    ->label('Linked Lease')
                    ->placeholder('Not linked')
                    ->toggleable(),
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
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->visible(fn (LeaseDocument $record): bool => $record->can_edit),

                    Tables\Actions\Action::make('download')
                        ->label('Download')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->url(fn (LeaseDocument $record): ?string => $record->getDownloadUrl())
                        ->openUrlInNewTab(),

                    Tables\Actions\Action::make('preview')
                        ->label('Preview')
                        ->icon('heroicon-o-eye')
                        ->url(fn (LeaseDocument $record): ?string => $record->getPreviewUrl())
                        ->openUrlInNewTab()
                        ->visible(fn (LeaseDocument $record): bool => $record->getPreviewUrl() !== null),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn (LeaseDocument $record): bool => $record->can_delete),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->hasAnyRole(['super_admin', 'admin'])),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
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
            'create' => Pages\CreateLeaseDocument::route('/create'),
            'view' => Pages\ViewLeaseDocument::route('/{record}'),
            'edit' => Pages\EditLeaseDocument::route('/{record}/edit'),
            'upload' => Pages\BulkUploadDocuments::route('/bulk-upload'),
            'review' => Pages\ReviewQueue::route('/review'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Zone managers can only see their zone's documents
        $user = auth()->user();
        if ($user && $user->hasRole('zone_manager') && $user->zone_id) {
            $query->where('zone_id', $user->zone_id);
        }

        return $query;
    }
}
