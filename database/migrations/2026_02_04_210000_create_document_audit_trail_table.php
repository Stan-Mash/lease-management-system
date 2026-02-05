<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_audit_trail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_document_id')->constrained('lease_documents')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // upload, view, download, edit, approve, reject, link, verify, replace
            $table->string('action_category'); // access, modification, workflow, integrity
            $table->text('description');
            $table->json('old_values')->nullable(); // Previous state for edits
            $table->json('new_values')->nullable(); // New state for edits
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('file_hash')->nullable(); // Hash at time of action (for integrity tracking)
            $table->boolean('integrity_verified')->nullable(); // Result of verification if applicable
            $table->timestamps();

            // Indexes for common queries
            $table->index(['lease_document_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('action');
            $table->index('action_category');
        });

        // Add version tracking to lease_documents
        Schema::table('lease_documents', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('file_hash');
            $table->foreignId('parent_document_id')->nullable()->after('version')
                ->constrained('lease_documents')->nullOnDelete();
            $table->timestamp('last_integrity_check')->nullable()->after('parent_document_id');
            $table->boolean('integrity_status')->nullable()->after('last_integrity_check');
        });
    }

    public function down(): void
    {
        Schema::table('lease_documents', function (Blueprint $table) {
            $table->dropForeign(['parent_document_id']);
            $table->dropColumn(['version', 'parent_document_id', 'last_integrity_check', 'integrity_status']);
        });

        Schema::dropIfExists('document_audit_trail');
    }
};
