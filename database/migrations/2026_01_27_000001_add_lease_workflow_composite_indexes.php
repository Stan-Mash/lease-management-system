<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            // Covers dashboard widgets filtering by state + end_date (expiring soon queries)
            $table->index(['workflow_state', 'end_date'], 'lease_state_enddate_idx');

            // Covers zone dashboard queries filtering by zone + state + end_date
            $table->index(['zone_id', 'workflow_state', 'end_date'], 'lease_zone_state_enddate_idx');

            // Covers field officer scoped queries
            $table->index(['assigned_field_officer_id', 'workflow_state'], 'lease_fo_state_idx');

            // Covers landlord approval queries
            $table->index(['landlord_id', 'workflow_state'], 'lease_landlord_state_idx');
        });

        Schema::table('lease_approvals', function (Blueprint $table) {
            // Covers approval stats consolidated query
            $table->index(['decision', 'reviewed_at'], 'approval_decision_reviewed_idx');
        });

        Schema::table('rent_escalations', function (Blueprint $table) {
            // Covers upcoming escalation queries
            $table->index(['applied', 'effective_date'], 'escalation_applied_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->dropIndex('lease_state_enddate_idx');
            $table->dropIndex('lease_zone_state_enddate_idx');
            $table->dropIndex('lease_fo_state_idx');
            $table->dropIndex('lease_landlord_state_idx');
        });

        Schema::table('lease_approvals', function (Blueprint $table) {
            $table->dropIndex('approval_decision_reviewed_idx');
        });

        Schema::table('rent_escalations', function (Blueprint $table) {
            $table->dropIndex('escalation_applied_date_idx');
        });
    }
};
