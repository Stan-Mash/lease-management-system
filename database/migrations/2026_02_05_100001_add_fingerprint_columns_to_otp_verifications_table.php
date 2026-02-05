<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->json('device_fingerprint')->nullable()->after('verified_at');
            $table->unsignedTinyInteger('risk_score')->nullable()->after('device_fingerprint');
            $table->string('user_agent')->nullable()->after('verification_ip');
        });
    }

    public function down(): void
    {
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->dropColumn(['device_fingerprint', 'risk_score', 'user_agent']);
        });
    }
};
