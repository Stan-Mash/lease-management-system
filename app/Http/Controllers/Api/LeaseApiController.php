<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaseTransitionRequest;
use App\Models\Lease;
use App\Services\QRCodeService;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaseApiController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of leases.
     *
     * Scoped to leases accessible by the authenticated user's role/zone.
     */
    public function index(): JsonResponse
    {
        $leases = Lease::accessibleByUser(auth()->user())
            ->with(['tenant', 'property', 'landlord', 'unit'])
            ->paginate(15);

        return response()->json($leases);
    }

    /**
     * Display the specified lease.
     */
    public function show(Lease $lease): JsonResponse
    {
        $this->authorize('view', $lease);

        $lease->load(['tenant', 'property', 'landlord', 'unit']);

        return response()->json($lease);
    }

    /**
     * Deprecated: verification by lease ID is disabled (IDOR risk).
     * Returns 410 Gone so clients migrate to verifyBySerialAndHash with serial + hash.
     */
    public function verifyDeprecated(Lease $lease): JsonResponse
    {
        return response()->json([
            'error' => 'gone',
            'message' => 'This endpoint has been deprecated. Use GET /api/v1/verify/lease with query parameters serial and hash (from the lease QR code or verification data) instead.',
            'new_endpoint' => url('/api/v1/verify/lease'),
        ], 410);
    }

    /**
     * Verify lease authenticity via serial + hash (public endpoint).
     * Does not accept lease ID to prevent IDOR; requires proof (hash) from QR/data.
     */
    public function verifyBySerialAndHash(Request $request): JsonResponse
    {
        $request->validate([
            'serial' => ['required', 'string', 'max:100'],
            'hash' => ['required', 'string', 'max:255'],
        ]);

        $lease = Lease::where('serial_number', $request->input('serial'))
            ->orWhere('reference_number', $request->input('serial'))
            ->first();

        if (! $lease || ! QRCodeService::verifyHash($lease, $request->input('hash'))) {
            return response()->json([
                'verified' => false,
                'error' => 'Lease not found or verification code invalid.',
            ], 404);
        }

        return response()->json([
            'verified' => true,
            'lease' => [
                'reference_number' => $lease->reference_number,
                'serial_number' => $lease->serial_number,
                'tenant_name' => $lease->tenant?->full_name,
                'property_name' => $lease->property?->name,
                'start_date' => $lease->start_date?->format('Y-m-d'),
                'end_date' => $lease->end_date?->format('Y-m-d'),
                'monthly_rent' => $lease->monthly_rent,
                'workflow_state' => $lease->workflow_state,
            ],
        ]);
    }

    /**
     * Transition lease to a new state.
     */
    public function transition(LeaseTransitionRequest $request, Lease $lease): JsonResponse
    {
        $this->authorize('update', $lease);

        try {
            $lease->transitionTo($request->validated('new_state'));

            return response()->json([
                'message' => 'Lease transitioned successfully',
                'lease' => [
                    'id' => $lease->id,
                    'workflow_state' => $lease->workflow_state,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
