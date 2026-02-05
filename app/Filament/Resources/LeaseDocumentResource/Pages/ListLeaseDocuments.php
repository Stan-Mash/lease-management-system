<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeaseDocumentResource\Pages;

use App\Enums\DocumentStatus;
use App\Filament\Resources\LeaseDocumentResource;
use App\Filament\Resources\LeaseDocumentResource\Widgets\DocumentStatsOverview;
use Filament\Actions;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListLeaseDocuments extends ListRecords
{
    protected static string $resource = LeaseDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('uploadCenter')
                ->label('Upload Center')
                ->icon('heroicon-o-cloud-arrow-up')
                ->url(fn (): string => static::$resource::getUrl('upload'))
                ->color('primary'),

            Actions\Action::make('myUploads')
                ->label('My Uploads')
                ->icon('heroicon-o-folder-open')
                ->url(fn (): string => static::$resource::getUrl('my-uploads'))
                ->color('info')
                ->badge(fn (): ?string => (string) \App\Models\LeaseDocument::where('uploaded_by', auth()->id())->count() ?: null),

            Actions\Action::make('reviewQueue')
                ->label('Review Queue')
                ->icon('heroicon-o-clipboard-document-check')
                ->url(fn (): string => static::$resource::getUrl('review'))
                ->color('warning')
                ->badge(fn (): ?string => (string) \App\Models\LeaseDocument::pendingReview()->count() ?: null),
        ];
    }

    public function getTabs(): array
    {
        $userId = auth()->id();

        return [
            'all' => Tab::make('All Documents')
                ->icon('heroicon-o-document-duplicate'),

            'my_uploads' => Tab::make('My Uploads')
                ->icon('heroicon-o-folder-open')
                ->badge(fn () => \App\Models\LeaseDocument::where('uploaded_by', $userId)->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('uploaded_by', $userId)),

            'pending' => Tab::make('Pending Review')
                ->icon('heroicon-o-clock')
                ->badge(fn () => \App\Models\LeaseDocument::pendingReview()->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->pendingReview()),

            'approved' => Tab::make('Approved')
                ->icon('heroicon-o-check-circle')
                ->badge(fn () => \App\Models\LeaseDocument::approved()->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->approved()),

            'linked' => Tab::make('Linked')
                ->icon('heroicon-o-link')
                ->badge(fn () => \App\Models\LeaseDocument::linked()->count())
                ->badgeColor('primary')
                ->modifyQueryUsing(fn (Builder $query) => $query->linked()),

            'rejected' => Tab::make('Rejected')
                ->icon('heroicon-o-x-circle')
                ->badge(fn () => \App\Models\LeaseDocument::rejected()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->rejected()),

            'quality_issues' => Tab::make('Quality Issues')
                ->icon('heroicon-o-exclamation-triangle')
                ->badge(fn () => \App\Models\LeaseDocument::needsAttention()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->needsAttention()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DocumentStatsOverview::class,
        ];
    }
}
