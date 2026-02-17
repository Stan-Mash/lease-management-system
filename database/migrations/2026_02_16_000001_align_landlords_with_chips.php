<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Align landlords table with CHIPS CLIENTS schema.
     *
     * The CHIPS "CLIENTS/TENANTS 1 TABLE" applies to both tenants AND landlords.
     * The tenants table was aligned in the previous migration; this aligns landlords.
     *
     * Strategy:
     * - Rename existing columns to match CHIPS naming conventions
     * - Add new CHIPS columns as nullable (populated during integration)
     * - Keep Chabrin-specific columns (lan_id, bank_name, zone_id, is_active)
     */
    public function up(): void
    {
        // Rename columns to match CHIPS naming
        Schema::table('landlords', function (Blueprint $table) {
            $table->renameColumn('name', 'names');
            $table->renameColumn('phone', 'mobile_number');
            $table->renameColumn('email', 'email_address');
            $table->renameColumn('id_number', 'national_id');
            $table->renameColumn('kra_pin', 'pin_number');
            $table->renameColumn('date_created', 'date_time');
        });

        // Add new CHIPS columns (all nullable)
        Schema::table('landlords', function (Blueprint $table) {
            $table->string('address', 255)->nullable()->after('names');
            $table->string('vat_number', 100)->nullable();
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->string('account_name', 255)->nullable();
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
            $table->text('properties_json')->nullable(); // named properties_json to avoid conflict with properties relationship
            $table->decimal('overdraft_penalty', 12, 2)->nullable();
        });
    }

    /**
     * Reverse the changes.
     */
    public function down(): void
    {
        Schema::table('landlords', function (Blueprint $table) {
            $table->dropColumn([
                'address', 'vat_number', 'bank_id', 'account_name',
                'username', 'client_password', 'uid', 'group_id', 'registered_date',
                'reference_number', 'created_by', 'nationality_id', 'passport_number',
                'client_type_id', 'photo', 'documents', 'current_status_id', 'client_status_id',
                'lead_id', 'type_id', 'client_id', 'second_name', 'last_name', 'title',
                'gender', 'prefered_messages_language_id', 'property_id', 'sla_id', 'unit_id',
                'lease_start_date', 'lease_years', 'rent_amount', 'escalation_rate', 'frequency',
                'address_2', 'address_3', 'promas_id', 'properties_json', 'overdraft_penalty',
            ]);
        });

        Schema::table('landlords', function (Blueprint $table) {
            $table->renameColumn('names', 'name');
            $table->renameColumn('mobile_number', 'phone');
            $table->renameColumn('email_address', 'email');
            $table->renameColumn('national_id', 'id_number');
            $table->renameColumn('pin_number', 'kra_pin');
            $table->renameColumn('date_time', 'date_created');
        });
    }
};
