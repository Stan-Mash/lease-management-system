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
            $table->foreignId('lease_template_id')
                  ->nullable()
                  ->after('lease_type')
                  ->constrained('lease_templates')
                  ->nullOnDelete();

            $table->integer('template_version_used')->nullable()->after('lease_template_id');

            $table->index('lease_template_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->dropForeign(['lease_template_id']);
            $table->dropIndex(['lease_template_id']);
            $table->dropColumn(['lease_template_id', 'template_version_used']);
        });
    }
};
