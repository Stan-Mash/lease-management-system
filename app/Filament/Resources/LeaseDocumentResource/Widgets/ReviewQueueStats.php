<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeaseDocumentResource\Widgets;

use App\Models\LeaseDocument;
use App\Models\Zone;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ReviewQueueStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $userId = auth()->id();

        // Documents waiting for review (excluding user's own uploads)
        $pendingCount = LeaseDocument::pendingReview()
            ->where('uploaded_by', '!=', $userId)
            ->count();

        // Oldest document waiting
        $oldestPending = LeaseDocument::pendingReview()
            ->where('uploaded_by', '!=', $userId)
            ->orderBy('created_at', 'asc')
            ->first();

        $waitingTime = $oldestPending
            ? $oldestPending->created_at->diffForHumans(['parts' => 2])
            : 'N/A';

        // Documents with quality issues
        $qualityIssues = LeaseDocument::pendingReview()
            ->where('uploaded_by', '!=', $userId)
            ->needsAttention()
            ->count();

        // Today's reviewed by current user
        $reviewedToday = LeaseDocument::where('reviewed_by', $userId)
            ->whereDate('reviewed_at', today())
            ->count();

        // Breakdown by zone
        $byZone = LeaseDocument::pendingReview()
            ->where('uploaded_by', '!=', $userId)
            ->select('zone_id', DB::raw('count(*) as count'))
            ->groupBy('zone_id')
            ->with('zone')
            ->get()
            ->map(fn ($item) => [
                'zone' => $item->zone?->name ?? 'Unknown',
                'count' => $item->count,
            ])
            ->sortByDesc('count')
            ->take(3)
            ->values();

        $zoneBreakdown = $byZone->map(fn ($item) => "{$item['zone']}: {$item['count']}")->join(', ');

        return [
            Stat::make('Pending Review', number_format($pendingCount))
                ->description($pendingCount > 0 ? "Oldest waiting: {$waitingTime}" : 'Queue is empty!')
                ->icon('heroicon-o-inbox')
                ->color($pendingCount > 50 ? 'danger' : ($pendingCount > 20 ? 'warning' : 'success')),

            Stat::make('Quality Issues', number_format($qualityIssues))
                ->description('Documents flagged as poor/illegible')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($qualityIssues > 0 ? 'danger' : 'success'),

            Stat::make('Reviewed Today', number_format($reviewedToday))
                ->description('Documents you reviewed today')
                ->icon('heroicon-o-check-badge')
                ->color('info'),

            Stat::make('By Zone', $byZone->count() > 0 ? $byZone->first()['zone'] : 'N/A')
                ->description($zoneBreakdown ?: 'No pending documents')
                ->icon('heroicon-o-map-pin')
                ->color('gray'),
        ];
    }
}
