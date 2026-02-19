<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The otp_verifications.code column was originally varchar(6) intended for
     * storing raw OTP digits, but OTPService hashes the code with Hash::make()
     * (bcrypt) before storing — producing a ~60-character string that exceeds
     * the column limit and causes SQLSTATE[22001] on every OTP request.
     *
     * Fix: widen to varchar(255) to accommodate bcrypt hashes, and drop the
     * direct index on `code` (hashes should never be queried directly).
     */
    public function up(): void
    {
        Schema::table('otp_verifications', function (Blueprint $table) {
            // Drop the index on `code` before changing the column
            // (some drivers require index removal before column modification)
            $table->dropIndex(['code']);

            // Widen to 255 to accommodate bcrypt hashes (~60 chars)
            $table->string('code', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->string('code', 6)->change();
            $table->index('code');
        });
    }
};
