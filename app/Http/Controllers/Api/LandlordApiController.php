<?php

namespace App\Http\Controllers\Api;

use App\Models\Landlord;
use Illuminate\Http\JsonResponse;

class LandlordApiController
{
    /**
     * Display a listing of landlords.
     */
    public function index(): JsonResponse
    {
        $landlords = Landlord::with(['properties', 'leases'])
            ->paginate(15);

        return response()->json($landlords);
    }

    /**
     * Display the specified landlord.
     */
    public function show(Landlord $landlord): JsonResponse
    {
        $landlord->load(['properties', 'leases']);

        return response()->json([
            'landlord' => $landlord,
            'active_leases' => $landlord->activeLeaseCount(),
            'total_monthly_rent' => $landlord->totalActiveRent(),
        ]);
    }
}
