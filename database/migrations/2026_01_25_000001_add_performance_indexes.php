<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add performance indexes for frequently queried columns.
     * These indexes improve query performance for:
     * - OTP verification lookups
     * - Audit log queries
     * - Digital signature lookups
     * - Lease approval queries
     */
    public function up(): void
    {
        // OTP Verifications - composite index for lease lookups with time ordering
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->index(['lease_id', 'created_at'], 'otp_lease_created_idx');
            $table->index(['lease_id', 'is_verified', 'is_expired'], 'otp_lease_status_idx');
            $table->index(['phone', 'created_at'], 'otp_phone_created_idx');
        });

        // Lease Audit Logs - indexes for audit queries
        Schema::table('lease_audit_logs', function (Blueprint $table) {
            $table->index(['lease_id', 'created_at'], 'audit_lease_created_idx');
            $table->index(['lease_id', 'action'], 'audit_lease_action_idx');
            $table->index(['user_id', 'created_at'], 'audit_user_created_idx');
        });

        // Digital Signatures - index for lease lookups
        Schema::table('digital_signatures', function (Blueprint $table) {
            $table->index(['lease_id', 'signed_at'], 'signature_lease_signed_idx');
            $table->index(['tenant_id', 'signed_at'], 'signature_tenant_signed_idx');
        });

        // Lease Approvals - indexes for approval status queries
        Schema::table('lease_approvals', function (Blueprint $table) {
            $table->index(['lease_id', 'decision'], 'approval_lease_decision_idx');
            $table->index(['landlord_id', 'decision'], 'approval_landlord_decision_idx');
            $table->index(['lease_id', 'created_at'], 'approval_lease_created_idx');
        });

        // Leases - additional performance indexes
        Schema::table('leases', function (Blueprint $table) {
            $table->index(['workflow_state', 'created_at'], 'lease_state_created_idx');
            $table->index(['zone_id', 'workflow_state'], 'lease_zone_state_idx');
            $table->index(['assigned_field_officer_id', 'workflow_state'], 'lease_officer_state_idx');
            $table->index(['tenant_id', 'workflow_state'], 'lease_tenant_state_idx');
            $table->index(['landlord_id', 'workflow_state'], 'lease_landlord_state_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->dropIndex('otp_lease_created_idx');
            $table->dropIndex('otp_lease_status_idx');
            $table->dropIndex('otp_phone_created_idx');
        });

        Schema::table('lease_audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_lease_created_idx');
            $table->dropIndex('audit_lease_action_idx');
            $table->dropIndex('audit_user_created_idx');
        });

        Schema::table('digital_signatures', function (Blueprint $table) {
            $table->dropIndex('signature_lease_signed_idx');
            $table->dropIndex('signature_tenant_signed_idx');
        });

        Schema::table('lease_approvals', function (Blueprint $table) {
            $table->dropIndex('approval_lease_decision_idx');
            $table->dropIndex('approval_landlord_decision_idx');
            $table->dropIndex('approval_lease_created_idx');
        });

        Schema::table('leases', function (Blueprint $table) {
            $table->dropIndex('lease_state_created_idx');
            $table->dropIndex('lease_zone_state_idx');
            $table->dropIndex('lease_officer_state_idx');
            $table->dropIndex('lease_tenant_state_idx');
            $table->dropIndex('lease_landlord_state_idx');
        });
    }
};
