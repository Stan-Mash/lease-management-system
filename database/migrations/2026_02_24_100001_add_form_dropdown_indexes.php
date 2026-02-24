<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add indexes that make the lease-create form dropdowns fast.
 *
 * Before these indexes:
 *   SELECT id, names, mobile_number FROM tenants WHERE deleted_at IS NULL ORDER BY names
 *   → Full sequential scan + filesort = ~300ms on production
 *
 * After:
 *   → Index scan on idx_tenants_active_names = <5ms
 *
 * Same pattern for units.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tenants: covers the exact query used by LeaseForm dropdown
        // WHERE deleted_at IS NULL ORDER BY names
        if (! $this->indexExists('tenants', 'idx_tenants_active_names')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->index(['deleted_at', 'names'], 'idx_tenants_active_names');
            });
        }

        // Tenants: mobile_number search in dropdown (getSearchResultsUsing fallback)
        if (! $this->indexExists('tenants', 'idx_tenants_mobile_number')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->index('mobile_number', 'idx_tenants_mobile_number');
            });
        }

        // Units: covers WHERE deleted_at IS NULL ORDER BY unit_number
        if (! $this->indexExists('units', 'idx_units_active_unit_number')) {
            Schema::table('units', function (Blueprint $table) {
                $table->index(['deleted_at', 'unit_number'], 'idx_units_active_unit_number');
            });
        }

        // Units: unit_code search
        if (! $this->indexExists('units', 'idx_units_unit_code')) {
            Schema::table('units', function (Blueprint $table) {
                $table->index('unit_code', 'idx_units_unit_code');
            });
        }
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_tenants_active_names');
            $table->dropIndexIfExists('idx_tenants_mobile_number');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_units_active_unit_number');
            $table->dropIndexIfExists('idx_units_unit_code');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return collect(\DB::select("
            SELECT indexname FROM pg_indexes
            WHERE tablename = ? AND indexname = ?
        ", [$table, $indexName]))->isNotEmpty();
    }
};
