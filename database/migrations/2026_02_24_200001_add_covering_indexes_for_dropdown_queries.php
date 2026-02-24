<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Replace the basic composite indexes from 2026_02_24_100001 with covering
 * partial indexes that allow PostgreSQL to perform an index-only scan.
 *
 * WHY THE PREVIOUS INDEXES DIDN'T HELP
 * ─────────────────────────────────────
 * The query is:
 *   SELECT id, names, mobile_number FROM tenants
 *   WHERE deleted_at IS NULL ORDER BY names
 *
 * The previous index ON tenants(deleted_at, names) made PostgreSQL aware of the
 * order, but because id and mobile_number are NOT in the index, the engine must
 * visit the heap (actual table row) for every single one of the 24,733 rows
 * after the index scan.  For a query that returns every active tenant, the
 * planner correctly judges that a sequential scan is cheaper than 24,733
 * random heap fetches → it ignores the index entirely.
 *
 * THE FIX: PARTIAL COVERING INDEX WITH INCLUDE
 * ─────────────────────────────────────────────
 * A partial covering index:
 *   • WHERE deleted_at IS NULL  → only indexes active rows (≈ half the storage)
 *   • ORDER BY names            → key column, so the sort is free
 *   • INCLUDE (id, mobile_number) → payload columns stored in the index leaf
 *     pages, so the heap never needs to be touched → index-only scan
 *
 * Result: PostgreSQL reads a single B-tree from start to finish and returns
 * all rows in sorted order with no heap access and no sort step.
 * Expected execution time: < 5 ms  (down from 98 ms).
 *
 * Blueprint doesn't support INCLUDE, so we use raw DDL.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Tenants ────────────────────────────────────────────────────────────

        // Drop the basic index from the previous migration if it exists
        if ($this->indexExists('tenants', 'idx_tenants_active_names')) {
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_tenants_active_names');
        }

        // Covering partial index — supports index-only scan for the dropdown query
        // SELECT id, names, mobile_number FROM tenants WHERE deleted_at IS NULL ORDER BY names
        if (! $this->indexExists('tenants', 'idx_tenants_dropdown')) {
            DB::statement('
                CREATE INDEX CONCURRENTLY idx_tenants_dropdown
                ON tenants (names)
                INCLUDE (id, mobile_number)
                WHERE deleted_at IS NULL
            ');
        }

        // ── Units ──────────────────────────────────────────────────────────────

        // Drop the basic index from the previous migration if it exists
        if ($this->indexExists('units', 'idx_units_active_unit_number')) {
            DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_units_active_unit_number');
        }

        // Covering partial index for the unit dropdown query
        // SELECT id, unit_number, unit_code FROM units WHERE deleted_at IS NULL ORDER BY unit_number
        if (! $this->indexExists('units', 'idx_units_dropdown')) {
            DB::statement('
                CREATE INDEX CONCURRENTLY idx_units_dropdown
                ON units (unit_number)
                INCLUDE (id, unit_code)
                WHERE deleted_at IS NULL
            ');
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_tenants_dropdown');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_units_dropdown');

        // Restore the basic indexes from the previous migration
        if (! $this->indexExists('tenants', 'idx_tenants_active_names')) {
            DB::statement('CREATE INDEX idx_tenants_active_names ON tenants (deleted_at, names)');
        }

        if (! $this->indexExists('units', 'idx_units_active_unit_number')) {
            DB::statement('CREATE INDEX idx_units_active_unit_number ON units (deleted_at, unit_number)');
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return collect(DB::select('
            SELECT indexname FROM pg_indexes
            WHERE tablename = ? AND indexname = ?
        ', [$table, $indexName]))->isNotEmpty();
    }
};
