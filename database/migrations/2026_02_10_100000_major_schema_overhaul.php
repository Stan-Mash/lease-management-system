<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Major schema overhaul migration.
     *
     * 1. Add date_created to core tables (backfilled from created_at)
     * 2. Rename landlords.landlord_code -> lan_id
     * 3. Rename properties.management_commission -> commission, add field_officer_id/zone_manager_id
     * 4. Add unit_code, field_officer_id, zone_manager_id to units
     * 5. Add field_officer_id, zone_manager_id to tenants
     * 6. Add lease_reference_number, unit_code, zone_manager_id to leases
     * 7. Add unit_code to lease_documents
     * 8. Add delegation columns to users (availability_status, backup_officer_id, acting_for_user_id)
     * 9. Backfill unit_code from property_code + unit_number
     * 10. Backfill date_created from created_at
     * 11. Backfill lease unit_code from the unit's unit_code
     */
    public function up(): void
    {
        // ---------------------------------------------------------------
        // 1. Add date_created to all core tables
        // ---------------------------------------------------------------
        $tablesForDateCreated = [
            'properties',
            'units',
            'tenants',
            'leases',
            'lease_documents',
            'users',
            'landlords',
        ];

        foreach ($tablesForDateCreated as $tableName) {
            if (! Schema::hasColumn($tableName, 'date_created')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->timestamp('date_created')->nullable();
                });
            }
        }

        // ---------------------------------------------------------------
        // 2. Landlords: rename landlord_code -> lan_id
        // ---------------------------------------------------------------
        if (Schema::hasColumn('landlords', 'landlord_code') && ! Schema::hasColumn('landlords', 'lan_id')) {
            Schema::table('landlords', function (Blueprint $table) {
                $table->renameColumn('landlord_code', 'lan_id');
            });
        }

        // ---------------------------------------------------------------
        // 3. Properties: rename management_commission -> commission,
        //    add field_officer_id, zone_manager_id
        // ---------------------------------------------------------------
        if (Schema::hasColumn('properties', 'management_commission') && ! Schema::hasColumn('properties', 'commission')) {
            Schema::table('properties', function (Blueprint $table) {
                $table->renameColumn('management_commission', 'commission');
            });
        }

        Schema::table('properties', function (Blueprint $table) {
            if (! Schema::hasColumn('properties', 'field_officer_id')) {
                $table->foreignId('field_officer_id')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null');
            }
            if (! Schema::hasColumn('properties', 'zone_manager_id')) {
                $table->foreignId('zone_manager_id')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null');
            }
        });

        // ---------------------------------------------------------------
        // 4. Units: add unit_code (unique, nullable initially for backfill),
        //    field_officer_id, zone_manager_id
        // ---------------------------------------------------------------
        Schema::table('units', function (Blueprint $table) {
            if (! Schema::hasColumn('units', 'unit_code')) {
                $table->string('unit_code')->nullable()->unique();
            }
            if (! Schema::hasColumn('units', 'field_officer_id')) {
                $table->foreignId('field_officer_id')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null');
            }
            if (! Schema::hasColumn('units', 'zone_manager_id')) {
                $table->foreignId('zone_manager_id')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null');
            }
        });

        // ---------------------------------------------------------------
        // 5. Tenants: add field_officer_id, zone_manager_id
        // ---------------------------------------------------------------
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'field_officer_id')) {
                $table->foreignId('field_officer_id')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null');
            }
            if (! Schema::hasColumn('tenants', 'zone_manager_id')) {
                $table->foreignId('zone_manager_id')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null');
            }
        });

        // ---------------------------------------------------------------
        // 6. Leases: add lease_reference_number, unit_code, zone_manager_id
        //    (assigned_field_officer_id already exists — skip)
        //    (qr_code_url already covered by verification_url / qr_code_path — skip)
        // ---------------------------------------------------------------
        Schema::table('leases', function (Blueprint $table) {
            if (! Schema::hasColumn('leases', 'lease_reference_number')) {
                $table->string('lease_reference_number')->nullable()->unique();
            }
            if (! Schema::hasColumn('leases', 'unit_code')) {
                $table->string('unit_code')->nullable()->index();
            }
            if (! Schema::hasColumn('leases', 'zone_manager_id')) {
                $table->foreignId('zone_manager_id')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null');
            }
        });

        // ---------------------------------------------------------------
        // 7. Lease Documents: add unit_code
        // ---------------------------------------------------------------
        Schema::table('lease_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('lease_documents', 'unit_code')) {
                $table->string('unit_code')->nullable()->index();
            }
        });

        // ---------------------------------------------------------------
        // 8. Users: delegation system columns
        //    - availability_status (varchar, default 'available')
        //    - backup_officer_id (FK -> users, nullable, onDelete set null)
        //    - acting_for_user_id (FK -> users, nullable, onDelete set null)
        // ---------------------------------------------------------------
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'availability_status')) {
                $table->string('availability_status', 20)->default('available')
                    ->comment('Availability: available, on_leave, away');
            }
            if (! Schema::hasColumn('users', 'backup_officer_id')) {
                $table->foreignId('backup_officer_id')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null');
            }
            if (! Schema::hasColumn('users', 'acting_for_user_id')) {
                $table->foreignId('acting_for_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null');
            }
        });

        // ---------------------------------------------------------------
        // 9. Backfill unit_code from property_code + unit_number
        // ---------------------------------------------------------------
        DB::statement("
            UPDATE units
            SET unit_code = (
                SELECT p.property_code || '-' || units.unit_number
                FROM properties p
                WHERE p.id = units.property_id
            )
            WHERE unit_code IS NULL
        ");

        // ---------------------------------------------------------------
        // 10. Backfill date_created from created_at for all tables
        // ---------------------------------------------------------------
        foreach ($tablesForDateCreated as $tableName) {
            DB::statement("
                UPDATE {$tableName}
                SET date_created = created_at
                WHERE date_created IS NULL
                  AND created_at IS NOT NULL
            ");
        }

        // ---------------------------------------------------------------
        // 11. Backfill lease unit_code from the unit's unit_code
        // ---------------------------------------------------------------
        DB::statement('
            UPDATE leases
            SET unit_code = (
                SELECT u.unit_code
                FROM units u
                WHERE u.id = leases.unit_id
            )
            WHERE leases.unit_code IS NULL
              AND leases.unit_id IS NOT NULL
        ');

        // ---------------------------------------------------------------
        // 12. Backfill lease_documents.unit_code from the unit via the lease
        // ---------------------------------------------------------------
        DB::statement('
            UPDATE lease_documents
            SET unit_code = (
                SELECT u.unit_code
                FROM leases l
                JOIN units u ON u.id = l.unit_id
                WHERE l.id = lease_documents.lease_id
            )
            WHERE lease_documents.unit_code IS NULL
              AND lease_documents.lease_id IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ---------------------------------------------------------------
        // 8. Users: drop delegation columns
        // ---------------------------------------------------------------
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'acting_for_user_id')) {
                $table->dropForeign(['acting_for_user_id']);
            }
            if (Schema::hasColumn('users', 'backup_officer_id')) {
                $table->dropForeign(['backup_officer_id']);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('users', 'acting_for_user_id')) {
                $columnsToDrop[] = 'acting_for_user_id';
            }
            if (Schema::hasColumn('users', 'backup_officer_id')) {
                $columnsToDrop[] = 'backup_officer_id';
            }
            if (Schema::hasColumn('users', 'availability_status')) {
                $columnsToDrop[] = 'availability_status';
            }
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        // ---------------------------------------------------------------
        // 7. Lease Documents: drop unit_code
        // ---------------------------------------------------------------
        Schema::table('lease_documents', function (Blueprint $table) {
            if (Schema::hasColumn('lease_documents', 'unit_code')) {
                $table->dropIndex(['unit_code']);
                $table->dropColumn('unit_code');
            }
        });

        // ---------------------------------------------------------------
        // 6. Leases: drop lease_reference_number, unit_code, zone_manager_id
        // ---------------------------------------------------------------
        Schema::table('leases', function (Blueprint $table) {
            if (Schema::hasColumn('leases', 'zone_manager_id')) {
                $table->dropForeign(['zone_manager_id']);
            }
        });

        Schema::table('leases', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('leases', 'lease_reference_number')) {
                $columnsToDrop[] = 'lease_reference_number';
            }
            if (Schema::hasColumn('leases', 'unit_code')) {
                $table->dropIndex(['unit_code']);
                $columnsToDrop[] = 'unit_code';
            }
            if (Schema::hasColumn('leases', 'zone_manager_id')) {
                $columnsToDrop[] = 'zone_manager_id';
            }
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        // ---------------------------------------------------------------
        // 5. Tenants: drop field_officer_id, zone_manager_id
        // ---------------------------------------------------------------
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'field_officer_id')) {
                $table->dropForeign(['field_officer_id']);
            }
            if (Schema::hasColumn('tenants', 'zone_manager_id')) {
                $table->dropForeign(['zone_manager_id']);
            }
        });

        Schema::table('tenants', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('tenants', 'field_officer_id')) {
                $columnsToDrop[] = 'field_officer_id';
            }
            if (Schema::hasColumn('tenants', 'zone_manager_id')) {
                $columnsToDrop[] = 'zone_manager_id';
            }
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        // ---------------------------------------------------------------
        // 4. Units: drop unit_code, field_officer_id, zone_manager_id
        // ---------------------------------------------------------------
        Schema::table('units', function (Blueprint $table) {
            if (Schema::hasColumn('units', 'field_officer_id')) {
                $table->dropForeign(['field_officer_id']);
            }
            if (Schema::hasColumn('units', 'zone_manager_id')) {
                $table->dropForeign(['zone_manager_id']);
            }
        });

        Schema::table('units', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('units', 'unit_code')) {
                $table->dropUnique(['unit_code']);
                $columnsToDrop[] = 'unit_code';
            }
            if (Schema::hasColumn('units', 'field_officer_id')) {
                $columnsToDrop[] = 'field_officer_id';
            }
            if (Schema::hasColumn('units', 'zone_manager_id')) {
                $columnsToDrop[] = 'zone_manager_id';
            }
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        // ---------------------------------------------------------------
        // 3. Properties: drop field_officer_id, zone_manager_id;
        //    rename commission -> management_commission
        // ---------------------------------------------------------------
        Schema::table('properties', function (Blueprint $table) {
            if (Schema::hasColumn('properties', 'field_officer_id')) {
                $table->dropForeign(['field_officer_id']);
            }
            if (Schema::hasColumn('properties', 'zone_manager_id')) {
                $table->dropForeign(['zone_manager_id']);
            }
        });

        Schema::table('properties', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('properties', 'field_officer_id')) {
                $columnsToDrop[] = 'field_officer_id';
            }
            if (Schema::hasColumn('properties', 'zone_manager_id')) {
                $columnsToDrop[] = 'zone_manager_id';
            }
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        if (Schema::hasColumn('properties', 'commission') && ! Schema::hasColumn('properties', 'management_commission')) {
            Schema::table('properties', function (Blueprint $table) {
                $table->renameColumn('commission', 'management_commission');
            });
        }

        // ---------------------------------------------------------------
        // 2. Landlords: rename lan_id -> landlord_code
        // ---------------------------------------------------------------
        if (Schema::hasColumn('landlords', 'lan_id') && ! Schema::hasColumn('landlords', 'landlord_code')) {
            Schema::table('landlords', function (Blueprint $table) {
                $table->renameColumn('lan_id', 'landlord_code');
            });
        }

        // ---------------------------------------------------------------
        // 1. Drop date_created from all tables
        // ---------------------------------------------------------------
        $tablesForDateCreated = [
            'properties',
            'units',
            'tenants',
            'leases',
            'lease_documents',
            'users',
            'landlords',
        ];

        foreach ($tablesForDateCreated as $tableName) {
            if (Schema::hasColumn($tableName, 'date_created')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('date_created');
                });
            }
        }
    }
};
