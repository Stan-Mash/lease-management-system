<?php

namespace App\Http\Controllers\Api;

use App\Models\Property;
use Illuminate\Http\JsonResponse;

class PropertyApiController
{
    /**
     * Display a listing of properties.
     */
    public function index(): JsonResponse
    {
        $properties = Property::with(['landlord', 'units'])
            ->paginate(15);

        return response()->json($properties);
    }

    /**
     * Display the specified property.
     */
    public function show(Property $property): JsonResponse
    {
        $property->load(['landlord', 'units', 'leases']);

        return response()->json([
            'property' => $property,
            'occupancy_rate' => $property->occupancyRate(),
            'total_monthly_rent' => $property->totalMonthlyRent(),
        ]);
    }
}
