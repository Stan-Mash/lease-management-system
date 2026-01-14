<?php

namespace App\Http\Controllers;

use App\Models\Lease;
use App\Models\LeaseApproval;
use App\Models\Landlord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FieldOfficerController extends Controller
{
    /**
     * API: Get approval overview dashboard data.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboard(Request $request)
    {
        try {
            $totalPending = Lease::where('workflow_state', 'pending_landlord_approval')
                ->whereNotNull('landlord_id')
                ->count();

            $overdueCount = Lease::where('workflow_state', 'pending_landlord_approval')
                ->whereNotNull('landlord_id')
                ->where('created_at', '<', now()->subHours(24))
                ->count();

            $approvedToday = LeaseApproval::where('decision', 'approved')
                ->whereDate('reviewed_at', today())
                ->count();

            $rejectedToday = LeaseApproval::where('decision', 'rejected')
                ->whereDate('reviewed_at', today())
                ->count();

            $approvedLast7Days = LeaseApproval::where('decision', 'approved')
                ->where('reviewed_at', '>=', now()->subDays(7))
                ->count();

            $rejectedLast7Days = LeaseApproval::where('decision', 'rejected')
                ->where('reviewed_at', '>=', now()->subDays(7))
                ->count();

            // Average approval time in hours
            $avgApprovalTime = LeaseApproval::where('decision', 'approved')
                ->whereNotNull('reviewed_at')
                ->where('created_at', '>=', now()->subDays(30))
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, reviewed_at)) as avg_hours')
                ->value('avg_hours');

            return response()->json([
                'success' => true,
                'stats' => [
                    'total_pending' => $totalPending,
                    'overdue_count' => $overdueCount,
                    'approved_today' => $approvedToday,
                    'rejected_today' => $rejectedToday,
                    'approved_last_7_days' => $approvedLast7Days,
                    'rejected_last_7_days' => $rejectedLast7Days,
                    'avg_approval_time_hours' => $avgApprovalTime ? round($avgApprovalTime, 1) : null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data.',
            ], 500);
        }
    }

    /**
     * API: Get all pending approvals.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pendingApprovals(Request $request)
    {
        try {
            $pendingLeases = Lease::where('workflow_state', 'pending_landlord_approval')
                ->whereNotNull('landlord_id')
                ->with(['landlord:id,name,phone,email', 'tenant:id,name,phone,email', 'approvals' => function ($query) {
                    $query->latest()->limit(1);
                }])
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($lease) {
                    return [
                        'id' => $lease->id,
                        'reference_number' => $lease->reference_number,
                        'landlord' => [
                            'id' => $lease->landlord->id,
                            'name' => $lease->landlord->name,
                            'phone' => $lease->landlord->phone,
                            'email' => $lease->landlord->email,
                        ],
                        'tenant' => [
                            'name' => $lease->tenant->name,
                            'phone' => $lease->tenant->phone,
                            'email' => $lease->tenant->email,
                        ],
                        'lease_type' => ucfirst(str_replace('_', ' ', $lease->lease_type)),
                        'monthly_rent' => $lease->monthly_rent,
                        'currency' => $lease->currency ?? 'KES',
                        'submitted_at' => $lease->created_at->toISOString(),
                        'pending_hours' => $lease->created_at->diffInHours(now()),
                        'is_overdue' => $lease->created_at < now()->subHours(24),
                    ];
                });

            return response()->json([
                'success' => true,
                'pending_count' => $pendingLeases->count(),
                'leases' => $pendingLeases,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pending approvals.',
            ], 500);
        }
    }

    /**
     * API: Get pending approvals grouped by landlord.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pendingByLandlord(Request $request)
    {
        try {
            $leasesByLandlord = Lease::where('workflow_state', 'pending_landlord_approval')
                ->whereNotNull('landlord_id')
                ->with(['landlord:id,name,phone,email', 'tenant:id,name,phone,email'])
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch data by landlord.',
            ], 500);
        }
    }

    /**
     * API: Get overdue approvals (>24 hours).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function overdueApprovals(Request $request)
    {
        try {
            $overdueLeases = Lease::where('workflow_state', 'pending_landlord_approval')
                ->whereNotNull('landlord_id')
                ->where('created_at', '<', now()->subHours(24))
                ->with(['landlord:id,name,phone,email', 'tenant:id,name,phone,email'])
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($lease) {
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
                'overdue_count' => $overdueLeases->count(),
                'leases' => $overdueLeases,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch overdue approvals.',
            ], 500);
        }
    }

    /**
     * API: Get approval history.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function approvalHistory(Request $request)
    {
        try {
            $days = $request->get('days', 7); // Default to last 7 days

            $approvals = LeaseApproval::whereNotNull('decision')
                ->where('reviewed_at', '>=', now()->subDays($days))
                ->with(['lease:id,reference_number,monthly_rent,landlord_id,tenant_id',
                        'lease.landlord:id,name',
                        'lease.tenant:id,name'])
                ->orderBy('reviewed_at', 'desc')
                ->get()
                ->map(function ($approval) {
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

            $approvedCount = $approvals->where('decision', 'approved')->count();
            $rejectedCount = $approvals->where('decision', 'rejected')->count();

            return response()->json([
                'success' => true,
                'period_days' => $days,
                'total_count' => $approvals->count(),
                'approved_count' => $approvedCount,
                'rejected_count' => $rejectedCount,
                'history' => $approvals,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch approval history.',
            ], 500);
        }
    }

    /**
     * API: Get lease approval status details.
     *
     * @param Request $request
     * @param int $leaseId
     * @return \Illuminate\Http\JsonResponse
     */
    public function leaseApprovalStatus(Request $request, int $leaseId)
    {
        try {
            $lease = Lease::with(['landlord:id,name,phone,email',
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lease not found.',
            ], 404);
        }
    }
}
