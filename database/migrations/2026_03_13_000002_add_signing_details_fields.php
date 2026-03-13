<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Manager/staff national ID for lessor signing block (Option A PDF overlay)
        Schema::table('users', function (Blueprint $table) {
            $table->string('national_id')->nullable()->after('phone')
                ->comment('Encrypted national ID for manager signing block on lease PDF');
        });

        // 2. LSK practicing certificate number for advocates
        Schema::table('lawyers', function (Blueprint $table) {
            $table->string('lsk_number', 50)->nullable()->after('firm')
                ->comment('Law Society of Kenya practicing certificate number');
        });

        // 3. Extend lease_lawyer_tracking to support:
        //    - Two advocate slots per lease (lessor side + lessee side)
        //    - Own-advocate contact details (captured before Lawyer record exists)
        //    - Advocate self-fills their details via portal
        Schema::table('lease_lawyer_tracking', function (Blueprint $table) {
            // Which side of the lease this advocate certification is for
            $table->string('side', 10)->default('lessee')->after('lease_id')
                ->comment('"lessor" or "lessee" — which party this certification covers');

            // Advocate details self-filled via portal (for own advocates)
            // For Chabrin advocates, these mirror the Lawyer model fields
            $table->string('advocate_name')->nullable()->after('advocate_lsk_number')
                ->comment('Filled by advocate in portal, or mirrored from Lawyer.name');
            $table->string('advocate_firm')->nullable()->after('advocate_name')
                ->comment('Filled by advocate in portal, or mirrored from Lawyer.firm');

            // Contact fields for own advocates (no Lawyer record, lawyer_id = null)
            $table->string('advocate_email')->nullable()->after('advocate_firm')
                ->comment('Own advocate email — used to send portal link');
            $table->string('advocate_phone', 30)->nullable()->after('advocate_email')
                ->comment('Own advocate phone — used for OTP verification');
        });

        // 4. Lease: add lessor advocate contact fields (mirrors lessee side already on model)
        //    and lessee advocate phone (email already exists as tenant_advocate_email)
        Schema::table('leases', function (Blueprint $table) {
            $table->string('lessee_advocate_phone', 30)->nullable()->after('tenant_advocate_email')
                ->comment('Tenant-selected advocate phone for OTP');
            $table->string('lessor_advocate_name')->nullable()->after('lessee_advocate_phone')
                ->comment('Lessor-selected own advocate name');
            $table->string('lessor_advocate_email')->nullable()->after('lessor_advocate_name')
                ->comment('Lessor-selected own advocate email');
            $table->string('lessor_advocate_phone', 30)->nullable()->after('lessor_advocate_email')
                ->comment('Lessor-selected own advocate phone for OTP');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('national_id');
        });

        Schema::table('lawyers', function (Blueprint $table) {
            $table->dropColumn('lsk_number');
        });

        Schema::table('lease_lawyer_tracking', function (Blueprint $table) {
            $table->dropColumn(['side', 'advocate_name', 'advocate_firm', 'advocate_email', 'advocate_phone']);
        });

        Schema::table('leases', function (Blueprint $table) {
            $table->dropColumn(['lessee_advocate_phone', 'lessor_advocate_name', 'lessor_advocate_email', 'lessor_advocate_phone']);
        });
    }
};
