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
        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_id')->constrained()->onDelete('cascade');
            $table->string('phone', 20); // Phone number OTP was sent to
            $table->string('code', 6); // 4-6 digit OTP code
            $table->string('purpose', 50)->default('digital_signing'); // Purpose: digital_signing, other
            $table->timestamp('sent_at'); // When OTP was sent
            $table->timestamp('expires_at'); // When OTP expires (10 minutes from sent_at)
            $table->timestamp('verified_at')->nullable(); // When OTP was successfully verified
            $table->integer('attempts')->default(0); // Number of verification attempts
            $table->boolean('is_verified')->default(false); // Whether OTP was successfully verified
            $table->boolean('is_expired')->default(false); // Whether OTP has expired
            $table->string('ip_address', 45)->nullable(); // IP address of verification attempt
            $table->text('metadata')->nullable(); // Additional data (JSON)
            $table->timestamps();

            // Indexes for common queries
            $table->index('lease_id');
            $table->index('phone');
            $table->index('code');
            $table->index(['lease_id', 'is_verified']); // Check if lease has verified OTP
            $table->index('expires_at'); // For cleanup of expired OTPs
            $table->index('sent_at'); // For rate limiting
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_verifications');
    }
};
