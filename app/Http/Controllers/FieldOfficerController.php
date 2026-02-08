<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\PendingLeaseResource;
use App\Models\Lease;
use App\Models\LeaseApproval;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FieldOfficerController extends Controller
{
    /**
     * API: Get approval overview dashboard data.
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $baseLeaseQuery = $this->scopedLeaseQuery()
                ->where('workflow_state', 'pending_landlord_approval')
                ->whereNotNull('landlord_id');

            $totalPending = (clone $baseLeaseQuery)->count();

            $overdueCount = (clone $baseLeaseQuery)
                ->where('created_at', '<', now()->subHours(24))
                ->count();

            // PostgreSQL-compatible approval stats query
            // Uses EXTRACT(EPOCH FROM ...) instead of MySQL's TIMESTAMPDIFF
            $approvalStats = DB::table('lease_approvals')
                ->selectRaw("
                    COUNT(CASE WHEN decision = 'approved' AND reviewed_at::date = ? THEN 1 END) as approved_today,
                    COUNT(CASE WHEN decision = 'rejected' AND reviewed_at::date = ? THEN 1 END) as rejected_today,
                    COUNT(CASE WHEN decision = 'approved' AND reviewed_at >= ? THEN 1 END) as approved_last_7_days,
                    COUNT(CASE WHEN decision = 'rejected' AND reviewed_at >= ? THEN 1 END) as rejected_last_7_days,
                    AVG(CASE WHEN decision = 'approved' AND reviewed_at IS NOT NULL AND created_at >= ?
                        THEN EXTRACT(EPOCH FROM (reviewed_at - created_at)) / 3600 END) as avg_hours
                ", [today(), today(), now()->subDays(7), now()->subDays(7), now()->subDays(30)])
                ->first();

            return response()->json([
                'success' => true,
                'stats' => [
                    'total_pending' => $totalPending,
                    'overdue_count' => $overdueCount,
                    'approved_today' => (int) ($approvalStats->approved_today ?? 0),
                    'rejected_today' => (int) ($approvalStats->rejected_today ?? 0),
                    'approved_last_7_days' => (int) ($approvalStats->approved_last_7_days ?? 0),
                    'rejected_last_7_days' => (int) ($approvalStats->rejected_last_7_days ?? 0),
                    'avg_approval_time_hours' => $approvalStats->avg_hours ? round((float) $approvalStats->avg_hours, 1) : null,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data.',
            ], 500);
        }
    }

    /**
     * API: Get all pending approvals with pagination.
     */
    public function pendingApprovals(Request $request): JsonResponse
    {
        try {
            $perPage = min((int) $request->get('per_page', 20), 100);

            $pendingLeases = $this->scopedLeaseQuery()
                ->where('workflow_state', 'pending_landlord_approval')
                ->whereNotNull('landlord_id')
                ->with(['landlord:id,name,phone,email', 'tenant:id,name,phone,email', 'approvals' => function ($query): void {
                    $query->latest()->limit(1);
                }])
                ->orderBy('created_at', 'asc')
                ->paginate($perPage);

            return PendingLeaseResource::collection($pendingLeases)
                ->additional(['success' => true])
                ->response();
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pending approvals.',
            ], 500);
        }
    }

    /**
     * API: Get pending approvals grouped by landlord.
     */
    public function pendingByLandlord(Request $request): JsonResponse
    {
        try {
            $limit = min((int) $request->get('limit', 500), 1000);

            $leasesByLandlord = $this->scopedLeaseQuery()
                ->where('workflow_state', 'pending_landlord_approval')
                ->whereNotNull('landlord_id')
                ->with(['landlord:id,name,phone,email', 'tenant:id,name,phone,email'])
                ->limit($limit)
                ->get()
                ->groupBy('landlord_id')
                ->map(function ($leases) {
                    $landlord = $leases->first()->landlord;

                    return [
                        'landlord' => [
                            'id' => $landlord->id,
                            'name' => $landlord->name,
                            'phone' => $landlord->phone,
                            'email' => $landlord->email,
                        ],
                        'pending_count' => $leases->count(),
                        'oldest_pending_hours' => $leases->min('created_at')->diffInHours(now()),
                        'total_rent_value' => $leases->sum('monthly_rent'),
                        'leases' => $leases->map(function ($lease) {
                            return [
                                'id' => $lease->id,
                                'reference_number' => $lease->reference_number,
                                'tenant_name' => $lease->tenant->name,
                                'monthly_rent' => $lease->monthly_rent,
                                'submitted_at' => $lease->created_at->toISOString(),
                                'pending_hours' => $lease->created_at->diffInHours(now()),
                                'is_overdue' => $lease->created_at < now()->subHours(24),
                            ];
                        })->values(),
                    ];
                })
                ->sortByDesc('pending_count')
                ->values();

            return response()->json([
                'success' => true,
                'landlords_count' => $leasesByLandlord->count(),
                'data' => $leasesByLandlord,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch data by landlord.',
            ], 500);
        }
    }

    /**
     * API: Get overdue approvals (>24 hours) with pagination.
     */
    public function overdueApprovals(Request $request): JsonResponse
    {
        try {
            $perPage = min((int) $request->get('per_page', 20), 100);

            $overdueLeases = $this->scopedLeaseQuery()
                ->where('workflow_state', 'pending_landlord_approval')
                ->whereNotNull('landlord_id')
                ->where('created_at', '<', now()->subHours(24))
                ->with(['landlord:id,name,phone,email', 'tenant:id,name,phone,email'])
                ->orderBy('created_at', 'asc')
                ->paginate($perPage);

            $overdueLeases->getCollection()->transform(function ($lease) {
                return [
                    'id' => $lease->id,
                    'reference_number' => $lease->reference_number,
                    'landlord' => [
                        'id' => $lease->landlord->id,
                        'name' => $lease->landlord->name,
                        'phone' => $lease->landlord->phone,
                    ],
                    'tenant' => [
                        'name' => $lease->tenant->name,
                        'phone' => $lease->tenant->phone,
                    ],
                    'monthly_rent' => $lease->monthly_rent,
                    'submitted_at' => $lease->created_at->toISOString(),
                    'overdue_hours' => $lease->created_at->diffInHours(now()),
                    'overdue_days' => $lease->created_at->diffInDays(now()),
                ];
            });

            return response()->json([
                'success' => true,
                'overdue_count' => $overdueLeases->total(),
                'leases' => $overdueLeases,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch overdue approvals.',
            ], 500);
        }
    }

    /**
     * API: Get approval history with pagination.
     */
    public function approvalHistory(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'days' => 'nullable|integer|min:1|max:365',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);
            $days = (int) $request->get('days', 7);
            $perPage = min((int) $request->get('per_page', 20), 100);

            $query = LeaseApproval::whereNotNull('decision')
                ->where('reviewed_at', '>=', now()->subDays($days))
                ->with(['lease:id,reference_number,monthly_rent,landlord_id,tenant_id',
                    'lease.landlord:id,name',
                    'lease.tenant:id,name'])
                ->orderBy('reviewed_at', 'desc');

            // Get summary counts from the full query (before pagination)
            $approvedCount = (clone $query)->where('decision', 'approved')->count();
            $rejectedCount = (clone $query)->where('decision', 'rejected')->count();

            $approvals = $query->paginate($perPage);

            $approvals->getCollection()->transform(function ($approval) {
                return [
                    'id' => $approval->id,
                    'lease_reference' => $approval->lease->reference_number,
                    'landlord_name' => $approval->lease->landlord->name,
                    'tenant_name' => $approval->lease->tenant->name,
                    'monthly_rent' => $approval->lease->monthly_rent,
                    'decision' => $approval->decision,
                    'comments' => $approval->comments,
                    'rejection_reason' => $approval->rejection_reason,
                    'reviewed_at' => $approval->reviewed_at->toISOString(),
                    'approval_time_hours' => $approval->created_at->diffInHours($approval->reviewed_at),
                ];
            });

            return response()->json([
                'success' => true,
                'period_days' => $days,
                'total_count' => $approvals->total(),
                'approved_count' => $approvedCount,
                'rejected_count' => $rejectedCount,
                'history' => $approvals,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch approval history.',
            ], 500);
        }
    }

    /**
     * API: Get lease approval status details.
     */
    public function leaseApprovalStatus(Request $request, int $leaseId): JsonResponse
    {
        try {
            $lease = $this->scopedLeaseQuery()
                ->with(['landlord:id,name,phone,email',
                    'tenant:id,name,phone,email',
                    'approvals' => function ($query) {
                        $query->latest();
                    }])
                ->findOrFail($leaseId);

            $latestApproval = $lease->approvals->first();

            return response()->json([
                'success' => true,
                'lease' => [
                    'id' => $lease->id,
                    'reference_number' => $lease->reference_number,
                    'workflow_state' => $lease->workflow_state,
                    'landlord' => [
                        'id' => $lease->landlord->id,
                        'name' => $lease->landlord->name,
                        'phone' => $lease->landlord->phone,
                    ],
                    'tenant' => [
                        'name' => $lease->tenant->name,
                        'phone' => $lease->tenant->phone,
                    ],
                    'monthly_rent' => $lease->monthly_rent,
                    'submitted_at' => $lease->created_at->toISOString(),
                ],
                'approval_status' => [
                    'has_pending' => $lease->hasPendingApproval(),
                    'has_been_approved' => $lease->hasBeenApproved(),
                    'has_been_rejected' => $lease->hasBeenRejected(),
                    'latest_approval' => $latestApproval ? [
                        'decision' => $latestApproval->decision,
                        'comments' => $latestApproval->comments,
                        'rejection_reason' => $latestApproval->rejection_reason,
                        'reviewed_at' => $latestApproval->reviewed_at?->toISOString(),
                        'approval_time_hours' => $latestApproval->reviewed_at ?
                            $latestApproval->created_at->diffInHours($latestApproval->reviewed_at) : null,
                    ] : null,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lease not found.',
            ], 404);
        }
    }

    /**
     * Scope lease queries to the authenticated field officer's assignments.
     */
    private function scopedLeaseQuery(): Builder
    {
        $user = auth()->user();

        $query = Lease::query();

        // Field officers only see their own assigned leases
        if ($user->isFieldOfficer()) {
            $query->where('assigned_field_officer_id', $user->id);
        } elseif ($user->hasZoneRestriction()) {
            $query->where('zone_id', $user->zone_id);
        }

        return $query;
    }
}
