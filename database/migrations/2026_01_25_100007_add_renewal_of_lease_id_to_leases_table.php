<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            if (! Schema::hasColumn('leases', 'renewal_of_lease_id')) {
                $table->foreignId('renewal_of_lease_id')
                    ->nullable()
                    ->after('zone_id')
                    ->constrained('leases')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            if (Schema::hasColumn('leases', 'renewal_of_lease_id')) {
                $table->dropForeign(['renewal_of_lease_id']);
                $table->dropColumn('renewal_of_lease_id');
            }
        });
    }
};
