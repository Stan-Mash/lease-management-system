<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware that resolves "acting" (delegation) permissions.
 *
 * When a user has `acting_for_user_id` set, this middleware:
 *  1. Merges the zone manager's zone-scoped permissions into the current
 *     request context (ensures Spatie's cache reflects the direct permissions
 *     granted during delegation activation).
 *  2. Stores the zone manager's details on the request as an `acting_for`
 *     attribute so that audit logs and UI components can reference it.
 *
 * Register this middleware in the Filament panel's authMiddleware stack
 * (AdminPanelProvider) so it runs on every authenticated request:
 *
 *     ->authMiddleware([
 *         Authenticate::class,
 *         \App\Http\Middleware\ResolveActingPermissions::class,
 *     ])
 */
class ResolveActingPermissions
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->acting_for_user_id) {
            return $next($request);
        }

        $this->resolveActingContext($request, $user);

        return $next($request);
    }

    /**
     * Load the zone manager the current user is acting for, merge any
     * zone-scoped permissions, and set request attributes for downstream
     * consumers (audit logging, UI banners, etc.).
     */
    protected function resolveActingContext(Request $request, User $user): void
    {
        $zoneManager = User::find($user->acting_for_user_id);

        if (! $zoneManager) {
            // The referenced user no longer exists; clear the stale reference
            // so future requests skip this path.
            Log::warning(
                "ResolveActingPermissions: acting_for_user_id [{$user->acting_for_user_id}] "
                . "on user [{$user->id}] references a non-existent user. Clearing.",
            );

            $user->updateQuietly(['acting_for_user_id' => null]);

            return;
        }

        // Ensure Spatie's permission cache is fresh so that the direct
        // permissions granted during activateDelegation() are reflected.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Reload the user's permissions from the database. This guarantees
        // that any permissions added via givePermissionTo() during
        // activateDelegation() are available for gate/policy checks in
        // this request cycle.
        $user->unsetRelation('permissions');
        $user->unsetRelation('roles');

        // Expose the acting context on the request for audit logs and UI.
        $request->attributes->set('acting_for', [
            'id' => $zoneManager->id,
            'name' => $zoneManager->name,
            'role' => $zoneManager->role,
            'role_display' => $zoneManager->getRoleDisplayName(),
            'zone_id' => $zoneManager->zone_id,
        ]);

        // Also store a convenience flag.
        $request->attributes->set('is_acting', true);

        Log::debug(
            "ResolveActingPermissions: User [{$user->name}] (ID:{$user->id}) "
            . "is acting for [{$zoneManager->name}] (ID:{$zoneManager->id}).",
        );
    }
}
