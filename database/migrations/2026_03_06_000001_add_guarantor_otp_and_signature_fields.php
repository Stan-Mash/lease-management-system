<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds OTP-gated signing fields for guarantor portal.
     */
    public function up(): void
    {
        Schema::table('guarantors', function (Blueprint $table) {
            $table->string('otp_token', 64)->nullable()->after('notes');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_token');
            $table->string('signature_path', 500)->nullable()->after('otp_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guarantors', function (Blueprint $table) {
            $table->dropColumn(['otp_token', 'otp_expires_at', 'signature_path']);
        });
    }
};
