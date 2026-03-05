<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lease_approvals', function (Blueprint $table) {
            $table->string('token', 64)->nullable()->unique()->after('metadata');
            $table->timestamp('token_expires_at')->nullable()->after('token');
        });
    }

    public function down(): void
    {
        Schema::table('lease_approvals', function (Blueprint $table) {
            $table->dropColumn(['token', 'token_expires_at']);
        });
    }
};
