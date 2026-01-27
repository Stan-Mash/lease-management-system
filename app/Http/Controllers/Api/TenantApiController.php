<?php

namespace App\Http\Controllers\Api;

use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

class TenantApiController
{
    /**
     * Display a listing of tenants.
     */
    public function index(): JsonResponse
    {
        $tenants = Tenant::paginate(15);
        return response()->json($tenants);
    }

    /**
     * Display the specified tenant.
     */
    public function show(Tenant $tenant): JsonResponse
    {
        $tenant->load('leases');
        return response()->json($tenant);
    }
}
