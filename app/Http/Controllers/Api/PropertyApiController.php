<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\JsonResponse;

class PropertyApiController extends Controller
{
    /**
     * Display a listing of properties.
     * Scoped by zone for zone managers/field officers; admins see all.
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();
        $query = Property::query();

        if (! $user->isSuperAdmin() && ! $user->isAdmin()) {
            if ($user->hasZoneRestriction() && $user->zone_id) {
                $query->where('zone_id', $user->zone_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $properties = $query->with(['landlord', 'units'])
            ->paginate(15);

        return response()->json($properties);
    }

    /**
     * Display the specified property.
     * Authorized only if the property is in the user's zone (or user is admin).
     */
    public function show(Property $property): JsonResponse
    {
        $user = auth()->user();
        if (! $user->isSuperAdmin() && ! $user->isAdmin()) {
            if (! $user->hasZoneRestriction() || $property->zone_id !== $user->zone_id) {
                abort(403, 'You do not have access to this property.');
            }
        }

        $property->load(['landlord', 'units', 'leases']);

        return response()->json([
            'property' => $property,
            'occupancy_rate' => $property->occupancyRate(),
            'total_monthly_rent' => $property->totalMonthlyRent(),
        ]);
    }
}
