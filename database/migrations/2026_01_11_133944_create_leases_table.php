<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leases', function (Blueprint $table) {
            $table->id();

            // 1. Identification
            // Unique reference like LSE-COM-A-00142-2026
            $table->string('reference_number', 30)->unique();

            // 2. Configuration
            $table->string('source', 20); // 'chabrin' or 'landlord'
            $table->string('lease_type', 30); // 'commercial', 'residential_micro', etc.

            // The Toggle: Digital (Email link) or Physical (Field Officer delivery)
            $table->string('signing_mode', 20)->nullable();

            // 3. Workflow Engine
            // Default is DRAFT. Indexed for fast dashboard loading.
            $table->string('workflow_state', 40)->default('DRAFT')->index();

            // 4. Relationships (The Glue)
            // If a Tenant is deleted, don't delete the lease (keep history)
            $table->foreignId('tenant_id')->constrained()->onDelete('restrict');
            $table->foreignId('unit_id')->constrained()->onDelete('restrict');
            $table->foreignId('property_id')->constrained()->onDelete('restrict');
            $table->foreignId('landlord_id')->nullable()->constrained()->onDelete('restrict');

            // 5. Financials & Dates
            $table->char('zone', 1); // A-G
            $table->decimal('monthly_rent', 12, 2);
            $table->decimal('deposit_amount', 12, 2);
            $table->date('start_date');
            $table->date('end_date')->nullable(); // Nullable for periodic leases

            // 6. Security & Logic
            $table->boolean('requires_lawyer')->default(false);
            $table->integer('document_version')->default(1);

            // 7. Remote Visit / Anti-Fraud Tracking
            // We capture WHERE the signing happened
            $table->decimal('signature_latitude', 10, 8)->nullable();
            $table->decimal('signature_longitude', 11, 8)->nullable();
            $table->string('signing_location_type')->nullable(); // 'on_site' or 'off_site'

            // 8. Files
            $table->string('generated_pdf_path', 500)->nullable();
            $table->string('signed_pdf_path', 500)->nullable();

            // 9. Audit
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leases');
    }
};
