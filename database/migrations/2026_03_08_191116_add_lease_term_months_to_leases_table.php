<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add lease_term_months to the leases table.
     *
     * This column is set in CreateLease::mutateFormDataBeforeCreate() based on the
     * diff between start_date and end_date, and is used by getDurationMonthsAttribute()
     * to drive the "Grant of Lease" duration display on the PDF overlay.
     * The column was present in $fillable and $casts on the Lease model but was never
     * added via a migration, causing every lease creation to fail with SQLSTATE[42703].
     */
    public function up(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->integer('lease_term_months')->nullable()->after('end_date');
        });
    }

    public function down(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->dropColumn('lease_term_months');
        });
    }
};
