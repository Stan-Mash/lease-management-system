<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Resources\LeaseDocumentResource;
use App\Filament\Resources\Leases\LeaseResource;
use App\Models\Lease;
use App\Models\LeaseDocument;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class LeasePortfolio extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Portfolio Overview';

    protected static string|UnitEnum|null $navigationGroup = 'Lease Portfolio';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.lease-portfolio';

    protected static ?string $title = 'Lease Portfolio';

    protected static ?string $slug = 'lease-portfolio';

    public function getViewData(): array
    {
        $user = auth()->user();
        $zoneFilter = null;

        // Apply zone filtering for zone managers
        if ($user && method_exists($user, 'hasZoneRestriction') && $user->hasZoneRestriction() && $user->zone_id) {
            $zoneFilter = $user->zone_id;
        }

        $cacheKey = 'lease_portfolio:' . ($zoneFilter ?? 'all') . ':' . auth()->id();

        // Cache heavy statistics (5-min TTL) — consolidates 16+ queries into 2 cached blocks
        $leaseStats = Cache::remember($cacheKey . ':lease_stats', now()->addMinutes(5), function () use ($zoneFilter) {
            $q = Lease::query()->when($zoneFilter, fn ($q) => $q->where('zone_id', $zoneFilter));
            $todayStr = now()->toDateString();
            $thirtyDays = now()->addDays(30)->toDateString();

            $stats = (clone $q)->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN workflow_state = 'active' THEN 1 END) as active,
                COUNT(CASE WHEN workflow_state = 'pending_landlord_approval' THEN 1 END) as pending,
                COUNT(CASE WHEN workflow_state = 'draft' THEN 1 END) as draft,
                COUNT(CASE WHEN workflow_state = 'active' AND end_date >= ? AND end_date <= ? THEN 1 END) as expiring_soon,
                COUNT(CASE WHEN workflow_state = 'expired' THEN 1 END) as expired
            ", [$todayStr, $thirtyDays])->first();

            return [
                'total' => (int) $stats->total,
                'active' => (int) $stats->active,
                'pending' => (int) $stats->pending,
                'draft' => (int) $stats->draft,
                'expiring_soon' => (int) $stats->expiring_soon,
                'expired' => (int) $stats->expired,
            ];
        });

        $documentStats = Cache::remember($cacheKey . ':doc_stats', now()->addMinutes(5), function () use ($zoneFilter) {
            $q = LeaseDocument::query()->when($zoneFilter, fn ($q) => $q->where('zone_id', $zoneFilter));

            return [
                'total' => (clone $q)->count(),
                'pending_review' => (clone $q)->pendingReview()->count(),
                'approved' => (clone $q)->approved()->count(),
                'linked' => (clone $q)->linked()->count(),
                'unlinked' => (clone $q)->whereNull('lease_id')->count(),
                'quality_issues' => (clone $q)->needsAttention()->count(),
            ];
        });

        // User-specific stats (light queries, short cache)
        $userId = auth()->id();
        $myUploads = Cache::remember("lease_portfolio:my_uploads:{$userId}", now()->addMinutes(3), function () use ($userId) {
            return LeaseDocument::where('uploaded_by', $userId)->count();
        });
        $myPendingUploads = Cache::remember("lease_portfolio:my_pending:{$userId}", now()->addMinutes(3), function () use ($userId) {
            return LeaseDocument::where('uploaded_by', $userId)->pendingReview()->count();
        });

        // Recent activity (not cached — always fresh, but uses eager loading)
        $recentLeases = Lease::query()
            ->when($zoneFilter, fn ($q) => $q->where('zone_id', $zoneFilter))
            ->with(['tenant', 'property', 'unit'])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        $recentDocuments = LeaseDocument::query()
            ->when($zoneFilter, fn ($q) => $q->where('zone_id', $zoneFilter))
            ->with(['zone', 'property', 'uploader'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Monthly trends (cached 5 min)
        $monthlyLeases = Cache::remember($cacheKey . ':monthly_leases', now()->addMinutes(5), function () use ($zoneFilter) {
            return Lease::query()
                ->when($zoneFilter, fn ($q) => $q->where('zone_id', $zoneFilter))
                ->where('created_at', '>=', now()->subMonths(6))
                ->select(DB::raw("to_char(created_at, 'YYYY-MM') as month"), DB::raw('count(*) as count'))
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('count', 'month')
                ->toArray();
        });

        $monthlyDocuments = Cache::remember($cacheKey . ':monthly_docs', now()->addMinutes(5), function () use ($zoneFilter) {
            return LeaseDocument::query()
                ->when($zoneFilter, fn ($q) => $q->where('zone_id', $zoneFilter))
                ->where('created_at', '>=', now()->subMonths(6))
                ->select(DB::raw("to_char(created_at, 'YYYY-MM') as month"), DB::raw('count(*) as count'))
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('count', 'month')
                ->toArray();
        });

        return [
            'leaseStats' => $leaseStats,
            'documentStats' => $documentStats,
            'myUploads' => $myUploads,
            'myPendingUploads' => $myPendingUploads,
            'recentLeases' => $recentLeases,
            'recentDocuments' => $recentDocuments,
            'monthlyLeases' => $monthlyLeases,
            'monthlyDocuments' => $monthlyDocuments,
            'leaseResourceUrl' => LeaseResource::getUrl('index'),
            'documentResourceUrl' => LeaseDocumentResource::getUrl('index'),
            'createLeaseUrl' => LeaseResource::getUrl('create'),
            'uploadDocumentUrl' => LeaseDocumentResource::getUrl('upload'),
            'reviewQueueUrl' => LeaseDocumentResource::getUrl('review'),
            'myUploadsUrl' => LeaseDocumentResource::getUrl('my-uploads'),
        ];
    }
}
