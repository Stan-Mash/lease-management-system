<?php

namespace Database\Seeders;

use App\Models\Guarantor;
use App\Models\Landlord;
use App\Models\Lease;
use App\Models\Tenant;
use App\Models\User;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevelopmentSeeder extends Seeder
{
    /**
     * Seed the database with realistic test data for development/testing.
     *
     * This seeder creates:
     * - Admin user
     * - 10 tenants with realistic data
     * - 5 landlords
     * - 15 leases in various states
     * - Guarantors for some leases
     *
     * Usage: php artisan db:seed --class=DevelopmentSeeder
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting Development Seeder...');

        // Create admin user
        $this->command->info('Creating admin user...');
        $admin = User::firstOrCreate(
            ['email' => 'admin@chabrin.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );
        $this->command->info("✅ Admin created: {$admin->email} / password");

        // Create test tenants
        $this->command->info('Creating tenants...');
        $tenants = $this->createTenants();
        $this->command->info("✅ Created {$tenants->count()} tenants");

        // Create landlords
        $this->command->info('Creating landlords...');
        $landlords = $this->createLandlords();
        $this->command->info("✅ Created {$landlords->count()} landlords");

        // Create leases in various states
        $this->command->info('Creating leases...');
        $leases = $this->createLeases($tenants, $landlords);
        $this->command->info("✅ Created {$leases->count()} leases");

        // Create guarantors for some leases
        $this->command->info('Creating guarantors...');
        $guarantorsCount = $this->createGuarantors($leases);
        $this->command->info("✅ Created {$guarantorsCount} guarantors");

        $this->command->newLine();
        $this->command->info('🎉 Development seeder completed successfully!');
        $this->command->newLine();
        $this->command->table(
            ['Resource', 'Count', 'Notes'],
            [
                ['Admin Users', '1', 'admin@chabrin.com / password'],
                ['Tenants', $tenants->count(), 'Realistic names and phone numbers'],
                ['Landlords', $landlords->count(), 'Diverse property owners'],
                ['Leases', $leases->count(), 'Various states and types'],
                ['Guarantors', $guarantorsCount, 'Attached to selected leases'],
            ],
        );

        $this->command->newLine();
        $this->command->info('📊 Lease States Distribution:');
        $leaseStates = $leases->groupBy('workflow_state')->map->count();
        foreach ($leaseStates as $state => $count) {
            $this->command->info("  • {$state}: {$count}");
        }

        $this->command->newLine();
        $this->command->warn('⚠️  Next Steps:');
        $this->command->line('  1. Access admin panel: /admin');
        $this->command->line('  2. Login with: admin@chabrin.com / password');
        $this->command->line('  3. Test digital signing flow with a tenant');
        $this->command->line('  4. Run: php artisan test:signing-flow');
    }

    private function createTenants()
    {
        $tenantData = [
            ['name' => 'John Mwangi', 'id_number' => '12345678', 'phone' => '+254712345678', 'email' => 'john.mwangi@example.com'],
            ['name' => 'Sarah Njeri', 'id_number' => '23456789', 'phone' => '+254723456789', 'email' => 'sarah.njeri@example.com'],
            ['name' => 'David Kamau', 'id_number' => '34567890', 'phone' => '+254734567890', 'email' => 'david.kamau@example.com'],
            ['name' => 'Grace Wanjiku', 'id_number' => '45678901', 'phone' => '+254745678901', 'email' => 'grace.wanjiku@example.com'],
            ['name' => 'Peter Omondi', 'id_number' => '56789012', 'phone' => '+254756789012', 'email' => 'peter.omondi@example.com'],
            ['name' => 'Mary Akinyi', 'id_number' => '67890123', 'phone' => '+254767890123', 'email' => 'mary.akinyi@example.com'],
            ['name' => 'James Kipchoge', 'id_number' => '78901234', 'phone' => '+254778901234', 'email' => 'james.kipchoge@example.com'],
            ['name' => 'Lucy Wambui', 'id_number' => '89012345', 'phone' => '+254789012345', 'email' => 'lucy.wambui@example.com'],
            ['name' => 'Daniel Otieno', 'id_number' => '90123456', 'phone' => '+254790123456', 'email' => 'daniel.otieno@example.com'],
            ['name' => 'Ruth Nyambura', 'id_number' => '01234567', 'phone' => '+254701234567', 'email' => 'ruth.nyambura@example.com'],
        ];

        $tenants = collect();
        foreach ($tenantData as $data) {
            $tenant = Tenant::firstOrCreate(
                ['email' => $data['email']],
                $data,
            );
            $tenants->push($tenant);
        }

        return $tenants;
    }

    private function createLandlords()
    {
        $landlordData = [
            ['name' => 'Acme Properties Ltd', 'id_number' => 'P051234567', 'phone' => '+254711111111', 'email' => 'info@acmeproperties.co.ke'],
            ['name' => 'Skyline Estates', 'id_number' => 'P052345678', 'phone' => '+254722222222', 'email' => 'contact@skyline.co.ke'],
            ['name' => 'Urban Living Ltd', 'id_number' => 'P053456789', 'phone' => '+254733333333', 'email' => 'rentals@urbanliving.co.ke'],
            ['name' => 'Greenview Properties', 'id_number' => 'P054567890', 'phone' => '+254744444444', 'email' => 'info@greenview.co.ke'],
            ['name' => 'Prime Real Estate', 'id_number' => 'P055678901', 'phone' => '+254755555555', 'email' => 'leasing@primerealestate.co.ke'],
        ];

        $landlords = collect();
        foreach ($landlordData as $data) {
            $landlord = Landlord::firstOrCreate(
                ['email' => $data['email']],
                $data,
            );
            $landlords->push($landlord);
        }

        return $landlords;
    }

    private function createLeases($tenants, $landlords)
    {
        $zones = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        $leaseTypes = ['commercial', 'residential'];
        $states = [
            'draft' => 3,
            'pending_landlord' => 2,
            'approved' => 2,
            'sent_digital' => 3,
            'pending_otp' => 2,
            'tenant_signed' => 2,
            'active' => 1,
        ];

        $leases = collect();
        $leaseCount = 0;

        foreach ($states as $state => $count) {
            for ($i = 0; $i < $count; $i++) {
                $tenant = $tenants->random();
                $landlord = $landlords->random();
                $leaseType = $leaseTypes[array_rand($leaseTypes)];
                $zone = $zones[array_rand($zones)];

                $startDate = now()->addDays(rand(1, 30));
                $endDate = $startDate->copy()->addYear();

                $monthlyRent = $leaseType === 'commercial'
                    ? rand(15000, 50000)
                    : rand(8000, 25000);

                try {
                    $lease = Lease::create([
                        'tenant_id' => $tenant->id,
                        'landlord_id' => $landlord->id,
                        'lease_type' => $leaseType,
                        'zone' => $zone,
                        'lease_source' => rand(0, 1) ? 'chabrin' : 'landlord',
                        'workflow_state' => $state,
                        'property_address' => $this->generateAddress($zone),
                        'monthly_rent' => $monthlyRent,
                        'security_deposit' => $monthlyRent * 2,
                        'currency' => 'KES',
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'payment_day' => rand(1, 28),
                        'payment_method' => ['bank_transfer', 'mpesa', 'cash'][rand(0, 2)],
                        'special_terms' => $this->generateSpecialTerms(),
                        'created_by' => 1,
                    ]);

                    $leases->push($lease);
                    $leaseCount++;
                } catch (Exception $e) {
                    // Skip if reference generation fails (unlikely with our implementation)
                    $this->command->warn("Skipped lease creation: {$e->getMessage()}");
                }
            }
        }

        return $leases;
    }

    private function createGuarantors($leases)
    {
        $guarantorCount = 0;

        // Add guarantors to ~40% of leases
        $leasesNeedingGuarantors = $leases->random(min(6, $leases->count()));

        foreach ($leasesNeedingGuarantors as $lease) {
            $numGuarantors = rand(1, 2);

            for ($i = 0; $i < $numGuarantors; $i++) {
                Guarantor::create([
                    'lease_id' => $lease->id,
                    'name' => $this->generateName(),
                    'id_number' => rand(10000000, 99999999),
                    'phone' => '+2547' . rand(10000000, 99999999),
                    'email' => strtolower(str_replace(' ', '.', $this->generateName())) . '@example.com',
                    'relationship' => ['parent', 'sibling', 'spouse', 'employer', 'friend'][rand(0, 4)],
                    'guarantee_amount' => $lease->monthly_rent * rand(1, 3),
                    'signed' => rand(0, 1),
                    'signed_at' => rand(0, 1) ? now()->subDays(rand(1, 30)) : null,
                ]);

                $guarantorCount++;
            }
        }

        return $guarantorCount;
    }

    private function generateAddress($zone)
    {
        $streets = ['Kenyatta Avenue', 'Moi Avenue', 'Uhuru Highway', 'Ngong Road', 'Waiyaki Way', 'Thika Road'];
        $buildings = ['Tower A', 'Building B', 'Block C', 'Suite D', 'Plaza E', 'Complex F'];

        return rand(1, 50) . ' ' . $streets[array_rand($streets)] . ', ' .
               $buildings[array_rand($buildings)] . ', Zone ' . $zone;
    }

    private function generateSpecialTerms()
    {
        $terms = [
            'Utilities included in rent',
            'No pets allowed',
            'Parking space included',
            'First month rent-free',
            'Early termination clause: 2 months notice required',
            'Rent review after 12 months',
        ];

        return $terms[array_rand($terms)];
    }

    private function generateName()
    {
        $firstNames = ['Michael', 'Jane', 'Robert', 'Patricia', 'William', 'Elizabeth', 'Charles', 'Susan'];
        $lastNames = ['Kariuki', 'Mutua', 'Wekesa', 'Chebet', 'Ngugi', 'Achieng', 'Kimani', 'Muthoni'];

        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }
}
