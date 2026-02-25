<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extend digital_signatures to support property manager countersignatures.
 *
 * Previously the table only stored tenant signatures (tenant_id was required).
 * This migration:
 *  - Makes tenant_id nullable (manager signatures have no tenant)
 *  - Adds signer_type: 'tenant' | 'manager'
 *  - Adds signed_by_user_id (FK to users) for manager signatures
 *  - Adds signed_by_name (display name stored at signing time — survives user renames)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('digital_signatures', function (Blueprint $table) {
            // Make tenant_id nullable — manager signatures won't have one
            $table->foreignId('tenant_id')->nullable()->change();

            // Who is the signer? 'tenant' (default, preserves all existing rows) or 'manager'
            $table->string('signer_type', 20)->default('tenant')->after('tenant_id')
                ->comment("'tenant' or 'manager'");

            // For manager signatures: the authenticated user who countersigned
            $table->foreignId('signed_by_user_id')->nullable()->constrained('users')->nullOnDelete()
                ->after('signer_type')
                ->comment('User ID of the manager who countersigned (null for tenant signatures)');

            // Human-readable name stored at signing time — never changes even if user is renamed
            $table->string('signed_by_name')->nullable()->after('signed_by_user_id')
                ->comment('Display name of the signer at time of signing');

            $table->index('signer_type');
        });
    }

    public function down(): void
    {
        Schema::table('digital_signatures', function (Blueprint $table) {
            $table->dropForeign(['signed_by_user_id']);
            $table->dropIndex(['signer_type']);
            $table->dropColumn(['signer_type', 'signed_by_user_id', 'signed_by_name']);
            $table->foreignId('tenant_id')->nullable(false)->change();
        });
    }
};
