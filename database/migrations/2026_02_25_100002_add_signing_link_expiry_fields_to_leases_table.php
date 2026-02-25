<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->timestamp('signing_link_expires_at')->nullable()->after('workflow_state');
            $table->timestamp('signing_link_expired_alerted_at')->nullable()->after('signing_link_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->dropColumn(['signing_link_expires_at', 'signing_link_expired_alerted_at']);
        });
    }
};
