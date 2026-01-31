<?php

namespace App\Http\Controllers;

use App\Models\Landlord;
use App\Models\Lease;
use App\Services\LandlordApprovalService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class LandlordApprovalController extends Controller
{
    /**
     * Display pending leases for a landlord.
     * For use in Landlord Mobile/Web App
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request, int $landlordId)
    {
        $landlord = $this->verifyLandlordOwnership($landlordId);

        // Get pending approvals for this landlord
        $pendingLeases = Lease::where('landlord_id', $landlordId)
            ->where('workflow_state', 'pending_landlord_approval')
            ->with(['tenant', 'approvals' => function ($query) {
                $query->latest();
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Get approval history
        $approvedLeases = Lease::where('landlord_id', $landlordId)
            ->whereHas('approvals', function ($query) {
                $query->where('decision', 'approved');
            })
            ->with(['tenant', 'approvals' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        $rejectedLeases = Lease::where('landlord_id', $landlordId)
            ->whereHas('approvals', function ($query) {
                $query->where('decision', 'rejected');
            })
            ->with(['tenant', 'approvals' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        return view('landlord.approvals.index', compact(
            'landlord',
            'pendingLeases',
            'approvedLeases',
            'rejectedLeases',
        ));
    }

    /**
     * Show lease details for landlord review.
     *
     * @return \Illuminate\View\View
     */
    public function show(Request $request, int $landlordId, int $leaseId)
    {
        $landlord = $this->verifyLandlordOwnership($landlordId);
        $lease = Lease::where('id', $leaseId)
            ->where('landlord_id', $landlordId)
            ->with(['tenant', 'guarantors', 'approvals'])
            ->firstOrFail();

        $approval = $lease->getLatestApproval();

        return view('landlord.approvals.show', compact('landlord', 'lease', 'approval'));
    }

    /**
     * Approve a lease (landlord action).
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve(Request $request, int $landlordId, int $leaseId)
    {
        $request->validate([
            'comments' => 'nullable|string|max:1000',
        ]);

        $landlord = $this->verifyLandlordOwnership($landlordId);
        $lease = Lease::where('id', $leaseId)
            ->where('landlord_id', $landlordId)
            ->where('workflow_state', 'pending_landlord_approval')
            ->firstOrFail();

        $result = LandlordApprovalService::approveLease(
            $lease,
            $request->comments,
            'both', // Send email + SMS
        );

        if ($result['success']) {
            return redirect()
                ->route('landlord.approvals.index', $landlordId)
                ->with('success', 'Lease approved successfully! Tenant has been notified.');
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Reject a lease (landlord action).
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reject(Request $request, int $landlordId, int $leaseId)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:255',
            'comments' => 'nullable|string|max:1000',
        ]);

        $landlord = $this->verifyLandlordOwnership($landlordId);
        $lease = Lease::where('id', $leaseId)
            ->where('landlord_id', $landlordId)
            ->where('workflow_state', 'pending_landlord_approval')
            ->firstOrFail();

        $result = LandlordApprovalService::rejectLease(
            $lease,
            $request->rejection_reason,
            $request->comments,
            'both', // Send email + SMS
        );

        if ($result['success']) {
            return redirect()
                ->route('landlord.approvals.index', $landlordId)
                ->with('success', 'Lease rejected. Tenant has been notified to revise.');
        }

        return back()->with('error', $result['message']);
    }

    // ============== API ENDPOINTS FOR MOBILE APPS ==============

    /**
     * API: Get pending leases for landlord.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiIndex(Request $request, int $landlordId)
    {
        try {
            $landlord = $this->verifyLandlordOwnership($landlordId);

            $pendingLeases = Lease::where('landlord_id', $landlordId)
                ->where('workflow_state', 'pending_landlord_approval')
                ->with(['tenant:id,name,phone,email', 'approvals' => function ($query) {
                    $query->latest()->limit(1);
                }])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($lease) {
                    return [
                        'id' => $lease->id,
                        'reference_number' => $lease->reference_number,
                        'tenant' => [
                            'name' => $lease->tenant->name,
                            'phone' => $lease->tenant->phone,
                            'email' => $lease->tenant->email,
                        ],
                        'lease_type' => ucfirst($lease->lease_type),
                        'monthly_rent' => $lease->monthly_rent,
                        'currency' => $lease->currency ?? 'KES',
                        'security_deposit' => $lease->security_deposit ?? $lease->deposit_amount,
                        'start_date' => $lease->start_date?->format('Y-m-d'),
                        'end_date' => $lease->end_date?->format('Y-m-d'),
                        'created_at' => $lease->created_at->toISOString(),
                    ];
                });

            return response()->json([
                'success' => true,
                'landlord' => [
                    'id' => $landlord->id,
                    'name' => $landlord->name,
                ],
                'pending_count' => $pendingLeases->count(),
                'leases' => $pendingLeases,
            ]);
        } catch (Exception $e) {
            Log::error('API: Failed to fetch pending leases', [
                'landlord_id' => $landlordId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pending leases.',
            ], 500);
        }
    }

    /**
     * API: Get lease details for landlord.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiShow(Request $request, int $landlordId, int $leaseId)
    {
        try {
            $lease = Lease::where('id', $leaseId)
                ->where('landlord_id', $landlordId)
                ->with(['tenant', 'guarantors', 'approvals'])
                ->firstOrFail();

            $approval = $lease->getLatestApproval();

            return response()->json([
                'success' => true,
                'lease' => [
                    'id' => $lease->id,
                    'reference_number' => $lease->reference_number,
                    'workflow_state' => $lease->workflow_state,
                    'lease_type' => ucfirst($lease->lease_type),
                    'lease_source' => $lease->lease_source,
                    'monthly_rent' => $lease->monthly_rent,
                    'currency' => $lease->currency ?? 'KES',
                    'security_deposit' => $lease->security_deposit ?? $lease->deposit_amount,
                    'start_date' => $lease->start_date?->format('Y-m-d'),
                    'end_date' => $lease->end_date?->format('Y-m-d'),
                    'property_address' => $lease->property_address,
                    'special_terms' => $lease->special_terms,
                    'tenant' => [
                        'name' => $lease->tenant->name,
                        'phone' => $lease->tenant->phone,
                        'email' => $lease->tenant->email,
                        'id_number' => $lease->tenant->id_number,
                    ],
                    'guarantors' => $lease->guarantors->map(function ($g) {
                        return [
                            'name' => $g->name,
                            'phone' => $g->phone,
                            'relationship' => $g->relationship,
                            'guarantee_amount' => $g->guarantee_amount,
                        ];
                    }),
                    'approval' => $approval ? [
                        'status' => $approval->decision ?? 'pending',
                        'comments' => $approval->comments,
                        'rejection_reason' => $approval->rejection_reason,
                        'reviewed_at' => $approval->reviewed_at?->toISOString(),
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
     * API: Approve lease (landlord mobile app).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiApprove(Request $request, int $landlordId, int $leaseId)
    {
        $request->validate([
            'comments' => 'nullable|string|max:1000',
        ]);

        try {
            $lease = Lease::where('id', $leaseId)
                ->where('landlord_id', $landlordId)
                ->where('workflow_state', 'pending_landlord_approval')
                ->firstOrFail();

            $result = LandlordApprovalService::approveLease(
                $lease,
                $request->comments,
                'both',
            );

            return response()->json($result);
        } catch (Exception $e) {
            Log::error('API: Lease approval failed', [
                'landlord_id' => $landlordId,
                'lease_id' => $leaseId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve lease: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * API: Reject lease (landlord mobile app).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiReject(Request $request, int $landlordId, int $leaseId)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:255',
            'comments' => 'nullable|string|max:1000',
        ]);

        try {
            $lease = Lease::where('id', $leaseId)
                ->where('landlord_id', $landlordId)
                ->where('workflow_state', 'pending_landlord_approval')
                ->firstOrFail();

            $result = LandlordApprovalService::rejectLease(
                $lease,
                $request->rejection_reason,
                $request->comments,
                'both',
            );

            return response()->json($result);
        } catch (Exception $e) {
            Log::error('API: Lease rejection failed', [
                'landlord_id' => $landlordId,
                'lease_id' => $leaseId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject lease: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Verify the authenticated user owns the given landlord record.
     */
    private function verifyLandlordOwnership(int $landlordId): Landlord
    {
        $landlord = Landlord::findOrFail($landlordId);
        $user = auth()->user();

        // Super admins and admins can access any landlord
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return $landlord;
        }

        // Field officers can access landlords in their zone
        if ($user->isFieldOfficer() && $landlord->zone_id === $user->zone_id) {
            return $landlord;
        }

        // Zone managers can access landlords in their zone
        if ($user->isZoneManager() && $landlord->zone_id === $user->zone_id) {
            return $landlord;
        }

        // Check if the user is directly linked to this landlord
        if ($user->landlord_id === $landlord->id) {
            return $landlord;
        }

        throw new AccessDeniedHttpException('You are not authorized to access this landlord\'s data.');
    }
}
