<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get roles from config file
        $configRoles = Config::get('roles.roles', []);

        $order = 0;
        foreach ($configRoles as $key => $roleData) {
            // Convert permissions array format if needed
            $permissions = [];
            if (isset($roleData['permissions'])) {
                foreach ($roleData['permissions'] as $permission) {
                    $permissions[] = ['permission' => $permission];
                }
            }

            Role::updateOrCreate(
                ['key' => $key],
                [
                    'name' => $roleData['name'] ?? ucfirst($key),
                    'description' => $roleData['description'] ?? '',
                    'color' => $roleData['color'] ?? 'gray',
                    'permissions' => $permissions,
                    'sort_order' => $order++,
                    'is_system' => in_array($key, ['super_admin', 'admin']), // Mark super_admin and admin as system roles
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Roles seeded successfully from config!');
        $this->command->info('You can now manage roles at /admin/roles');
    }
}
