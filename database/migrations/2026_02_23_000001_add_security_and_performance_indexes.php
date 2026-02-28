<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add missing database indexes for high-frequency query columns.
 *
 * Context: The existing 2026_02_11_000001_add_performance_indexes.php covers
 * properties, units, tenants, landlords, users, and leases (zone/FO/ZM columns).
 * This migration adds the remaining missing indexes identified by the security
 * and performance audit, particularly on columns used in WHERE clauses during:
 *
 * - Digital signing portal (otp_verifications)
 * - Lease workflow queries (leases by tenant + state)
 * - Document audit queries (lease_documents by lease + status)
 * - Rent escalation application (rent_escalations by lease + applied flag)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Helper: skip if index already exists (safe to run multiple times)
        $indexExists = fn (string $table, string $name): bool => DB::select('SELECT 1 FROM pg_indexes WHERE tablename = ? AND indexname = ?', [$table, $name]) !== [];

        // otp_verifications — queried on every signing step
        Schema::table('otp_verifications', function (Blueprint $table) use ($indexExists) {
            if (! $indexExists('otp_verifications', 'otps_lease_id_idx')) {
                $table->index('lease_id', 'otps_lease_id_idx');
            }
            if (! $indexExists('otp_verifications', 'otps_is_expired_idx')) {
                $table->index('is_expired', 'otps_is_expired_idx');
            }
            if (! $indexExists('otp_verifications', 'otps_expires_at_idx')) {
                $table->index('expires_at', 'otps_expires_at_idx');
            }
            // Composite: used by OTPService::verify() → forLease()->valid()
            if (! $indexExists('otp_verifications', 'otps_lease_valid_idx')) {
                $table->index(['lease_id', 'is_expired', 'expires_at'], 'otps_lease_valid_idx');
            }
        });

        // digital_signatures — checked before every signature submission
        Schema::table('digital_signatures', function (Blueprint $table) use ($indexExists) {
            if (! $indexExists('digital_signatures', 'sigs_lease_id_idx')) {
                $table->index('lease_id', 'sigs_lease_id_idx');
            }
            if (! $indexExists('digital_signatures', 'sigs_tenant_id_idx')) {
                $table->index('tenant_id', 'sigs_tenant_id_idx');
            }
        });

        // leases — additional composite index for tenant + state lookups
        Schema::table('leases', function (Blueprint $table) use ($indexExists) {
            if (! $indexExists('leases', 'leases_tenant_state_idx')) {
                $table->index(['tenant_id', 'workflow_state'], 'leases_tenant_state_idx');
            }
            // Expiry alert queries: state = active AND end_date within N days
            if (! $indexExists('leases', 'leases_active_end_idx')) {
                $table->index(['workflow_state', 'end_date'], 'leases_active_end_idx');
            }
        });

        // lease_documents — filtered by status and lease on every document list
        Schema::table('lease_documents', function (Blueprint $table) use ($indexExists) {
            if (! $indexExists('lease_documents', 'lease_docs_lease_id_idx')) {
                $table->index('lease_id', 'lease_docs_lease_id_idx');
            }
        });

        // rent_escalations — scheduled escalation application queries
        Schema::table('rent_escalations', function (Blueprint $table) use ($indexExists) {
            if (! $indexExists('rent_escalations', 'escalations_lease_applied_idx')) {
                $table->index(['lease_id', 'applied'], 'escalations_lease_applied_idx');
            }
            if (! $indexExists('rent_escalations', 'escalations_effective_date_idx')) {
                $table->index('effective_date', 'escalations_effective_date_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rent_escalations', function (Blueprint $table) {
            $table->dropIndexIfExists('escalations_effective_date_idx');
            $table->dropIndexIfExists('escalations_lease_applied_idx');
        });

        Schema::table('lease_documents', function (Blueprint $table) {
            $table->dropIndexIfExists('lease_docs_lease_id_idx');
        });

        Schema::table('leases', function (Blueprint $table) {
            $table->dropIndexIfExists('leases_active_end_idx');
            $table->dropIndexIfExists('leases_tenant_state_idx');
        });

        Schema::table('digital_signatures', function (Blueprint $table) {
            $table->dropIndexIfExists('sigs_tenant_id_idx');
            $table->dropIndexIfExists('sigs_lease_id_idx');
        });

        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->dropIndexIfExists('otps_lease_valid_idx');
            $table->dropIndexIfExists('otps_expires_at_idx');
            $table->dropIndexIfExists('otps_is_expired_idx');
            $table->dropIndexIfExists('otps_lease_id_idx');
        });
    }
};
