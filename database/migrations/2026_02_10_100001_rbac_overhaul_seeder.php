<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * RBAC Overhaul Migration
     *
     * Creates new roles (accountant, auditor, office_administrator,
     * office_admin_assistant, office_assistant), creates new permissions,
     * assigns permissions to new roles, deletes a specific user,
     * updates user role assignments, and syncs Spatie model_has_roles.
     */
    public function up(): void
    {
        // Reset cached roles and permissions at the start
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // -----------------------------------------------------------------
        // 1. Create ALL permissions (existing + new)
        // -----------------------------------------------------------------
        $allPermissions = [
            // Lease permissions
            'view_leases', 'view_any_lease', 'create_leases', 'update_leases',
            'delete_leases', 'print_leases', 'manage_leases', 'approve_leases',

            // Tenant permissions
            'view_tenants', 'manage_tenants', 'delete_tenants',

            // Landlord permissions
            'view_landlords', 'manage_landlords',

            // Property permissions
            'view_properties', 'manage_properties',

            // Unit permissions
            'view_units', 'manage_units',

            // Zone permissions
            'view_zones', 'manage_zones', 'view_zone_reports',

            // User permissions
            'view_users', 'manage_users',

            // Financial permissions
            'view_financials', 'edit_financials', 'manage_financials',

            // Document permissions
            'view_documents', 'manage_documents',

            // Report permissions
            'view_reports', 'export_reports', 'view_audit_logs',

            // Dashboard permissions
            'view_dashboard', 'view_company_dashboard', 'view_zone_dashboard',

            // Office/Operations permissions
            'distribute_copies', 'manage_office', 'manage_inventory',

            // Delegation permissions
            'act_as_zone_manager',
        ];

        foreach ($allPermissions as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
            );
        }

        // -----------------------------------------------------------------
        // 2. Ensure ALL Spatie roles exist, then assign permissions
        // -----------------------------------------------------------------
        // Create all 12 roles first (some may already exist, firstOrCreate handles that)
        $allRoleNames = [
            'super_admin',
            'system_admin',
            'property_manager',
            'asst_property_manager',
            'zone_manager',
            'senior_field_officer',
            'field_officer',
            'accountant',
            'auditor',
            'office_administrator',
            'office_admin_assistant',
            'office_assistant',
        ];

        foreach ($allRoleNames as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // Reset cache again after role creation
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Now assign permissions to the new roles
        $this->assignPermissionsToAccountant();
        $this->assignPermissionsToAuditor();
        $this->assignPermissionsToOfficeAdministrator();
        $this->assignPermissionsToOfficeAdminAssistant();
        $this->assignPermissionsToOfficeAssistant();
        $this->assignPermissionsToExistingRoles();

        // -----------------------------------------------------------------
        // 3. Hard delete user with name "FRANCIS NYAGA CHABARI" (id=2)
        // -----------------------------------------------------------------
        DB::table('model_has_roles')
            ->where('model_type', 'App\\Models\\User')
            ->where('model_id', 2)
            ->delete();

        DB::table('model_has_permissions')
            ->where('model_type', 'App\\Models\\User')
            ->where('model_id', 2)
            ->delete();

        DB::table('users')->where('id', 2)->delete();

        // -----------------------------------------------------------------
        // 4. Update specific users' roles in the users table `role` column
        // -----------------------------------------------------------------
        $roleUpdates = [
            // Super admins
            12 => 'super_admin',     // WILFRED WAMITHI WAMAE (was viewer)
            13 => 'super_admin',     // WILFRED KIMATHI MURUNGI (was admin)
            40 => 'super_admin',     // MARK CHABARI NYAGA (was admin)

            // System admin
            52 => 'admin',           // STANELY KIMANI MACHARIA (keep as admin)

            // Property manager
            8 => 'property_manager',       // GIBSON MWANGI GITAU (was manager)

            // Assistant property managers
            53 => 'asst_property_manager',  // MOSES KIBURI WAINAINA (was manager)
            72 => 'asst_property_manager',  // KENNETH MWANGI WAGACHA (was manager)

            // Accountant
            7 => 'accountant',             // REUBEN MUSYIMI MUTINDA (was admin)

            // Auditor
            6 => 'auditor',                // LUCY WANYAGA MURIITHI (was viewer)

            // Office administrator
            3 => 'office_administrator',   // LUCY WANJIRU KARANJA (was admin)

            // Office admin assistant
            73 => 'office_admin_assistant',  // LOISE NYAKIO MUIRURI (was agent)

            // Office assistants
            60 => 'office_assistant',        // ISABELLA NASWA WANJALA (was agent)
            4 => 'office_assistant',        // HANNAH WANGUI KIBUTU (was agent)

            // Senior field officers
            5 => 'senior_field_officer',    // GEORGE MUIGAI GITAU (was field_officer)
            11 => 'senior_field_officer',    // JANE WANGUI MWANGI (was field_officer)
            21 => 'senior_field_officer',    // PASCALINE WAMBUI MUIRURI (was field_officer)
            43 => 'senior_field_officer',    // ALLAN LUHAMBE SOGOMI (was field_officer)
            22 => 'senior_field_officer',    // BONIFACE KANGETHE MWANGI (was field_officer)
        ];

        foreach ($roleUpdates as $userId => $roleName) {
            DB::table('users')
                ->where('id', $userId)
                ->update(['role' => $roleName]);
        }

        // Update all remaining 'agent' and 'viewer' users to 'field_officer'
        DB::table('users')
            ->whereIn('role', ['agent', 'viewer'])
            ->update(['role' => 'field_officer']);

        // -----------------------------------------------------------------
        // 5. Sync Spatie model_has_roles for each user
        // -----------------------------------------------------------------
        $this->syncAllUserSpatieRoles();

        // -----------------------------------------------------------------
        // 6. Reset Spatie permission cache at the end
        // -----------------------------------------------------------------
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data changes (user deletions, role reassignments, permission changes)
        // cannot be reliably reversed. Manual intervention is required to
        // restore the previous state if a rollback is needed.
    }

    // =====================================================================
    // Role creation helpers
    // =====================================================================

    private function assignPermissionsToAccountant(): void
    {
        $role = Role::findByName('accountant', 'web');

        $this->assignPermissions($role, [
            'view_leases',
            'view_any_lease',
            'view_tenants',
            'view_landlords',
            'view_properties',
            'view_units',
            'view_financials',
            'edit_financials',
            'manage_financials',
            'view_reports',
            'export_reports',
            'view_dashboard',
            'view_company_dashboard',
        ]);
    }

    private function assignPermissionsToAuditor(): void
    {
        $role = Role::findByName('auditor', 'web');

        $this->assignPermissions($role, [
            'view_leases',
            'view_any_lease',
            'view_tenants',
            'view_landlords',
            'view_properties',
            'view_units',
            'view_zones',
            'view_zone_reports',
            'view_reports',
            'view_audit_logs',
            'export_reports',
            'view_financials',
            'view_dashboard',
            'view_company_dashboard',
            'view_zone_dashboard',
        ]);
    }

    private function assignPermissionsToOfficeAdministrator(): void
    {
        $role = Role::findByName('office_administrator', 'web');

        $this->assignPermissions($role, [
            'view_leases',
            'view_any_lease',
            'create_leases',
            'update_leases',
            'print_leases',
            'view_tenants',
            'manage_tenants',
            'view_landlords',
            'view_properties',
            'view_units',
            'distribute_copies',
            'manage_office',
            'view_documents',
            'manage_documents',
            'view_reports',
            'view_dashboard',
        ]);
    }

    private function assignPermissionsToOfficeAdminAssistant(): void
    {
        $role = Role::findByName('office_admin_assistant', 'web');

        $this->assignPermissions($role, [
            'view_leases',
            'print_leases',
            'view_tenants',
            'view_landlords',
            'view_properties',
            'view_units',
            'distribute_copies',
            'manage_office',
            'view_documents',
            'view_dashboard',
        ]);
    }

    private function assignPermissionsToOfficeAssistant(): void
    {
        $role = Role::findByName('office_assistant', 'web');

        $this->assignPermissions($role, [
            'view_leases',
            'view_tenants',
            'view_landlords',
            'view_properties',
            'view_units',
            'view_documents',
            'view_dashboard',
        ]);
    }

    /**
     * Assign permissions to the existing roles that were just created as Spatie roles.
     */
    private function assignPermissionsToExistingRoles(): void
    {
        // Get all available permissions
        $allPermissionNames = Permission::pluck('name')->toArray();

        // Super admin gets ALL permissions
        $superAdmin = Role::findByName('super_admin', 'web');
        $this->assignPermissions($superAdmin, $allPermissionNames);

        // System admin gets ALL permissions
        $systemAdmin = Role::findByName('system_admin', 'web');
        $this->assignPermissions($systemAdmin, $allPermissionNames);

        // Property manager
        $propertyManager = Role::findByName('property_manager', 'web');
        $this->assignPermissions($propertyManager, [
            'view_leases', 'view_any_lease', 'create_leases', 'update_leases', 'delete_leases',
            'print_leases', 'manage_leases', 'approve_leases',
            'view_tenants', 'manage_tenants', 'delete_tenants',
            'view_landlords', 'manage_landlords',
            'view_properties', 'manage_properties',
            'view_units', 'manage_units',
            'view_zones', 'manage_zones', 'view_zone_reports',
            'view_users', 'manage_users',
            'view_financials', 'edit_financials', 'manage_financials',
            'view_documents', 'manage_documents',
            'view_reports', 'export_reports',
            'view_dashboard', 'view_company_dashboard', 'view_zone_dashboard',
            'distribute_copies', 'manage_office',
        ]);

        // Assistant property manager
        $asstPm = Role::findByName('asst_property_manager', 'web');
        $this->assignPermissions($asstPm, [
            'view_leases', 'view_any_lease', 'create_leases', 'update_leases',
            'print_leases', 'manage_leases',
            'view_tenants', 'manage_tenants',
            'view_landlords', 'manage_landlords',
            'view_properties', 'manage_properties',
            'view_units', 'manage_units',
            'view_zones', 'view_zone_reports',
            'view_users',
            'view_financials',
            'view_documents', 'manage_documents',
            'view_reports', 'export_reports',
            'view_dashboard', 'view_company_dashboard', 'view_zone_dashboard',
            'distribute_copies', 'manage_office',
        ]);

        // Zone manager
        $zoneManager = Role::findByName('zone_manager', 'web');
        $this->assignPermissions($zoneManager, [
            'view_leases', 'view_any_lease', 'create_leases', 'update_leases',
            'print_leases',
            'view_tenants', 'manage_tenants',
            'view_landlords',
            'view_properties',
            'view_units',
            'view_zones', 'view_zone_reports',
            'view_documents', 'manage_documents',
            'view_reports',
            'view_dashboard', 'view_zone_dashboard',
        ]);

        // Senior field officer
        $seniorFo = Role::findByName('senior_field_officer', 'web');
        $this->assignPermissions($seniorFo, [
            'view_leases', 'view_any_lease', 'create_leases', 'update_leases',
            'view_tenants', 'manage_tenants',
            'view_landlords',
            'view_properties',
            'view_units',
            'view_documents',
            'view_reports',
            'view_dashboard',
            'act_as_zone_manager',
        ]);

        // Field officer
        $fieldOfficer = Role::findByName('field_officer', 'web');
        $this->assignPermissions($fieldOfficer, [
            'view_leases', 'view_any_lease', 'create_leases', 'update_leases',
            'view_tenants', 'manage_tenants',
            'view_landlords',
            'view_properties',
            'view_units',
            'view_documents',
            'view_dashboard',
        ]);
    }

    // =====================================================================
    // Helper methods
    // =====================================================================

    /**
     * Assign permissions to a role using direct pivot table insertion
     * to work around the custom 'permissions' JSON column on the roles table.
     */
    private function assignPermissions(Role $role, array $permissionNames): void
    {
        // Detach all existing permissions first
        $role->permissions()->detach();

        // Resolve permission IDs
        $permissionIds = Permission::whereIn('name', $permissionNames)
            ->pluck('id')
            ->toArray();

        // Attach via the pivot table directly
        $role->permissions()->attach($permissionIds);

        // Reset Spatie's permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Sync Spatie model_has_roles for every user based on their `role` column.
     *
     * Maps the users.role string column to the corresponding Spatie role name.
     */
    private function syncAllUserSpatieRoles(): void
    {
        // Map users.role column values to Spatie role names.
        // Most are 1:1, but 'admin' maps to 'system_admin' and
        // 'manager' maps to 'property_manager' in Spatie.
        $roleColumnToSpatieRole = [
            'super_admin' => 'super_admin',
            'admin' => 'system_admin',
            'property_manager' => 'property_manager',
            'asst_property_manager' => 'asst_property_manager',
            'zone_manager' => 'zone_manager',
            'senior_field_officer' => 'senior_field_officer',
            'field_officer' => 'field_officer',
            'accountant' => 'accountant',
            'auditor' => 'auditor',
            'office_administrator' => 'office_administrator',
            'office_admin_assistant' => 'office_admin_assistant',
            'office_assistant' => 'office_assistant',
        ];

        $users = DB::table('users')->whereNotNull('role')->get(['id', 'role']);

        foreach ($users as $userData) {
            $spatieRoleName = $roleColumnToSpatieRole[$userData->role] ?? null;

            if ($spatieRoleName === null) {
                // Skip users with unrecognized role values
                continue;
            }

            // Find the user model and sync their Spatie role
            $user = App\Models\User::find($userData->id);

            if ($user) {
                $user->syncRoles([$spatieRoleName]);
            }
        }
    }
};
