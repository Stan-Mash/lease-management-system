<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Align Chabrin Lease System tables with CHIPS system schema.
     *
     * Strategy:
     * - Rename existing columns to match CHIPS naming conventions
     * - Add new CHIPS columns as nullable (populated during integration)
     * - Keep Chabrin-specific columns that serve the lease system
     */
    public function up(): void
    {
        // =====================================================================
        // TENANTS TABLE
        // =====================================================================
        Schema::table('tenants', function (Blueprint $table) {
            // Renames: Chabrin → CHIPS
            $table->renameColumn('full_name', 'names');
            $table->renameColumn('id_number', 'national_id');
            $table->renameColumn('phone_number', 'mobile_number');
            $table->renameColumn('email', 'email_address');
            $table->renameColumn('kra_pin', 'pin_number');
            $table->renameColumn('date_created', 'date_time');
        });

        Schema::table('tenants', function (Blueprint $table) {
            // New CHIPS columns (all nullable)
            $table->string('address', 255)->nullable()->after('names');
            $table->string('vat_number', 100)->nullable();
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->string('account_name', 255)->nullable();
            $table->string('account_number', 255)->nullable();
            $table->string('username', 255)->nullable();
            $table->string('client_password', 255)->nullable();
            $table->string('uid', 255)->nullable();
            $table->unsignedBigInteger('group_id')->nullable();
            $table->timestamp('registered_date')->nullable();
            $table->string('reference_number', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('nationality_id')->nullable();
            $table->string('passport_number', 255)->nullable();
            $table->unsignedBigInteger('client_type_id')->nullable();
            $table->string('photo', 255)->nullable();
            $table->text('documents')->nullable();
            $table->unsignedBigInteger('current_status_id')->nullable();
            $table->unsignedBigInteger('client_status_id')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('type_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('second_name', 255)->nullable();
            $table->string('last_name', 255)->nullable();
            $table->string('title', 50)->nullable();
            $table->string('gender', 20)->nullable();
            $table->unsignedBigInteger('prefered_messages_language_id')->nullable();
            $table->unsignedBigInteger('property_id')->nullable();
            $table->unsignedBigInteger('sla_id')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->date('lease_start_date')->nullable();
            $table->integer('lease_years')->nullable();
            $table->decimal('rent_amount', 12, 2)->nullable();
            $table->decimal('escalation_rate', 5, 2)->nullable();
            $table->string('frequency', 50)->nullable();
            $table->string('address_2', 255)->nullable();
            $table->string('address_3', 255)->nullable();
            $table->string('promas_id', 255)->nullable();
            $table->text('properties')->nullable();
            $table->decimal('overdraft_penalty', 12, 2)->nullable();
        });

        // =====================================================================
        // PROPERTIES TABLE
        // =====================================================================
        Schema::table('properties', function (Blueprint $table) {
            // Renames: Chabrin → CHIPS
            $table->renameColumn('name', 'property_name');
            $table->renameColumn('property_code', 'reference_number');
            $table->renameColumn('location', 'description');
            $table->renameColumn('date_created', 'date_time');
        });

        Schema::table('properties', function (Blueprint $table) {
            // New CHIPS columns (all nullable)
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('lat_long', 255)->nullable();
            $table->text('photos_and_documents')->nullable();
            $table->unsignedBigInteger('zone_area_id')->nullable();
            $table->string('lr_number', 255)->nullable();
            $table->unsignedBigInteger('usage_type_id')->nullable();
            $table->unsignedBigInteger('current_status_id')->nullable();
            $table->date('acquisition_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->unsignedBigInteger('zone_supervisor_id')->nullable();
            $table->unsignedBigInteger('parent_property_id')->nullable();
        });

        // =====================================================================
        // UNITS TABLE
        // =====================================================================
        // Rename status to status_legacy first, then add CHIPS columns
        Schema::table('units', function (Blueprint $table) {
            $table->renameColumn('market_rent', 'rent_amount');
            $table->renameColumn('status', 'status_legacy');
            $table->renameColumn('date_created', 'date_time');
        });

        Schema::table('units', function (Blueprint $table) {
            // New CHIPS columns (all nullable)
            $table->unsignedBigInteger('unit_type_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('usage_type_id')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->text('unit_uploads')->nullable();
            $table->unsignedBigInteger('occupancy_status_id')->nullable();
            $table->string('unit_name', 255)->nullable();
            $table->unsignedBigInteger('unit_condition_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->boolean('vat_able')->default(false);
            $table->unsignedBigInteger('current_status_id')->nullable();
            $table->decimal('initial_water_meter_reading', 12, 2)->nullable();
            $table->unsignedBigInteger('topology_id')->nullable();
            $table->unsignedBigInteger('block_owner_tenant_id')->nullable();
        });

        // =====================================================================
        // USERS TABLE
        // =====================================================================
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('is_active', 'block');
            $table->renameColumn('last_login_at', 'lastvisitDate');
            $table->renameColumn('date_created', 'registerDate');
        });

        // Invert the block values: is_active=true (1) → block=0 (active), is_active=false (0) → block=1 (blocked)
        DB::statement('UPDATE users SET "block" = NOT "block"');

        Schema::table('users', function (Blueprint $table) {
            // New CHIPS columns (all nullable)
            $table->string('username', 255)->nullable();
            $table->boolean('sendEmail')->default(true);
            $table->string('activation', 255)->nullable();
            $table->text('params')->nullable();
            $table->timestamp('lastResetTime')->nullable();
            $table->integer('resetCount')->default(0);
            $table->string('otpKey', 255)->nullable();
            $table->string('reset_token', 255)->nullable();
            $table->timestamp('reset_token_expires_at')->nullable();
            $table->string('otep', 255)->nullable();
            $table->boolean('requireReset')->default(false);
        });
    }

    /**
     * Reverse the changes.
     */
    public function down(): void
    {
        // =====================================================================
        // USERS TABLE - Reverse
        // =====================================================================
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username', 'sendEmail', 'activation', 'params',
                'lastResetTime', 'resetCount', 'otpKey', 'reset_token',
                'reset_token_expires_at', 'otep', 'requireReset',
            ]);
        });

        // Re-invert block values back to is_active
        DB::statement('UPDATE users SET "block" = NOT "block"');

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('block', 'is_active');
            $table->renameColumn('lastvisitDate', 'last_login_at');
            $table->renameColumn('registerDate', 'date_created');
        });

        // =====================================================================
        // UNITS TABLE - Reverse
        // =====================================================================
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn([
                'unit_type_id', 'client_id', 'usage_type_id', 'description',
                'created_by_id', 'unit_uploads', 'occupancy_status_id', 'unit_name',
                'unit_condition_id', 'category_id', 'vat_able', 'current_status_id',
                'initial_water_meter_reading', 'topology_id', 'block_owner_tenant_id',
            ]);
        });

        Schema::table('units', function (Blueprint $table) {
            $table->renameColumn('rent_amount', 'market_rent');
            $table->renameColumn('status_legacy', 'status');
            $table->renameColumn('date_time', 'date_created');
        });

        // =====================================================================
        // PROPERTIES TABLE - Reverse
        // =====================================================================
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn([
                'client_id', 'lat_long', 'photos_and_documents', 'zone_area_id',
                'lr_number', 'usage_type_id', 'current_status_id', 'acquisition_date',
                'created_by', 'bank_account_id', 'zone_supervisor_id', 'parent_property_id',
            ]);
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->renameColumn('property_name', 'name');
            $table->renameColumn('reference_number', 'property_code');
            $table->renameColumn('description', 'location');
            $table->renameColumn('date_time', 'date_created');
        });

        // =====================================================================
        // TENANTS TABLE - Reverse
        // =====================================================================
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'address', 'vat_number', 'bank_id', 'account_name', 'account_number',
                'username', 'client_password', 'uid', 'group_id', 'registered_date',
                'reference_number', 'created_by', 'nationality_id', 'passport_number',
                'client_type_id', 'photo', 'documents', 'current_status_id', 'client_status_id',
                'lead_id', 'type_id', 'client_id', 'second_name', 'last_name', 'title',
                'gender', 'prefered_messages_language_id', 'property_id', 'sla_id', 'unit_id',
                'lease_start_date', 'lease_years', 'rent_amount', 'escalation_rate', 'frequency',
                'address_2', 'address_3', 'promas_id', 'properties', 'overdraft_penalty',
            ]);
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->renameColumn('names', 'full_name');
            $table->renameColumn('national_id', 'id_number');
            $table->renameColumn('mobile_number', 'phone_number');
            $table->renameColumn('email_address', 'email');
            $table->renameColumn('pin_number', 'kra_pin');
            $table->renameColumn('date_time', 'date_created');
        });
    }
};
