<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use Exception;
use Illuminate\Http\JsonResponse;

class LeaseApiController extends Controller
{
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
     * Verify lease authenticity via QR code.
     */
    public function verify(Lease $lease): JsonResponse
    {
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
    public function transition(Lease $lease): JsonResponse
    {
        $this->authorize('update', $lease);

        request()->validate([
            'new_state' => ['required', 'string'],
        ]);

        try {
            $lease->transitionTo(request('new_state'));

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
