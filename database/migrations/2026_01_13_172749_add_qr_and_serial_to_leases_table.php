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
        Schema::table('leases', function (Blueprint $table) {
            $table->string('serial_number')->unique()->nullable()->after('reference_number')
                ->comment('Unique serial number for document tracking (e.g., LSE-2026-0001)');
            $table->text('qr_code_data')->nullable()->after('signed_pdf_hash')
                ->comment('QR code data payload (JSON with verification info)');
            $table->string('qr_code_path')->nullable()->after('qr_code_data')
                ->comment('Path to generated QR code image file');
            $table->timestamp('qr_generated_at')->nullable()->after('qr_code_path')
                ->comment('When the QR code was generated');
            $table->string('verification_url')->nullable()->after('qr_generated_at')
                ->comment('Public URL for QR code verification');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->dropColumn([
                'serial_number',
                'qr_code_data',
                'qr_code_path',
                'qr_generated_at',
                'verification_url',
            ]);
        });
    }
};
