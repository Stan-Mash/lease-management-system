<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeaseDocumentResource\Pages;

use App\Enums\DocumentStatus;
use App\Filament\Resources\LeaseDocumentResource;
use App\Models\LeaseDocument;
use BackedEnum;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MyUploads extends ListRecords
{
    protected static string $resource = LeaseDocumentResource::class;

    protected static ?string $title = 'My Uploads';

    // Enable navigation for this page to appear in sidebar
    protected static bool $isNavigable = true;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder-open';

    protected static ?string $navigationLabel = 'My Uploads';

    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return true;
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Lease Portfolio';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = LeaseDocument::where('uploaded_by', auth()->id())->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $pendingCount = LeaseDocument::where('uploaded_by', auth()->id())
            ->pendingReview()
            ->count();

        return $pendingCount > 0 ? 'warning' : 'success';
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->where('uploaded_by', auth()->id());
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn (LeaseDocument $record): string => $record->title),

                Tables\Columns\TextColumn::make('document_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => LeaseDocument::getDocumentTypes()[$state] ?? $state),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),

                Tables\Columns\TextColumn::make('quality')
                    ->label('Quality')
                    ->badge(),

                Tables\Columns\TextColumn::make('zone.name')
                    ->label('Zone')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('property.name')
                    ->label('Property')
                    ->limit(20)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('file_size_for_humans')
                    ->label('Size'),

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
                    ->tooltip(fn (LeaseDocument $record): string => $record->updated_at->format('M j, Y H:i:s')),

                Tables\Columns\IconColumn::make('integrity_status')
                    ->label('Integrity')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-shield-exclamation')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(DocumentStatus::class)
                    ->multiple()
                    ->label('Status'),

                Tables\Filters\Filter::make('recent')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(7)))
                    ->label('Last 7 Days')
                    ->toggle(),

                Tables\Filters\Filter::make('pending')
                    ->query(fn (Builder $query): Builder => $query->pendingReview())
                    ->label('Pending Review')
                    ->toggle(),
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
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->emptyStateHeading('No uploads yet')
            ->emptyStateDescription('Start uploading lease documents to see them here.')
            ->emptyStateIcon('heroicon-o-document-arrow-up')
            ->emptyStateActions([
                Actions\Action::make('upload')
                    ->label('Upload Document')
                    ->icon('heroicon-o-cloud-arrow-up')
                    ->url(fn (): string => LeaseDocumentResource::getUrl('upload'))
                    ->color('primary'),
            ]);
    }

    public function getTabs(): array
    {
        $userId = auth()->id();

        return [
            'all' => Tab::make('All My Uploads')
                ->icon('heroicon-o-folder-open')
                ->badge(fn () => LeaseDocument::where('uploaded_by', $userId)->count()),

            'pending' => Tab::make('Pending')
                ->icon('heroicon-o-clock')
                ->badge(fn () => LeaseDocument::where('uploaded_by', $userId)->pendingReview()->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->pendingReview()),

            'approved' => Tab::make('Approved')
                ->icon('heroicon-o-check-circle')
                ->badge(fn () => LeaseDocument::where('uploaded_by', $userId)->approved()->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->approved()),

            'rejected' => Tab::make('Rejected')
                ->icon('heroicon-o-x-circle')
                ->badge(fn () => LeaseDocument::where('uploaded_by', $userId)->rejected()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->rejected()),

            'today' => Tab::make('Today')
                ->icon('heroicon-o-calendar')
                ->badge(fn () => LeaseDocument::where('uploaded_by', $userId)->whereDate('created_at', today())->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', today())),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('uploadCenter')
                ->label('Upload Center')
                ->icon('heroicon-o-cloud-arrow-up')
                ->url(fn (): string => LeaseDocumentResource::getUrl('upload'))
                ->color('primary'),

            Actions\Action::make('reviewQueue')
                ->label('Review Queue')
                ->icon('heroicon-o-clipboard-document-check')
                ->url(fn (): string => LeaseDocumentResource::getUrl('review'))
                ->color('warning')
                ->badge(fn (): ?string => (string) \App\Models\LeaseDocument::pendingReview()->count() ?: null),
        ];
    }
}
