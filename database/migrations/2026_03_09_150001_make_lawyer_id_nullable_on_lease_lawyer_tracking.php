<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lease_lawyer_tracking', function (Blueprint $table) {
            // Allow null when tenant selects their own external advocate
            // (no Chabrin-registered Lawyer record exists for them)
            $table->foreignId('lawyer_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('lease_lawyer_tracking', function (Blueprint $table) {
            $table->foreignId('lawyer_id')->nullable(false)->change();
        });
    }
};
