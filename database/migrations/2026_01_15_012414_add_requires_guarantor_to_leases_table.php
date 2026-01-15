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
            // Add requires_guarantor column if it doesn't exist
            if (!Schema::hasColumn('leases', 'requires_guarantor')) {
                $table->boolean('requires_guarantor')->default(false)->after('requires_lawyer');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            if (Schema::hasColumn('leases', 'requires_guarantor')) {
                $table->dropColumn('requires_guarantor');
            }
        });
    }
};
