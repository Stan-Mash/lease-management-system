<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lease_approvals', function (Blueprint $table) {
            $table->string('latitude', 32)->nullable()->after('token_expires_at');
            $table->string('longitude', 32)->nullable()->after('latitude');
        });

        Schema::table('lease_lawyer_tracking', function (Blueprint $table) {
            $table->string('latitude', 32)->nullable()->after('lawyer_link_expires_at');
            $table->string('longitude', 32)->nullable()->after('latitude');
        });

        Schema::table('lease_witnesses', function (Blueprint $table) {
            $table->string('witness_id_number', 100)->nullable()->after('lsk_number');
            $table->string('witness_signature_path', 500)->nullable()->after('witness_id_number');
        });
    }

    public function down(): void
    {
        Schema::table('lease_approvals', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });

        Schema::table('lease_lawyer_tracking', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });

        Schema::table('lease_witnesses', function (Blueprint $table) {
            $table->dropColumn(['witness_id_number', 'witness_signature_path']);
        });
    }
};

