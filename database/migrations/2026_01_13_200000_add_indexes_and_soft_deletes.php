<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add soft deletes to leases
        Schema::table('leases', function (Blueprint $table) {
            if (!Schema::hasColumn('leases', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Add soft deletes to tenants
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Add soft deletes to landlords
        Schema::table('landlords', function (Blueprint $table) {
            if (!Schema::hasColumn('landlords', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Add soft deletes to properties
        Schema::table('properties', function (Blueprint $table) {
            if (!Schema::hasColumn('properties', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Add soft deletes to units
        Schema::table('units', function (Blueprint $table) {
            if (!Schema::hasColumn('units', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Add indexes for performance
        Schema::table('leases', function (Blueprint $table) {
            $table->index(['workflow_state', 'created_at'], 'idx_leases_workflow_state_created');
            $table->index(['workflow_state', 'end_date'], 'idx_leases_workflow_state_end_date');
            $table->index('tenant_id', 'idx_leases_tenant_id');
            $table->index('unit_id', 'idx_leases_unit_id');
            $table->index('property_id', 'idx_leases_property_id');
            $table->index('landlord_id', 'idx_leases_landlord_id');
            $table->unique('serial_number', 'idx_leases_serial_number');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->unique('id_number', 'idx_tenants_id_number');
            $table->unique('email', 'idx_tenants_email');
            $table->index('phone_number', 'idx_tenants_phone');
        });

        Schema::table('landlords', function (Blueprint $table) {
            $table->unique('id_number', 'idx_landlords_id_number');
            $table->index('landlord_code', 'idx_landlords_code');
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->index('landlord_id', 'idx_properties_landlord_id');
            $table->index('property_code', 'idx_properties_code');
            $table->index('zone', 'idx_properties_zone');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->index('property_id', 'idx_units_property_id');
            $table->index('status', 'idx_units_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop soft deletes
        Schema::table('leases', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('landlords', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Drop custom indexes
        Schema::table('leases', function (Blueprint $table) {
            $table->dropIndex('idx_leases_workflow_state_created');
            $table->dropIndex('idx_leases_workflow_state_end_date');
            $table->dropIndex('idx_leases_tenant_id');
            $table->dropIndex('idx_leases_unit_id');
            $table->dropIndex('idx_leases_property_id');
            $table->dropIndex('idx_leases_landlord_id');
            $table->dropUnique('idx_leases_serial_number');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropUnique('idx_tenants_id_number');
            $table->dropUnique('idx_tenants_email');
            $table->dropIndex('idx_tenants_phone');
        });

        Schema::table('landlords', function (Blueprint $table) {
            $table->dropUnique('idx_landlords_id_number');
            $table->dropIndex('idx_landlords_code');
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex('idx_properties_landlord_id');
            $table->dropIndex('idx_properties_code');
            $table->dropIndex('idx_properties_zone');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropIndex('idx_units_property_id');
            $table->dropIndex('idx_units_status');
        });
    }
};
