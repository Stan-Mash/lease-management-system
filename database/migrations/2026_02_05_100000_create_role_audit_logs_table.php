<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Who was affected
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // What action was taken
            $table->string('action', 50); // assigned, revoked, modified, permission_changed

            // Role information
            $table->string('old_role')->nullable();
            $table->string('new_role')->nullable();

            // Permission changes (for detailed permission audits)
            $table->json('old_permissions')->nullable();
            $table->json('new_permissions')->nullable();

            // Who made the change
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');

            // Context
            $table->string('reason')->nullable();
            $table->json('metadata')->nullable();

            // Security context
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamps();

            // Indexes for common queries
            $table->index('user_id');
            $table->index('performed_by');
            $table->index('action');
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_audit_logs');
    }
};
