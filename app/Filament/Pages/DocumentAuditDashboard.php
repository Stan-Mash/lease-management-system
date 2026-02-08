<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\DocumentQuality;
use App\Enums\DocumentStatus;
use App\Models\DocumentAudit;
use App\Models\LeaseDocument;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class DocumentAuditDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Audit Dashboard';

    protected static ?string $title = 'Document Audit Dashboard';

    protected static string|UnitEnum|null $navigationGroup = 'Lease Portfolio';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'document-audit-dashboard';

    protected string $view = 'filament.pages.document-audit-dashboard';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'admin', 'system_admin', 'it_officer', 'audit']);
    }

    public function getViewData(): array
    {
        return [
            'statsCards' => $this->getStatsCards(),
            'recentActivity' => $this->getRecentActivity(),
            'activityByCategory' => $this->getActivityByCategory(),
            'topUploaders' => $this->getTopUploaders(),
            'actionBreakdown' => $this->getActionBreakdown(),
        ];
    }

    /**
     * Get the four primary stat card values.
     *
     * @return array<string, array{value: int, label: string, icon: string, color: string, description: string}>
     */
    protected function getStatsCards(): array
    {
        $totalDocuments = LeaseDocument::count();

        $pendingReview = LeaseDocument::where('status', DocumentStatus::PENDING_REVIEW)
            ->orWhere('status', DocumentStatus::IN_REVIEW)
            ->count();

        $qualityIssues = LeaseDocument::whereIn('quality', [
            DocumentQuality::POOR,
            DocumentQuality::ILLEGIBLE,
        ])->count();

        $integrityFailures = DocumentAudit::where('integrity_verified', false)->count();

        return [
            'total_documents' => [
                'value' => $totalDocuments,
                'label' => 'Total Documents',
                'icon' => 'heroicon-o-document-duplicate',
                'color' => 'primary',
                'description' => 'All documents in the system',
            ],
            'pending_review' => [
                'value' => $pendingReview,
                'label' => 'Pending Review',
                'icon' => 'heroicon-o-clock',
                'color' => 'warning',
                'description' => 'Documents awaiting review',
            ],
            'quality_issues' => [
                'value' => $qualityIssues,
                'label' => 'Quality Issues',
                'icon' => 'heroicon-o-exclamation-triangle',
                'color' => 'danger',
                'description' => 'Poor or illegible documents',
            ],
            'integrity_failures' => [
                'value' => $integrityFailures,
                'label' => 'Integrity Failures',
                'icon' => 'heroicon-o-shield-exclamation',
                'color' => 'danger',
                'description' => 'Failed integrity checks',
            ],
        ];
    }

    /**
     * Get the last 50 audit trail entries with related data.
     */
    protected function getRecentActivity(): Collection
    {
        return DocumentAudit::with(['user', 'document'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (DocumentAudit $audit) => [
                'id' => $audit->id,
                'created_at' => $audit->created_at,
                'user_name' => $audit->user?->name ?? 'System',
                'action' => $audit->action,
                'action_label' => $audit->action_label,
                'action_color' => $audit->action_color,
                'action_icon' => $audit->action_icon,
                'document_title' => $audit->document?->title ?? 'Deleted Document',
                'description' => $audit->description,
                'ip_address' => $audit->ip_address,
                'action_category' => $audit->action_category,
                'integrity_verified' => $audit->integrity_verified,
            ]);
    }

    /**
     * Get audit counts grouped by action_category.
     *
     * @return array<string, array{count: int, label: string, icon: string, color: string}>
     */
    protected function getActivityByCategory(): array
    {
        $counts = DocumentAudit::select('action_category', DB::raw('count(*) as count'))
            ->groupBy('action_category')
            ->pluck('count', 'action_category')
            ->toArray();

        $categoryMeta = [
            DocumentAudit::CATEGORY_ACCESS => [
                'label' => 'Access',
                'icon' => 'heroicon-o-eye',
                'color' => 'info',
            ],
            DocumentAudit::CATEGORY_MODIFICATION => [
                'label' => 'Modification',
                'icon' => 'heroicon-o-pencil-square',
                'color' => 'warning',
            ],
            DocumentAudit::CATEGORY_WORKFLOW => [
                'label' => 'Workflow',
                'icon' => 'heroicon-o-arrow-path',
                'color' => 'success',
            ],
            DocumentAudit::CATEGORY_INTEGRITY => [
                'label' => 'Integrity',
                'icon' => 'heroicon-o-shield-check',
                'color' => 'danger',
            ],
        ];

        $result = [];

        foreach ($categoryMeta as $key => $meta) {
            $result[$key] = [
                'count' => $counts[$key] ?? 0,
                'label' => $meta['label'],
                'icon' => $meta['icon'],
                'color' => $meta['color'],
            ];
        }

        return $result;
    }

    /**
     * Get top 10 users by upload count.
     *
     * @return Collection<int, array{name: string, email: string, upload_count: int}>
     */
    protected function getTopUploaders(): Collection
    {
        return DocumentAudit::select('user_id', DB::raw('count(*) as upload_count'))
            ->where('action', DocumentAudit::ACTION_UPLOAD)
            ->groupBy('user_id')
            ->orderByDesc('upload_count')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $user = User::find($row->user_id);

                return [
                    'name' => $user?->name ?? 'Unknown User',
                    'email' => $user?->email ?? '-',
                    'upload_count' => (int) $row->upload_count,
                ];
            });
    }

    /**
     * Get action counts for a per-action breakdown.
     *
     * @return array<string, int>
     */
    protected function getActionBreakdown(): array
    {
        return DocumentAudit::select('action', DB::raw('count(*) as count'))
            ->groupBy('action')
            ->orderByDesc('count')
            ->pluck('count', 'action')
            ->toArray();
    }
}
