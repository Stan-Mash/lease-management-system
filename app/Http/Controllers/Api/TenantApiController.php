<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

class TenantApiController extends Controller
{
    /**
     * Display a listing of tenants.
     * Scoped to tenants that appear in leases accessible by the current user (zone/admin).
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();
        $accessibleTenantIds = Lease::accessibleByUser($user)->pluck('tenant_id')->unique()->filter();

        $tenants = Tenant::whereIn('id', $accessibleTenantIds)
            ->paginate(15);

        return response()->json($tenants);
    }

    /**
     * Display the specified tenant.
     * Authorized only if the tenant has at least one lease accessible by the current user.
     */
    public function show(Tenant $tenant): JsonResponse
    {
        $user = auth()->user();
        $hasAccess = Lease::accessibleByUser($user)->where('tenant_id', $tenant->id)->exists();

        if (! $hasAccess) {
            abort(403, 'You do not have access to this tenant.');
        }

        $tenant->load('leases');

        return response()->json($tenant);
    }
}
