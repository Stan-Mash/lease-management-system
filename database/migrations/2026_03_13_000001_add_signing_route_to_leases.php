<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add signing_route and fully_executed_at to the leases table.
 *
 * signing_route = 'manager' → Route 2: lessor is Chabrin (manager countersigns)
 * signing_route = 'landlord' → Route 1: lessor is the property owner (landlord signs)
 *
 * fully_executed_at records when the final advocate certification was completed.
 * Leases sit in 'fully_executed' state until start_date ≤ today, at which point
 * the scheduler (or observer) transitions them to 'active'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            // 'manager' = Chabrin countersigns (Route 2, the default)
            // 'landlord' = property owner signs as lessor (Route 1)
            $table->string('signing_route', 20)->default('manager')->after('workflow_state');
            $table->timestamp('fully_executed_at')->nullable()->after('countersigned_at');
        });
    }

    public function down(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->dropColumn(['signing_route', 'fully_executed_at']);
        });
    }
};
