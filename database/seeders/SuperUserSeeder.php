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

        // Stanley Mash - Super Super Admin
        $stanley = User::updateOrCreate(
            ['email' => 'stanley.mash@chabrinagencies.co.ke'],
            [
                'name' => 'Stanley Mash',
                'username' => 'stanley.mash',
                'password' => Hash::make('Chabrin@2026!'),
                'block' => false,
                'sendEmail' => true,
                'registerDate' => now(),
                'activation' => '',
                'resetCount' => 0,
                'requireReset' => false,
            ]
        );
        $stanley->syncRoles(['super_admin']);

        // Kimathi - Super Admin
        $kimathi = User::updateOrCreate(
            ['email' => 'kimathiw@chabrinagencies.co.ke'],
            [
                'name' => 'Kimathi',
                'username' => 'kimathiw',
                'password' => Hash::make('Chabrin@2026!'),
                'block' => false,
                'sendEmail' => true,
                'registerDate' => now(),
                'activation' => '',
                'resetCount' => 0,
                'requireReset' => false,
            ]
        );
        $kimathi->syncRoles(['super_admin']);

        // Mark Nyaga - Super Admin
        $mark = User::updateOrCreate(
            ['email' => 'mark.nyaga@chabrinagencies.co.ke'],
            [
                'name' => 'Mark Nyaga',
                'username' => 'mark.nyaga',
                'password' => Hash::make('Chabrin@2026!'),
                'block' => false,
                'sendEmail' => true,
                'registerDate' => now(),
                'activation' => '',
                'resetCount' => 0,
                'requireReset' => false,
            ]
        );
        $mark->syncRoles(['super_admin']);

        $this->command->info('Default admin user removed.');
        $this->command->info('Super users created:');
        $this->command->info('  Stanley  | stanley.mash@chabrinagencies.co.ke | password: Chabrin@2026!');
        $this->command->info('  Kimathi  | kimathiw@chabrinagencies.co.ke     | password: Chabrin@2026!');
        $this->command->info('  Mark     | mark.nyaga@chabrinagencies.co.ke   | password: Chabrin@2026!');
    }
}
