<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Lease permissions
            'view_leases',
            'view_any_lease',
            'create_leases',
            'update_leases',
            'delete_leases',
            'print_leases',
            'transition_lease_state',

            // Landlord lease permissions (for PM/APM only)
            'edit_landlord_leases',
            'upload_landlord_documents',

            // Approval permissions
            'approve_leases',
            'reject_leases',
            'request_approval',

            // Digital signing
            'send_digital_signing',
            'verify_otp',

            // Field officer permissions
            'checkout_leases',
            'checkin_leases',
            'record_delivery',
            'record_physical_signature',

            // Lawyer tracking
            'manage_lawyers',
            'send_to_lawyer',
            'receive_from_lawyer',

            // Copy distribution
            'distribute_copies',

            // Tenant/Landlord/Property management
            'view_tenants',
            'manage_tenants',
            'view_landlords',
            'manage_landlords',
            'view_properties',
            'manage_properties',
            'view_units',
            'manage_units',

            // Zone management
            'view_zones',
            'manage_zones',
            'view_zone_reports',

            // User management
            'view_users',
            'manage_users',
            'assign_roles',

            // Reports & Audit
            'view_reports',
            'view_audit_logs',
            'export_reports',

            // System settings
            'manage_settings',
            'manage_templates',

            // Dashboard access
            'view_dashboard',
            'view_company_dashboard',
            'view_zone_dashboard',
            'view_fo_dashboard',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles with permissions per SRS Section 10
        $this->createSuperUser();
        $this->createSystemAdmin();
        $this->createPropertyManager();
        $this->createAssistantPropertyManager();
        $this->createZoneManager();
        $this->createSeniorFieldOfficer();
        $this->createFieldOfficer();
        $this->createAudit();
    }

    private function createSuperUser(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $role->syncPermissions(Permission::all());
    }

    private function createSystemAdmin(): void
    {
        $role = Role::firstOrCreate(['name' => 'system_admin']);
        $role->givePermissionTo([
            'view_leases', 'view_any_lease', 'create_leases', 'update_leases',
            'print_leases', 'transition_lease_state',
            'approve_leases', 'reject_leases', 'request_approval',
            'send_digital_signing', 'verify_otp',
            'manage_lawyers', 'send_to_lawyer', 'receive_from_lawyer',
            'distribute_copies',
            'view_tenants', 'manage_tenants',
            'view_landlords', 'manage_landlords',
            'view_properties', 'manage_properties',
            'view_units', 'manage_units',
            'view_zones', 'manage_zones', 'view_zone_reports',
            'view_users', 'manage_users', 'assign_roles',
            'view_reports', 'view_audit_logs', 'export_reports',
            'manage_settings', 'manage_templates',
            'view_dashboard', 'view_company_dashboard', 'view_zone_dashboard',
        ]);
    }

    private function createPropertyManager(): void
    {
        $role = Role::firstOrCreate(['name' => 'property_manager']);
        $role->givePermissionTo([
            'view_leases', 'view_any_lease', 'create_leases', 'update_leases',
            'print_leases', 'transition_lease_state',
            'edit_landlord_leases', 'upload_landlord_documents',
            'approve_leases', 'reject_leases', 'request_approval',
            'send_digital_signing', 'verify_otp',
            'manage_lawyers', 'send_to_lawyer', 'receive_from_lawyer',
            'distribute_copies',
            'view_tenants', 'manage_tenants',
            'view_landlords', 'manage_landlords',
            'view_properties', 'manage_properties',
            'view_units', 'manage_units',
            'view_zones', 'view_zone_reports',
            'view_reports', 'export_reports',
            'manage_templates',
            'view_dashboard', 'view_company_dashboard', 'view_zone_dashboard',
        ]);
    }

    private function createAssistantPropertyManager(): void
    {
        $role = Role::firstOrCreate(['name' => 'asst_property_manager']);
        $role->givePermissionTo([
            'view_leases', 'view_any_lease', 'create_leases', 'update_leases',
            'print_leases', 'transition_lease_state',
            'edit_landlord_leases', 'upload_landlord_documents',
            'approve_leases', 'reject_leases', 'request_approval',
            'send_digital_signing', 'verify_otp',
            'send_to_lawyer', 'receive_from_lawyer',
            'distribute_copies',
            'view_tenants', 'manage_tenants',
            'view_landlords', 'manage_landlords',
            'view_properties', 'manage_properties',
            'view_units', 'manage_units',
            'view_zones', 'view_zone_reports',
            'view_reports', 'export_reports',
            'view_dashboard', 'view_company_dashboard', 'view_zone_dashboard',
        ]);
    }

    private function createZoneManager(): void
    {
        $role = Role::firstOrCreate(['name' => 'zone_manager']);
        $role->givePermissionTo([
            'view_leases', 'create_leases', 'update_leases',
            'print_leases', 'transition_lease_state',
            'request_approval',
            'send_digital_signing', 'verify_otp',
            'checkout_leases', 'checkin_leases',
            'distribute_copies',
            'view_tenants', 'manage_tenants',
            'view_landlords',
            'view_properties',
            'view_units',
            'view_zone_reports',
            'view_reports',
            'view_dashboard', 'view_zone_dashboard',
        ]);
    }

    private function createSeniorFieldOfficer(): void
    {
        $role = Role::firstOrCreate(['name' => 'senior_field_officer']);
        $role->givePermissionTo([
            'view_leases', 'create_leases', 'update_leases',
            'transition_lease_state',
            'request_approval',
            'send_digital_signing', 'verify_otp',
            'checkout_leases', 'checkin_leases',
            'record_delivery', 'record_physical_signature',
            'view_tenants', 'manage_tenants',
            'view_landlords',
            'view_properties',
            'view_units',
            'view_dashboard', 'view_fo_dashboard',
        ]);
    }

    private function createFieldOfficer(): void
    {
        $role = Role::firstOrCreate(['name' => 'field_officer']);
        $role->givePermissionTo([
            'view_leases',
            'transition_lease_state',
            'checkout_leases', 'checkin_leases',
            'record_delivery', 'record_physical_signature',
            'view_tenants',
            'view_landlords',
            'view_properties',
            'view_units',
            'view_dashboard', 'view_fo_dashboard',
        ]);
    }

    private function createAudit(): void
    {
        $role = Role::firstOrCreate(['name' => 'audit']);
        $role->givePermissionTo([
            'view_leases', 'view_any_lease',
            'view_tenants',
            'view_landlords',
            'view_properties',
            'view_units',
            'view_zones', 'view_zone_reports',
            'view_reports', 'view_audit_logs', 'export_reports',
            'view_dashboard', 'view_company_dashboard', 'view_zone_dashboard',
        ]);
    }
}
