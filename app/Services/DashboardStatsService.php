<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lease;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Provides cached dashboard statistics to prevent expensive COUNT(*) queries
 * from running on every page load.
 *
 * Stats are cached for 5 minutes (configurable via cache.dashboard_stats_ttl).
 * Cache is invalidated automatically via the LeaseObserver whenever a lease
 * workflow_state changes, ensuring counts stay accurate.
 *
 * Usage:
 *   $stats = DashboardStatsService::getAdminStats();
 *   $stats = DashboardStatsService::getZoneStats($zoneId);
 *
 * Invalidation (call from LeaseObserver::updated()):
 *   DashboardStatsService::invalidate($lease->zone_id);
 */
class DashboardStatsService
{
    /** Cache TTL in seconds — 5 minutes */
    private const TTL = 300;

    private const KEY_ADMIN  = 'dashboard:admin:stats';
    private const KEY_ZONE   = 'dashboard:zone:%d:stats';

    /**
     * Get company-wide dashboard statistics (admin / super admin view).
     */
    public static function getAdminStats(): array
    {
        return Cache::remember(self::KEY_ADMIN, self::TTL, function () {
            $tz = config('app.timezone', 'Africa/Nairobi');

            return [
                'active_leases'   => Lease::where('workflow_state', 'active')->count(),
                'draft_leases'    => Lease::where('workflow_state', 'draft')->count(),
                'pending_leases'  => Lease::where('workflow_state', 'pending_landlord_approval')->count(),
                'tenant_signed'   => Lease::where('workflow_state', 'tenant_signed')->count(),
                'expiring_30d'    => Lease::where('workflow_state', 'active')
                    ->whereBetween('end_date', [
                        Carbon::now($tz)->startOfDay(),
                        Carbon::now($tz)->addDays(30)->endOfDay(),
                    ])
                    ->count(),
                'expiring_7d'     => Lease::where('workflow_state', 'active')
                    ->whereBetween('end_date', [
                        Carbon::now($tz)->startOfDay(),
                        Carbon::now($tz)->addDays(7)->endOfDay(),
                    ])
                    ->count(),
                'generated_at'    => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Get zone-scoped statistics for a zone manager.
     */
    public static function getZoneStats(int $zoneId): array
    {
        $key = sprintf(self::KEY_ZONE, $zoneId);

        return Cache::remember($key, self::TTL, function () use ($zoneId) {
            $tz = config('app.timezone', 'Africa/Nairobi');

            return [
                'active_leases'     => Lease::where('zone_id', $zoneId)->where('workflow_state', 'active')->count(),
                'pending_approval'  => Lease::where('zone_id', $zoneId)->where('workflow_state', 'pending_landlord_approval')->count(),
                'expiring_30d'      => Lease::where('zone_id', $zoneId)
                    ->where('workflow_state', 'active')
                    ->whereBetween('end_date', [
                        Carbon::now($tz)->startOfDay(),
                        Carbon::now($tz)->addDays(30)->endOfDay(),
                    ])
                    ->count(),
                'generated_at'      => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Invalidate cached stats.
     *
     * Call this from LeaseObserver::updated() whenever workflow_state changes.
     *
     * @param int|null $zoneId If provided, also clears zone-specific cache.
     */
    public static function invalidate(?int $zoneId = null): void
    {
        Cache::forget(self::KEY_ADMIN);

        if ($zoneId !== null) {
            Cache::forget(sprintf(self::KEY_ZONE, $zoneId));
        }
    }
}
