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
        Schema::create('lease_template_versions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lease_template_id')->constrained()->cascadeOnDelete();
            $table->integer('version_number');

            // Snapshot of entire template state
            $table->longText('blade_content');
            $table->json('css_styles')->nullable();
            $table->json('layout_config')->nullable();
            $table->json('branding_config')->nullable();
            $table->json('available_variables')->nullable();

            // Change tracking
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('change_summary')->nullable(); // "Updated tenant signature section"
            $table->json('changes_diff')->nullable(); // Detailed diff data

            $table->timestamps();

            $table->unique(['lease_template_id', 'version_number']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lease_template_versions');
    }
};
