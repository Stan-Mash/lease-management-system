<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── 1. Add zone_id to units and tenants tables ───
        if (!Schema::hasColumn('units', 'zone_id')) {
            Schema::table('units', function ($table) {
                $table->foreignId('zone_id')->nullable()->after('property_id')->constrained('zones')->nullOnDelete();
                $table->index('zone_id');
            });
        }

        if (!Schema::hasColumn('tenants', 'zone_id')) {
            Schema::table('tenants', function ($table) {
                $table->foreignId('zone_id')->nullable()->after('id')->constrained('zones')->nullOnDelete();
                $table->index('zone_id');
            });
        }

        // ─── 2. Create 6 zones: AB (A+B combined), C, D, E, F, G ───
        $zones = [
            ['name' => 'Zone AB', 'code' => 'ZN-AB', 'description' => 'Zone A and B combined', 'zone_manager_id' => null], // Zipporah
            ['name' => 'Zone C',  'code' => 'ZN-C',  'description' => 'Zone C',               'zone_manager_id' => null], // Salivan
            ['name' => 'Zone D',  'code' => 'ZN-D',  'description' => 'Zone D',               'zone_manager_id' => null], // Florence Mutei
            ['name' => 'Zone E',  'code' => 'ZN-E',  'description' => 'Zone E',               'zone_manager_id' => null], // Florence Karimi
            ['name' => 'Zone F',  'code' => 'ZN-F',  'description' => 'Zone F',               'zone_manager_id' => null], // Dennis Mawira
            ['name' => 'Zone G',  'code' => 'ZN-G',  'description' => 'Zone G',               'zone_manager_id' => null], // Sheila
        ];

        $zoneIds = [];
        foreach ($zones as $zone) {
            $existing = DB::table('zones')->where('code', $zone['code'])->first();
            if ($existing) {
                $zoneIds[$zone['code']] = $existing->id;
                // Update zone manager assignment
                DB::table('zones')->where('id', $existing->id)->update([
                    'zone_manager_id' => $zone['zone_manager_id'],
                    'is_active' => true,
                ]);
            } else {
                $zoneIds[$zone['code']] = DB::table('zones')->insertGetId([
                    'name' => $zone['name'],
                    'code' => $zone['code'],
                    'description' => $zone['description'],
                    'zone_manager_id' => $zone['zone_manager_id'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // ─── 3. Map property zone letters to zone IDs ───
        // A and B → Zone AB
        $zoneAbId = $zoneIds['ZN-AB'];
        DB::table('properties')->whereIn('zone', ['A', 'B'])->update(['zone_id' => $zoneAbId]);

        // C, D, E, F, G → matching zone
        $letterToCode = ['C' => 'ZN-C', 'D' => 'ZN-D', 'E' => 'ZN-E', 'F' => 'ZN-F', 'G' => 'ZN-G'];
        foreach ($letterToCode as $letter => $code) {
            DB::table('properties')->where('zone', $letter)->update(['zone_id' => $zoneIds[$code]]);
        }

        // ─── 4. Propagate zone_id to units from their properties ───
        DB::statement("
            UPDATE units
            SET zone_id = properties.zone_id
            FROM properties
            WHERE units.property_id = properties.id
            AND properties.zone_id IS NOT NULL
        ");

        // ─── 5. Propagate zone_id to leases from their properties ───
        DB::statement("
            UPDATE leases
            SET zone_id = properties.zone_id
            FROM properties
            WHERE leases.property_id = properties.id
            AND properties.zone_id IS NOT NULL
            AND leases.zone_id IS NULL
        ");

        // ─── 6. Propagate zone_id to tenants from their latest lease's property ───
        // For tenants, we set zone_id from their most recent lease
        DB::statement("
            UPDATE tenants
            SET zone_id = sub.zone_id
            FROM (
                SELECT DISTINCT ON (tenant_id) tenant_id, zone_id
                FROM leases
                WHERE zone_id IS NOT NULL
                ORDER BY tenant_id, created_at DESC
            ) sub
            WHERE tenants.id = sub.tenant_id
            AND tenants.zone_id IS NULL
        ");

        // ─── 7. Assign zone_id to zone manager users ───
        $managerAssignments = [
            14 => $zoneIds['ZN-AB'],  // Zipporah → AB
            16 => $zoneIds['ZN-C'],   // Salivan → C
            18 => $zoneIds['ZN-G'],   // Sheila → G
            20 => $zoneIds['ZN-D'],   // Florence Mutei → D
            27 => $zoneIds['ZN-E'],   // Florence Karimi → E
            45 => $zoneIds['ZN-F'],   // Dennis Mawira → F
        ];

        foreach ($managerAssignments as $userId => $zoneId) {
            DB::table('users')->where('id', $userId)->update([
                'zone_id' => $zoneId,
                'role' => 'zone_manager',
            ]);
        }

        // ─── 8. Assign auditors to zones ───
        $auditorAssignments = [
            30 => $zoneIds['ZN-AB'],  // Albin → AB
            6  => $zoneIds['ZN-D'],   // Lucy Muriithi → D
            9  => $zoneIds['ZN-G'],   // Lucy Karimi → G
            25 => $zoneIds['ZN-F'],   // Oliver → F
            35 => $zoneIds['ZN-E'],   // Dennis Gitonga → E
        ];

        foreach ($auditorAssignments as $userId => $zoneId) {
            DB::table('users')->where('id', $userId)->update([
                'zone_id' => $zoneId,
                'role' => 'auditor',
            ]);
            // Sync Spatie role
            $this->syncSpatieRole($userId, 'auditor');
        }

        // ─── 9. Change Wilfred Wamae (id=12) to internal_auditor ───
        DB::table('users')->where('id', 12)->update([
            'role' => 'internal_auditor',
            'zone_id' => null, // Internal auditor sees all zones
        ]);
        $this->syncSpatieRole(12, 'internal_auditor');

        // ─── 10. Assign permissions to internal_auditor role ───
        // Internal auditor gets all auditor permissions plus cross-zone access
        $auditorRole = DB::table('roles')->where('name', 'auditor')->first();
        $internalAuditorRole = DB::table('roles')->where('name', 'internal_auditor')->first();

        if ($auditorRole && $internalAuditorRole) {
            // Get all auditor permissions
            $auditorPermIds = DB::table('role_has_permissions')
                ->where('role_id', $auditorRole->id)
                ->pluck('permission_id');

            // Clear existing internal_auditor permissions and re-assign
            DB::table('role_has_permissions')->where('role_id', $internalAuditorRole->id)->delete();

            $inserts = [];
            foreach ($auditorPermIds as $permId) {
                $inserts[] = [
                    'permission_id' => $permId,
                    'role_id' => $internalAuditorRole->id,
                ];
            }

            // Also add any additional permissions for cross-zone view
            $additionalPerms = ['view_all_zones', 'view_field_officer_dashboard'];
            foreach ($additionalPerms as $permName) {
                $perm = DB::table('permissions')->where('name', $permName)->first();
                if ($perm && !in_array($perm->id, $auditorPermIds->toArray())) {
                    $inserts[] = [
                        'permission_id' => $perm->id,
                        'role_id' => $internalAuditorRole->id,
                    ];
                }
            }

            if (!empty($inserts)) {
                DB::table('role_has_permissions')->insert($inserts);
            }
        }

        // ─── 11. Sync Spatie roles for zone managers ───
        foreach ($managerAssignments as $userId => $zoneId) {
            $this->syncSpatieRole($userId, 'zone_manager');
        }

        // ─── 12. Clear Spatie permission cache ───
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Remove zone_id from units and tenants
        Schema::table('units', function ($table) {
            if (Schema::hasColumn('units', 'zone_id')) {
                $table->dropForeign(['zone_id']);
                $table->dropColumn('zone_id');
            }
        });

        Schema::table('tenants', function ($table) {
            if (Schema::hasColumn('tenants', 'zone_id')) {
                $table->dropForeign(['zone_id']);
                $table->dropColumn('zone_id');
            }
        });

        // Clear zone_id from properties and leases
        DB::table('properties')->update(['zone_id' => null]);
        DB::table('leases')->update(['zone_id' => null]);

        // Delete created zones
        DB::table('zones')->whereIn('code', ['ZN-AB', 'ZN-C', 'ZN-D', 'ZN-E', 'ZN-F', 'ZN-G'])->delete();
    }

    private function syncSpatieRole(int $userId, string $roleName): void
    {
        $role = DB::table('roles')->where('name', $roleName)->where('guard_name', 'web')->first();
        if (!$role) {
            return;
        }

        // Remove all existing roles for this user
        DB::table('model_has_roles')
            ->where('model_id', $userId)
            ->where('model_type', 'App\\Models\\User')
            ->delete();

        // Assign new role
        DB::table('model_has_roles')->insert([
            'role_id' => $role->id,
            'model_type' => 'App\\Models\\User',
            'model_id' => $userId,
        ]);
    }
};
