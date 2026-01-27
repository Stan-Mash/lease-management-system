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
        Schema::create('template_variable_definitions', function (Blueprint $table) {
            $table->id();

            $table->string('variable_name', 100)->unique(); // 'lease.monthly_rent'
            $table->string('display_name', 100); // 'Monthly Rent Amount'
            $table->string('category', 50); // 'financial', 'tenant_info', 'property_info', 'dates', 'legal'
            $table->text('description')->nullable();

            // Type & Formatting
            $table->string('data_type', 30); // 'money', 'date', 'text', 'number', 'address'
            $table->json('format_options')->nullable(); // {decimal_places: 2, currency: 'KES', date_format: 'd/m/Y'}

            // Validation
            $table->boolean('is_required')->default(false);
            $table->string('eloquent_path')->nullable(); // 'lease.monthly_rent' or 'tenant.full_name'
            $table->string('helper_method')->nullable(); // 'formatMoney', 'formatDate'

            // Sample data for preview
            $table->string('sample_value')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_variable_definitions');
    }
};
