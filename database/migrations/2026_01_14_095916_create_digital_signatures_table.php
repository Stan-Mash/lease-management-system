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
        Schema::create('digital_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade'); // Who signed
            $table->text('signature_data'); // Base64 encoded signature image
            $table->string('signature_type', 20)->default('canvas'); // canvas, uploaded, typed
            $table->string('ip_address', 45); // IP address when signed
            $table->string('user_agent', 500)->nullable(); // Browser info
            $table->timestamp('signed_at'); // When signature was captured
            $table->string('otp_verification_id')->nullable(); // Link to OTP that was verified
            $table->boolean('is_verified')->default(true); // Whether signature is verified
            $table->string('verification_hash', 64)->nullable(); // SHA-256 hash of signature data
            $table->decimal('signature_latitude', 10, 8)->nullable(); // GPS latitude
            $table->decimal('signature_longitude', 11, 8)->nullable(); // GPS longitude
            $table->text('metadata')->nullable(); // Additional data (JSON)
            $table->timestamps();

            // Indexes for queries
            $table->index('lease_id');
            $table->index('tenant_id');
            $table->index('signed_at');
            $table->index('is_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_signatures');
    }
};
