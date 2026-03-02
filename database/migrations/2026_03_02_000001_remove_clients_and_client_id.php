<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * This migration removes the legacy Client pivot and moves all relationships
     * to direct Tenant / Landlord foreign keys.
     *
     * IMPORTANT:
     * - Assumes CHIPS import process has already populated landlords and tenants
     *   tables, with client-style reference_numbers:
     *     - Landlords: reference_number starts with 'LAN'
     *     - Tenants:   reference_number starts with 'TEN'
     * - Any existing leases should already have tenant_id / landlord_id set;
     *   client_id is treated as legacy and will be dropped.
     */
    public function up(): void
    {
        // ---------------------------------------------------------------------
        // 1. Ensure landlord_id exists on properties and units (if missing)
        // ---------------------------------------------------------------------

        Schema::table('properties', function (Blueprint $table) {
            if (! Schema::hasColumn('properties', 'landlord_id')) {
                $table->unsignedBigInteger('landlord_id')->nullable()->after('client_id');
            }
        });

        Schema::table('units', function (Blueprint $table) {
            if (! Schema::hasColumn('units', 'landlord_id')) {
                $table->unsignedBigInteger('landlord_id')->nullable()->after('client_id');
            }
        });

        // ---------------------------------------------------------------------
        // 2. Best-effort backfill of landlord_id from client_id where possible
        // ---------------------------------------------------------------------
        //
        // Strategy:
        // - If there is a landlords.client_id matching properties.client_id /
        //   units.client_id, use that landlord.id.
        // - Otherwise leave landlord_id null and allow manual cleanup.
        //

        if (Schema::hasTable('clients') && Schema::hasTable('landlords')) {
            // Properties → landlord_id
            DB::statement("
                UPDATE properties p
                SET landlord_id = l.id
                FROM landlords l
                WHERE p.client_id IS NOT NULL
                  AND l.client_id = p.client_id
                  AND (p.landlord_id IS NULL OR p.landlord_id = 0)
            ");

            // Units → landlord_id
            DB::statement("
                UPDATE units u
                SET landlord_id = l.id
                FROM landlords l
                WHERE u.client_id IS NOT NULL
                  AND l.client_id = u.client_id
                  AND (u.landlord_id IS NULL OR u.landlord_id = 0)
            ");
        }

        // ---------------------------------------------------------------------
        // 3. Drop legacy client_id columns from properties and units
        // ---------------------------------------------------------------------

        Schema::table('properties', function (Blueprint $table) {
            if (Schema::hasColumn('properties', 'client_id')) {
                $table->dropColumn('client_id');
            }
        });

        Schema::table('units', function (Blueprint $table) {
            if (Schema::hasColumn('units', 'client_id')) {
                $table->dropColumn('client_id');
            }
        });

        // ---------------------------------------------------------------------
        // 4. Drop client_id and any direct client linkage from leases
        // ---------------------------------------------------------------------
        // Leases should already be using tenant_id / landlord_id exclusively.

        Schema::table('leases', function (Blueprint $table) {
            if (Schema::hasColumn('leases', 'client_id')) {
                $table->dropColumn('client_id');
            }
        });

        // ---------------------------------------------------------------------
        // 5. Drop the legacy clients table
        // ---------------------------------------------------------------------

        if (Schema::hasTable('clients')) {
            Schema::drop('clients');
        }
    }

    public function down(): void
    {
        // The down() method re-creates only the minimum structure required
        // to allow rollback; it does NOT attempt to fully restore legacy data.

        // 1. Re-create a minimal clients table (no data restoration)
        if (! Schema::hasTable('clients')) {
            Schema::create('clients', function (Blueprint $table) {
                $table->id();
                $table->string('names')->nullable();
                $table->string('reference_number')->nullable();
                $table->timestamps();
            });
        }

        // 2. Re-add client_id columns to properties, units, and leases
        Schema::table('properties', function (Blueprint $table) {
            if (! Schema::hasColumn('properties', 'client_id')) {
                $table->unsignedBigInteger('client_id')->nullable()->after('date_time');
            }
        });

        Schema::table('units', function (Blueprint $table) {
            if (! Schema::hasColumn('units', 'client_id')) {
                $table->unsignedBigInteger('client_id')->nullable()->after('date_time');
            }
        });

        Schema::table('leases', function (Blueprint $table) {
            if (! Schema::hasColumn('leases', 'client_id')) {
                $table->unsignedBigInteger('client_id')->nullable()->after('tenant_id');
            }
        });
    }
};

