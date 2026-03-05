<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PO Box for tenants
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('po_box', 100)->nullable()->after('address');
        });

        // PO Box for landlords
        Schema::table('landlords', function (Blueprint $table) {
            $table->string('po_box', 100)->nullable()->after('address');
        });

        // Rent review fields on leases (used for commercial lease overlay stamping)
        Schema::table('leases', function (Blueprint $table) {
            $table->unsignedSmallInteger('rent_review_years')->nullable()->after('deposit_amount');
            $table->decimal('rent_review_rate', 5, 2)->nullable()->after('rent_review_years');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('po_box');
        });
        Schema::table('landlords', function (Blueprint $table) {
            $table->dropColumn('po_box');
        });
        Schema::table('leases', function (Blueprint $table) {
            $table->dropColumn(['rent_review_years', 'rent_review_rate']);
        });
    }
};
