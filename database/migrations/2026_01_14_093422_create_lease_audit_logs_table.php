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
        Schema::create('lease_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_id')->constrained()->onDelete('cascade');
            $table->string('action', 100); // Action performed (e.g., 'state_transition', 'field_updated', 'document_uploaded')
            $table->string('old_state', 40)->nullable(); // Previous workflow state
            $table->string('new_state', 40)->nullable(); // New workflow state
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Who performed the action
            $table->string('user_role_at_time', 50)->nullable(); // User's role when action was performed
            $table->string('ip_address', 45)->nullable(); // IPv4 or IPv6
            $table->json('additional_data')->nullable(); // Any extra context (field changes, reasons, etc.)
            $table->text('description')->nullable(); // Human-readable description of the action
            $table->timestamps(); // created_at is when the action occurred

            // Indexes for common queries
            $table->index('lease_id');
            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
            $table->index(['lease_id', 'created_at']); // Compound index for lease timeline
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lease_audit_logs');
    }
};
