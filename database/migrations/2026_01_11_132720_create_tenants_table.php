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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('full_name'); // Matches "Tenant Name"
            $table->string('id_number')->nullable()->unique(); // Matches "ID Number"

            // Contact & Preferences
            $table->string('phone_number')->unique(); // Matches "Tel/Mobile"
            $table->string('email')->nullable();
            $table->enum('notification_preference', ['EMAIL', 'SMS', 'BOTH'])->default('SMS');

            // Financial & Employment (From your "Tenant Details" file)
            $table->string('kra_pin')->nullable();
            $table->string('occupation')->nullable();
            $table->string('employer_name')->nullable();

            // Emergency Contact
            $table->string('next_of_kin_name')->nullable();
            $table->string('next_of_kin_phone')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
