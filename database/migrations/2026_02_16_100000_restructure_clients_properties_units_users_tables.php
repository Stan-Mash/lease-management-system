<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The shared column set for both clients and tenants tables.
     */
    private function addClientTenantColumns(Blueprint $table): void
    {
        $table->id();
        $table->timestamp('date_time')->nullable();
        $table->string('names')->nullable();
        $table->string('address')->nullable();
        $table->string('vat_number')->nullable();
        $table->string('pin_number')->nullable();
        $table->string('mobile_number')->nullable();
        $table->string('email_address')->nullable();
        $table->unsignedBigInteger('bank_id')->nullable();
        $table->string('account_name')->nullable();
        $table->string('account_number')->nullable();
        $table->string('username')->nullable();
        $table->string('client_password')->nullable();
        $table->string('uid')->nullable();
        $table->unsignedBigInteger('group_id')->nullable();
        $table->date('registered_date')->nullable();
        $table->string('reference_number')->nullable();
        $table->unsignedBigInteger('created_by')->nullable();
        $table->unsignedBigInteger('nationality_id')->nullable();
        $table->string('national_id')->nullable();
        $table->string('passport_number')->nullable();
        $table->unsignedBigInteger('client_type_id')->nullable();
        $table->string('photo')->nullable();
        $table->text('documents')->nullable();
        $table->unsignedBigInteger('current_status_id')->nullable();
        $table->unsignedBigInteger('client_status_id')->nullable();
        $table->unsignedBigInteger('lead_id')->nullable();
        $table->unsignedBigInteger('type_id')->nullable();
        $table->unsignedBigInteger('client_id')->nullable();
        $table->string('second_name')->nullable();
        $table->string('last_name')->nullable();
        $table->string('title')->nullable();
        $table->string('gender')->nullable();
        $table->unsignedBigInteger('prefered_messages_language_id')->nullable();
        $table->unsignedBigInteger('property_id')->nullable();
        $table->unsignedBigInteger('sla_id')->nullable();
        $table->unsignedBigInteger('unit_id')->nullable();
        $table->date('lease_start_date')->nullable();
        $table->integer('lease_years')->nullable();
        $table->decimal('rent_amount', 15, 2)->nullable();
        $table->decimal('escalation_rate', 8, 4)->nullable();
        $table->string('frequency')->nullable();
        $table->string('address_2')->nullable();
        $table->string('address_3')->nullable();
        $table->string('promas_id')->nullable();
        $table->text('properties')->nullable();
        $table->decimal('overdraft_penalty', 15, 2)->nullable();
        $table->timestamps();
    }

    public function up(): void
    {
        // =====================================================================
        // 1. CLIENTS TABLE (replaces landlords)
        // =====================================================================
        Schema::dropIfExists('landlords');

        Schema::create('clients', function (Blueprint $table) {
            $this->addClientTenantColumns($table);
        });

        // =====================================================================
        // 2. TENANTS TABLE (restructure with same columns)
        // =====================================================================
        Schema::dropIfExists('tenant_events');
        Schema::dropIfExists('tenants');

        Schema::create('tenants', function (Blueprint $table) {
            $this->addClientTenantColumns($table);
        });

        // =====================================================================
        // 3. PROPERTIES TABLE (restructure)
        // =====================================================================
        Schema::dropIfExists('properties');

        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->timestamp('date_time')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('description')->nullable();
            $table->string('lat_long')->nullable();
            $table->text('photos_and_documents')->nullable();
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->unsignedBigInteger('zone_area_id')->nullable();
            $table->string('property_name')->nullable();
            $table->string('lr_number')->nullable();
            $table->unsignedBigInteger('usage_type_id')->nullable();
            $table->unsignedBigInteger('current_status_id')->nullable();
            $table->date('acquisition_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->unsignedBigInteger('field_officer_id')->nullable();
            $table->unsignedBigInteger('zone_supervisor_id')->nullable();
            $table->unsignedBigInteger('zone_manager_id')->nullable();
            $table->unsignedBigInteger('parent_property_id')->nullable();
            $table->timestamps();
        });

        // =====================================================================
        // 4. UNITS TABLE (restructure)
        // =====================================================================
        Schema::dropIfExists('units');

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->timestamp('date_time')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('property_id')->nullable();
            $table->unsignedBigInteger('unit_type_id')->nullable();
            $table->unsignedBigInteger('usage_type_id')->nullable();
            $table->string('unit_code')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->text('unit_uploads')->nullable();
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->unsignedBigInteger('occupancy_status_id')->nullable();
            $table->string('unit_name')->nullable();
            $table->unsignedBigInteger('unit_condition_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->decimal('rent_amount', 15, 2)->nullable();
            $table->boolean('vat_able')->default(false);
            $table->unsignedBigInteger('current_status_id')->nullable();
            $table->string('unit_number')->nullable();
            $table->decimal('initial_water_meter_reading', 15, 2)->nullable();
            $table->unsignedBigInteger('topology_id')->nullable();
            $table->unsignedBigInteger('block_owner_tenant_id')->nullable();
            $table->timestamps();
        });

        // =====================================================================
        // 5. USERS TABLE (restructure)
        // =====================================================================
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('block')->default(false);
            $table->boolean('sendEmail')->default(false);
            $table->timestamp('registerDate')->nullable();
            $table->timestamp('lastvisitDate')->nullable();
            $table->string('activation')->nullable();
            $table->text('params')->nullable();
            $table->timestamp('lastResetTime')->nullable();
            $table->integer('resetCount')->default(0);
            $table->string('otpKey')->nullable();
            $table->string('reset_token')->nullable();
            $table->timestamp('reset_token_expires_at')->nullable();
            $table->text('otep')->nullable();
            $table->boolean('requireReset')->default(false);
            $table->timestamps();
        });

        // Recreate sessions and password reset tokens
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        // Reverse: drop new tables, recreate old ones
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('units');
        Schema::dropIfExists('properties');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('clients');

        // Recreate original landlords
        Schema::create('landlords', function (Blueprint $table) {
            $table->id();
            $table->string('landlord_code')->nullable()->index();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('id_number')->nullable();
            $table->string('kra_pin')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Recreate original tenants
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('id_number')->nullable()->unique();
            $table->string('phone_number')->unique();
            $table->string('email')->nullable();
            $table->enum('notification_preference', ['EMAIL', 'SMS', 'BOTH'])->default('SMS');
            $table->string('preferred_language', 5)->default('en');
            $table->string('kra_pin')->nullable();
            $table->string('occupation')->nullable();
            $table->string('employer_name')->nullable();
            $table->string('next_of_kin_name')->nullable();
            $table->string('next_of_kin_phone')->nullable();
            $table->timestamps();
        });

        // Recreate original properties
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('property_code')->unique();
            $table->char('zone', 1)->default('A');
            $table->string('location')->nullable();
            $table->foreignId('landlord_id')->constrained()->onDelete('restrict');
            $table->decimal('management_commission', 5, 2)->default(10.00);
            $table->timestamps();
        });

        // Recreate original units
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('unit_number');
            $table->string('type')->nullable();
            $table->decimal('market_rent', 12, 2)->default(0);
            $table->decimal('deposit_required', 12, 2)->default(0);
            $table->enum('status', ['VACANT', 'OCCUPIED', 'MAINTENANCE'])->default('VACANT');
            $table->unique(['property_id', 'unit_number']);
            $table->timestamps();
        });

        // Recreate original users
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }
};
