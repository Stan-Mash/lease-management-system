<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds preferred_language column to tenants table for SMS localization.
 * Supports English (en) and Swahili (sw) for improved OTP conversion
 * and tenant communication in the Kenyan market.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'preferred_language')) {
                $table->string('preferred_language', 5)
                    ->default('en')
                    ->after('notification_preference')
                    ->comment('Preferred SMS language: en (English), sw (Swahili)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'preferred_language')) {
                $table->dropColumn('preferred_language');
            }
        });
    }
};
