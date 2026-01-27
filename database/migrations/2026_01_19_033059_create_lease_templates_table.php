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
        Schema::create('lease_templates', function (Blueprint $table) {
            $table->id();

            // Identification
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();

            // Template Type
            $table->string('template_type', 30)->index(); // 'residential_major', 'residential_micro', 'commercial', 'custom'
            $table->enum('source_type', ['uploaded_pdf', 'custom_blade', 'system_default'])->default('custom_blade');

            // Content Storage
            $table->longText('blade_content'); // The actual Blade template code
            $table->json('css_styles')->nullable(); // Extracted/custom CSS
            $table->json('layout_config')->nullable(); // Layout metadata (margins, fonts, spacing)

            // Branding
            $table->string('logo_path')->nullable(); // Path to custom logo
            $table->json('branding_config')->nullable(); // {primary_color, secondary_color, header_text, footer_text}

            // Original PDF (if uploaded)
            $table->string('source_pdf_path')->nullable(); // Original uploaded PDF
            $table->json('extraction_metadata')->nullable(); // Metadata from PDF extraction

            // Template Variables
            $table->json('available_variables')->nullable(); // List of all {{$variable}} placeholders used
            $table->json('required_variables')->nullable(); // Variables that MUST be present

            // Status & Assignment
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false)->index(); // Default for this template_type
            $table->integer('version_number')->default(1);

            // Metadata
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable(); // When template was made available
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['template_type', 'is_active']);
            $table->index(['is_default', 'template_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lease_templates');
    }
};
