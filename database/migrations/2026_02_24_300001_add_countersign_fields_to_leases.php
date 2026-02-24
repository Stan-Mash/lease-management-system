<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add property manager countersignature fields to the leases table.
 *
 * These are populated when the manager clicks "Countersign & Activate Lease"
 * on the ViewLease page. The countersignature record (name + timestamp) is
 * stored alongside the tenant's digital signature for audit purposes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->string('countersigned_by')->nullable()->after('signing_mode')
                ->comment('Full name of the property manager who countersigned');
            $table->timestamp('countersigned_at')->nullable()->after('countersigned_by')
                ->comment('Timestamp when the manager countersigned');
            $table->text('countersign_notes')->nullable()->after('countersigned_at')
                ->comment('Optional notes recorded by the manager at countersigning');
        });
    }

    public function down(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->dropColumn(['countersigned_by', 'countersigned_at', 'countersign_notes']);
        });
    }
};
