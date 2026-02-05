<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Resources\LeaseDocumentResource;
use App\Filament\Resources\Leases\LeaseResource;
use App\Models\Lease;
use App\Models\LeaseDocument;
use BackedEnum;
use Filament\Pages\Page;
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

        // Lease Statistics
        $leaseQuery = Lease::query();
        if ($zoneFilter) {
            $leaseQuery->where('zone_id', $zoneFilter);
        }

        $leaseStats = [
            'total' => (clone $leaseQuery)->count(),
            'active' => (clone $leaseQuery)->where('workflow_state', 'active')->count(),
            'pending' => (clone $leaseQuery)->where('workflow_state', 'pending_landlord_approval')->count(),
            'draft' => (clone $leaseQuery)->where('workflow_state', 'draft')->count(),
            'expiring_soon' => (clone $leaseQuery)->where('workflow_state', 'active')
                ->whereBetween('end_date', [now(), now()->addDays(30)])->count(),
            'expired' => (clone $leaseQuery)->where('workflow_state', 'expired')->count(),
        ];

        // Document Statistics
        $docQuery = LeaseDocument::query();
        if ($zoneFilter) {
            $docQuery->where('zone_id', $zoneFilter);
        }

        $documentStats = [
            'total' => (clone $docQuery)->count(),
            'pending_review' => (clone $docQuery)->pendingReview()->count(),
            'approved' => (clone $docQuery)->approved()->count(),
            'linked' => (clone $docQuery)->linked()->count(),
            'unlinked' => (clone $docQuery)->whereNull('lease_id')->count(),
            'quality_issues' => (clone $docQuery)->needsAttention()->count(),
        ];

        // User-specific stats
        $myUploads = LeaseDocument::where('uploaded_by', auth()->id())->count();
        $myPendingUploads = LeaseDocument::where('uploaded_by', auth()->id())->pendingReview()->count();

        // Recent activity
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

        // Monthly trends (last 6 months) - PostgreSQL compatible
        $monthlyLeases = Lease::query()
            ->when($zoneFilter, fn ($q) => $q->where('zone_id', $zoneFilter))
            ->where('created_at', '>=', now()->subMonths(6))
            ->select(DB::raw("to_char(created_at, 'YYYY-MM') as month"), DB::raw('count(*) as count'))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $monthlyDocuments = LeaseDocument::query()
            ->when($zoneFilter, fn ($q) => $q->where('zone_id', $zoneFilter))
            ->where('created_at', '>=', now()->subMonths(6))
            ->select(DB::raw("to_char(created_at, 'YYYY-MM') as month"), DB::raw('count(*) as count'))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

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
