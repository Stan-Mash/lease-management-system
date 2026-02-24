<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance indexes for dashboard widgets and lease aggregation queries.
 *
 * Targets the exact query shapes used by:
 *   - DashboardStatsService  (workflow_state, end_date, zone_id)
 *   - LeaseStatsWidget        (workflow_state, zone_id, monthly_rent, end_date)
 *   - RevenueChartWidget      (workflow_state, created_at, monthly_rent)
 *   - ZonePerformanceWidget   (workflow_state, zone_id, monthly_rent)
 *   - FieldOfficerPerformanceWidget (role, zone_id on users; assigned_field_officer_id on leases)
 *   - Units occupancy query   (status_legacy, property_id)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── leases ────────────────────────────────────────────────────────────

        // Already exists from 2026_02_23_000001: (tenant_id, workflow_state)
        // Already exists from 2026_02_23_000001: (workflow_state, end_date)

        // Dashboard revenue aggregation: WHERE workflow_state='active' → SUM(monthly_rent)
        // Also used for date-filtered GROUP BY revenue chart
        if (! $this->indexExists('leases', 'leases_workflow_state_created_at_monthly_rent_idx')) {
            Schema::table('leases', function (Blueprint $table) {
                $table->index(['workflow_state', 'created_at', 'monthly_rent'], 'leases_workflow_state_created_at_monthly_rent_idx');
            });
        }

        // Zone-scoped dashboard: WHERE zone_id=? AND workflow_state=?
        if (! $this->indexExists('leases', 'leases_zone_id_workflow_state_idx')) {
            Schema::table('leases', function (Blueprint $table) {
                $table->index(['zone_id', 'workflow_state'], 'leases_zone_id_workflow_state_idx');
            });
        }

        // Field officer scoped queries: WHERE assigned_field_officer_id=? AND workflow_state=?
        if (! $this->indexExists('leases', 'leases_fo_workflow_idx')) {
            Schema::table('leases', function (Blueprint $table) {
                $table->index(['assigned_field_officer_id', 'workflow_state'], 'leases_fo_workflow_idx');
            });
        }

        // ── units ─────────────────────────────────────────────────────────────

        // Occupancy rate subquery: WHERE status_legacy='OCCUPIED', GROUP BY property_id → zone_id JOIN
        if (! $this->indexExists('units', 'units_property_id_status_legacy_idx')) {
            Schema::table('units', function (Blueprint $table) {
                $table->index(['property_id', 'status_legacy'], 'units_property_id_status_legacy_idx');
            });
        }

        // ── users ─────────────────────────────────────────────────────────────

        // FieldOfficerPerformanceWidget: WHERE role='field_officer' AND zone_id=?
        if (! $this->indexExists('users', 'users_role_zone_id_idx')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index(['role', 'zone_id'], 'users_role_zone_id_idx');
            });
        }

        // ── properties ────────────────────────────────────────────────────────

        // Occupancy subquery JOIN: properties.zone_id used in zone → property → unit chain
        if (! $this->indexExists('properties', 'properties_zone_id_idx')) {
            Schema::table('properties', function (Blueprint $table) {
                $table->index('zone_id', 'properties_zone_id_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->dropIndexIfExists('leases_workflow_state_created_at_monthly_rent_idx');
            $table->dropIndexIfExists('leases_zone_id_workflow_state_idx');
            $table->dropIndexIfExists('leases_fo_workflow_idx');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropIndexIfExists('units_property_id_status_legacy_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndexIfExists('users_role_zone_id_idx');
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndexIfExists('properties_zone_id_idx');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return collect(
            \Illuminate\Support\Facades\DB::select(
                "SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?",
                [$table, $indexName]
            )
        )->isNotEmpty();
    }
};
