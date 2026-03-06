<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add advocate certification tracking to lease_lawyer_tracking.
 *
 * When a lease comes back from the lawyer, staff now record:
 *  - What type of certification the advocate performed
 *  - The advocate's LSK practising certificate number
 *  - The date of formal certification
 *  - Whether the physical attested copy has been scanned and uploaded
 *
 * This satisfies Track 2 (Legal Certification) in the three-track signing model.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lease_lawyer_tracking', function (Blueprint $table) {
            // What did the advocate do?
            $table->enum('certification_type', ['review', 'witness', 'attestation', 'registration'])
                ->nullable()
                ->after('returned_notes')
                ->comment(implode(', ', [
                    'review = advocate reviewed and advised only',
                    'witness = advocate signed as a witness on the document',
                    'attestation = advocate formally attested/certified the document',
                    'registration = advocate prepared and submitted for Lands Registry registration',
                ]));

            // Advocate's LSK number for verification
            $table->string('advocate_lsk_number')->nullable()
                ->after('certification_type')
                ->comment('LSK (Law Society of Kenya) practising certificate number');

            // When did the advocate formally certify?
            $table->timestamp('certified_at')->nullable()
                ->after('advocate_lsk_number')
                ->comment('Date/time the advocate signed/attested the document');

            // Has the physical attested copy been scanned and uploaded?
            $table->boolean('physical_copy_uploaded')->default(false)
                ->after('certified_at');

            // Link to the uploaded document in lease_documents
            $table->foreignId('physical_copy_document_id')
                ->nullable()
                ->constrained('lease_documents')
                ->nullOnDelete()
                ->after('physical_copy_uploaded')
                ->comment('FK to the scanned attested copy in lease_documents');

            $table->index('certification_type');
            $table->index('certified_at');
        });
    }

    public function down(): void
    {
        Schema::table('lease_lawyer_tracking', function (Blueprint $table) {
            $table->dropForeign(['physical_copy_document_id']);
            $table->dropIndex(['certification_type']);
            $table->dropIndex(['certified_at']);
            $table->dropColumn([
                'certification_type',
                'advocate_lsk_number',
                'certified_at',
                'physical_copy_uploaded',
                'physical_copy_document_id',
            ]);
        });
    }
};
