<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeaseDocumentResource\Pages;

use App\Enums\DocumentStatus;
use App\Filament\Resources\LeaseDocumentResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListLeaseDocuments extends ListRecords
{
    protected static string $resource = LeaseDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('bulkUpload')
                ->label('Bulk Upload')
                ->icon('heroicon-o-cloud-arrow-up')
                ->url(fn (): string => static::$resource::getUrl('upload'))
                ->color('primary'),

            Actions\Action::make('reviewQueue')
                ->label('Review Queue')
                ->icon('heroicon-o-clipboard-document-check')
                ->url(fn (): string => static::$resource::getUrl('review'))
                ->color('warning')
                ->badge(fn (): ?string => (string) \App\Models\LeaseDocument::pendingReview()->count() ?: null),

            Actions\CreateAction::make()
                ->label('Single Upload'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Documents')
                ->icon('heroicon-o-document-duplicate'),

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
            LeaseDocumentResource\Widgets\DocumentStatsOverview::class,
        ];
    }
}
