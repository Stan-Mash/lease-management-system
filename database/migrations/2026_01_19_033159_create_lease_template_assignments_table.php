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
        Schema::create('lease_template_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lease_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lease_template_id')->constrained()->restrictOnDelete();
            $table->integer('template_version_used'); // Which version was used

            // Track when assignment was made
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at');

            // Cache rendered output metadata
            $table->json('render_metadata')->nullable(); // Variables used, render time, etc.

            $table->timestamps();

            $table->index(['lease_id', 'lease_template_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lease_template_assignments');
    }
};
