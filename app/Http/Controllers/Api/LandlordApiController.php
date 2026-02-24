<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Landlord;
use Illuminate\Http\JsonResponse;

class LandlordApiController extends Controller
{
    /**
     * Display a listing of landlords.
     * Scoped by zone for zone managers/field officers; admins see all.
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();
        $query = Landlord::query();

        if (! $user->isSuperAdmin() && ! $user->isAdmin()) {
            if ($user->hasZoneRestriction() && $user->zone_id) {
                $query->where('zone_id', $user->zone_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $landlords = $query->with(['properties', 'leases'])
            ->paginate(15);

        return response()->json($landlords);
    }

    /**
     * Display the specified landlord.
     * Authorized only if the landlord is in the user's zone (or user is admin).
     */
    public function show(Landlord $landlord): JsonResponse
    {
        $user = auth()->user();
        if (! $user->isSuperAdmin() && ! $user->isAdmin()) {
            if (! $user->hasZoneRestriction() || $landlord->zone_id !== $user->zone_id) {
                abort(403, 'You do not have access to this landlord.');
            }
        }

        $landlord->load(['properties', 'leases']);

        return response()->json([
            'landlord' => $landlord,
            'active_leases' => $landlord->activeLeaseCount(),
            'total_monthly_rent' => $landlord->totalActiveRent(),
        ]);
    }
}
