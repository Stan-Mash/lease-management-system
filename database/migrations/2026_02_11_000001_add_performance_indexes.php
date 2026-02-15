<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Helper to check if index exists before adding
        $indexExists = function (string $table, string $indexName): bool {
            return DB::select("SELECT 1 FROM pg_indexes WHERE tablename = ? AND indexname = ?", [$table, $indexName]) !== [];
        };

        // Properties table
        Schema::table('properties', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('properties', 'properties_zone_idx')) {
                $table->index('zone', 'properties_zone_idx');
            }
            if (!$indexExists('properties', 'properties_fo_idx')) {
                $table->index('field_officer_id', 'properties_fo_idx');
            }
            if (!$indexExists('properties', 'properties_zm_idx')) {
                $table->index('zone_manager_id', 'properties_zm_idx');
            }
        });

        // Units table
        Schema::table('units', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('units', 'units_property_idx')) {
                $table->index('property_id', 'units_property_idx');
            }
            if (!$indexExists('units', 'units_status_idx')) {
                $table->index('status', 'units_status_idx');
            }
            if (!$indexExists('units', 'units_fo_idx')) {
                $table->index('field_officer_id', 'units_fo_idx');
            }
        });

        // Tenants table
        Schema::table('tenants', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('tenants', 'tenants_fo_idx')) {
                $table->index('field_officer_id', 'tenants_fo_idx');
            }
            if (!$indexExists('tenants', 'tenants_zm_idx')) {
                $table->index('zone_manager_id', 'tenants_zm_idx');
            }
        });

        // Landlords table
        Schema::table('landlords', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('landlords', 'landlords_active_idx')) {
                $table->index('is_active', 'landlords_active_idx');
            }
        });

        // Users table
        Schema::table('users', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('users', 'users_role_idx')) {
                $table->index('role', 'users_role_idx');
            }
            if (!$indexExists('users', 'users_role_zone_idx')) {
                $table->index(['role', 'zone_id'], 'users_role_zone_idx');
            }
            if (!$indexExists('users', 'users_active_idx')) {
                $table->index('is_active', 'users_active_idx');
            }
        });

        // Leases table — additional indexes for common queries
        Schema::table('leases', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('leases', 'leases_end_date_idx')) {
                $table->index('end_date', 'leases_end_date_idx');
            }
            if (!$indexExists('leases', 'leases_state_end_idx')) {
                $table->index(['workflow_state', 'end_date'], 'leases_state_end_idx');
            }
            if (!$indexExists('leases', 'leases_property_idx')) {
                $table->index('property_id', 'leases_property_idx');
            }
            if (!$indexExists('leases', 'leases_fo_idx')) {
                $table->index('assigned_field_officer_id', 'leases_fo_idx');
            }
            if (!$indexExists('leases', 'leases_zm_idx')) {
                $table->index('zone_manager_id', 'leases_zm_idx');
            }
        });

        // Lease documents table
        Schema::table('lease_documents', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('lease_documents', 'lease_docs_zone_idx')) {
                $table->index('zone_id', 'lease_docs_zone_idx');
            }
            if (!$indexExists('lease_documents', 'lease_docs_status_idx')) {
                $table->index('status', 'lease_docs_status_idx');
            }
            if (!$indexExists('lease_documents', 'lease_docs_uploaded_by_idx')) {
                $table->index('uploaded_by', 'lease_docs_uploaded_by_idx');
            }
        });
    }

    public function down(): void
    {
        // Drop indexes in reverse
        Schema::table('lease_documents', function (Blueprint $table) {
            $table->dropIndex(['zone_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['uploaded_by']);
        });
        Schema::table('leases', function (Blueprint $table) {
            $table->dropIndex(['end_date']);
            $table->dropIndex(['workflow_state', 'end_date']);
            $table->dropIndex(['property_id']);
            $table->dropIndex(['assigned_field_officer_id']);
            $table->dropIndex(['zone_manager_id']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['role', 'zone_id']);
            $table->dropIndex(['is_active']);
        });
        Schema::table('landlords', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['field_officer_id']);
            $table->dropIndex(['zone_manager_id']);
        });
        Schema::table('units', function (Blueprint $table) {
            $table->dropIndex(['property_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['field_officer_id']);
        });
        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex(['zone']);
            $table->dropIndex(['field_officer_id']);
            $table->dropIndex(['zone_manager_id']);
        });
    }
};
