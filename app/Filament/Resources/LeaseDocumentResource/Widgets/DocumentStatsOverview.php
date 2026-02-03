<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeaseDocumentResource\Widgets;

use App\Enums\DocumentStatus;
use App\Models\LeaseDocument;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DocumentStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalDocuments = LeaseDocument::count();
        $pendingReview = LeaseDocument::pendingReview()->count();
        $approved = LeaseDocument::approved()->count();
        $linked = LeaseDocument::linked()->count();
        $rejected = LeaseDocument::rejected()->count();
        $qualityIssues = LeaseDocument::needsAttention()->count();

        // Calculate storage used
        $totalSize = LeaseDocument::sum('file_size');
        $compressedSize = LeaseDocument::where('is_compressed', true)->sum('compressed_size');
        $uncompressedSize = LeaseDocument::where('is_compressed', false)->sum('file_size');
        $actualStorageUsed = $compressedSize + $uncompressedSize;
        $savedSpace = $totalSize - $actualStorageUsed;

        return [
            Stat::make('Total Documents', number_format($totalDocuments))
                ->description('All uploaded documents')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray'),

            Stat::make('Pending Review', number_format($pendingReview))
                ->description('Awaiting review')
                ->icon('heroicon-o-clock')
                ->color($pendingReview > 50 ? 'danger' : ($pendingReview > 10 ? 'warning' : 'success')),

            Stat::make('Approved', number_format($approved))
                ->description('Ready to link')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Linked', number_format($linked))
                ->description('Attached to leases')
                ->icon('heroicon-o-link')
                ->color('primary'),

            Stat::make('Rejected', number_format($rejected))
                ->description('Need re-upload')
                ->icon('heroicon-o-x-circle')
                ->color('danger'),

            Stat::make('Storage Used', $this->formatBytes($actualStorageUsed))
                ->description($savedSpace > 0 ? 'Saved ' . $this->formatBytes($savedSpace) . ' via compression' : 'No compression savings')
                ->icon('heroicon-o-server')
                ->color('info'),
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}
