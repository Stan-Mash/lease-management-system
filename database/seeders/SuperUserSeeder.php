<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperUserSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure super_admin role exists
        $role = Role::firstOrCreate(['name' => 'super_admin']);

        // Remove any default admin user
        User::where('email', 'admin@admin.com')
            ->orWhere('email', 'admin@example.com')
            ->orWhere('username', 'admin')
            ->delete();

        // Stanely Macharia - Super Super Admin
        $stanely = User::updateOrCreate(
            ['email' => 'stanely.macharia@chabrinagencies.co.ke'],
            [
                'name' => 'Stanely Macharia',
                'username' => 'stanely.macharia',
                'password' => Hash::make('Chabrin@2026!'),
                'block' => false,
                'sendEmail' => true,
                'registerDate' => now(),
                'activation' => '',
                'resetCount' => 0,
                'requireReset' => false,
            ],
        );
        $stanely->syncRoles(['super_admin']);

        // Wilfred Kimathi Murungi - Super Admin
        $kimathi = User::updateOrCreate(
            ['email' => 'kimathiw@chabrinagencies.co.ke'],
            [
                'name' => 'Wilfred Kimathi Murungi',
                'username' => 'kimathiw',
                'password' => Hash::make('Chabrin@2026!'),
                'block' => false,
                'sendEmail' => true,
                'registerDate' => now(),
                'activation' => '',
                'resetCount' => 0,
                'requireReset' => false,
            ],
        );
        $kimathi->syncRoles(['super_admin']);

        // Mark Nyaga Chabari - Super Admin
        $mark = User::updateOrCreate(
            ['email' => 'mark.nyaga@chabrinagencies.co.ke'],
            [
                'name' => 'Mark Nyaga Chabari',
                'username' => 'mark.nyaga',
                'password' => Hash::make('Chabrin@2026!'),
                'block' => false,
                'sendEmail' => true,
                'registerDate' => now(),
                'activation' => '',
                'resetCount' => 0,
                'requireReset' => false,
            ],
        );
        $mark->syncRoles(['super_admin']);

        $this->command->info('Default admin user removed.');
        $this->command->info('Super users created:');
        $this->command->info('  Stanely  | stanely.macharia@chabrinagencies.co.ke | password: Chabrin@2026!');
        $this->command->info('  Kimathi  | kimathiw@chabrinagencies.co.ke         | password: Chabrin@2026!');
        $this->command->info('  Mark     | mark.nyaga@chabrinagencies.co.ke       | password: Chabrin@2026!');
    }
}
