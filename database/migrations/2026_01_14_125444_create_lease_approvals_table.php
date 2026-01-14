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
        Schema::create('lease_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_id')->constrained('leases')->onDelete('cascade');
            $table->foreignId('landlord_id')->constrained('landlords')->onDelete('cascade');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');

            $table->enum('decision', ['approved', 'rejected'])->nullable();
            $table->text('comments')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('notified_at')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->json('previous_data')->nullable(); // Store lease state before approval
            $table->json('metadata')->nullable(); // Additional tracking data

            $table->timestamps();

            // Indexes
            $table->index('lease_id');
            $table->index('landlord_id');
            $table->index('decision');
            $table->index('reviewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lease_approvals');
    }
};
