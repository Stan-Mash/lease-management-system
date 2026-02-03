<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lease_documents', function (Blueprint $table) {
            // Make lease_id nullable for uploads before linking
            $table->foreignId('lease_id')->nullable()->change();

            // Add zone and property for organization (before linking)
            $table->foreignId('zone_id')->nullable()->after('lease_id')->constrained()->nullOnDelete();
            $table->foreignId('property_id')->nullable()->after('zone_id')->constrained()->nullOnDelete();
            $table->foreignId('tenant_id')->nullable()->after('property_id')->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();

            // Workflow status
            $table->string('status')->default('pending_review')->after('document_type');
            $table->string('quality')->nullable()->after('status'); // good, fair, poor, illegible

            // Review workflow
            $table->foreignId('reviewed_by')->nullable()->after('uploaded_by')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('rejection_reason')->nullable()->after('reviewed_at');

            // Linking workflow
            $table->foreignId('linked_by')->nullable()->after('rejection_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('linked_at')->nullable()->after('linked_by');

            // Additional metadata
            $table->string('source')->default('scanned')->after('linked_at'); // scanned, landlord_upload, system_generated
            $table->year('document_year')->nullable()->after('document_date'); // for organization
            $table->text('notes')->nullable()->after('document_year');
            $table->json('metadata')->nullable()->after('notes'); // OCR data, extracted text, etc.

            // Compression tracking
            $table->string('compression_method')->nullable()->after('is_compressed'); // gzip, zip, none
            $table->timestamp('compressed_at')->nullable()->after('compression_method');

            // Indexes for common queries
            $table->index('status');
            $table->index('quality');
            $table->index('source');
            $table->index('document_year');
            $table->index(['zone_id', 'status']);
            $table->index(['property_id', 'status']);
            $table->index(['uploaded_by', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('lease_documents', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['status']);
            $table->dropIndex(['quality']);
            $table->dropIndex(['source']);
            $table->dropIndex(['document_year']);
            $table->dropIndex(['zone_id', 'status']);
            $table->dropIndex(['property_id', 'status']);
            $table->dropIndex(['uploaded_by', 'status']);

            // Drop foreign keys
            $table->dropForeign(['zone_id']);
            $table->dropForeign(['property_id']);
            $table->dropForeign(['tenant_id']);
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['reviewed_by']);
            $table->dropForeign(['linked_by']);

            // Drop columns
            $table->dropColumn([
                'zone_id',
                'property_id',
                'tenant_id',
                'unit_id',
                'status',
                'quality',
                'reviewed_by',
                'reviewed_at',
                'rejection_reason',
                'linked_by',
                'linked_at',
                'source',
                'document_year',
                'notes',
                'metadata',
                'compression_method',
                'compressed_at',
            ]);
        });
    }
};
