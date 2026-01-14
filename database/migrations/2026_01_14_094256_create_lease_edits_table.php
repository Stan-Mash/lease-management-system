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
        Schema::create('lease_edits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_id')->constrained()->onDelete('cascade');
            $table->foreignId('edited_by')->constrained('users')->onDelete('cascade'); // PM or APM who made edit
            $table->string('edit_type', 30); // clause_added, clause_removed, clause_modified, other
            $table->string('section_affected', 200)->nullable(); // Which clause (e.g., 'Clause 7.3 - Subletting')
            $table->text('original_text')->nullable(); // Before (NULL if new clause)
            $table->text('new_text')->nullable(); // After (NULL if removed)
            $table->text('reason')->nullable(); // Why the change was made
            $table->integer('document_version'); // Version number (increments with each edit batch)
            $table->timestamps(); // edited_at is created_at

            // Indexes for common queries
            $table->index('lease_id');
            $table->index('edited_by');
            $table->index('document_version');
            $table->index(['lease_id', 'document_version']); // Compound index for version history
            $table->index('created_at'); // For timeline queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lease_edits');
    }
};
