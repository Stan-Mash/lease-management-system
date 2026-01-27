<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Additional composite indexes for workflow queries
        // Only create indexes that don't already exist from earlier migrations
        Schema::table('leases', function (Blueprint $table) {
            if (! collect(Schema::getIndexes('leases'))->pluck('name')->contains('lease_state_enddate_idx')) {
                $table->index(['workflow_state', 'end_date'], 'lease_state_enddate_idx');
            }
            if (! collect(Schema::getIndexes('leases'))->pluck('name')->contains('lease_zone_state_enddate_idx')) {
                $table->index(['zone_id', 'workflow_state', 'end_date'], 'lease_zone_state_enddate_idx');
            }
        });

        Schema::table('lease_approvals', function (Blueprint $table) {
            if (! collect(Schema::getIndexes('lease_approvals'))->pluck('name')->contains('approval_decision_reviewed_idx')) {
                $table->index(['decision', 'reviewed_at'], 'approval_decision_reviewed_idx');
            }
        });

        Schema::table('rent_escalations', function (Blueprint $table) {
            if (! collect(Schema::getIndexes('rent_escalations'))->pluck('name')->contains('escalation_applied_date_idx')) {
                $table->index(['applied', 'effective_date'], 'escalation_applied_date_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->dropIndex('lease_state_enddate_idx');
            $table->dropIndex('lease_zone_state_enddate_idx');
        });

        Schema::table('lease_approvals', function (Blueprint $table) {
            $table->dropIndex('approval_decision_reviewed_idx');
        });

        Schema::table('rent_escalations', function (Blueprint $table) {
            $table->dropIndex('escalation_applied_date_idx');
        });
    }
};
