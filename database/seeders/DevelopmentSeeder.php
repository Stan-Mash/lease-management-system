<?php

namespace Database\Seeders;

use App\Models\Guarantor;
use App\Models\Landlord;
use App\Models\Lease;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DevelopmentSeeder extends Seeder
{
    /**
     * Seed the database with realistic test data for development/testing.
     *
     * This seeder creates:
     * - Admin user with super_admin role
     * - 10 tenants with realistic data
     * - 5 landlords
     * - 5 properties with units
     * - 15 leases in various states
     * - Guarantors for some leases
     *
     * Usage: php artisan db:seed --class=DevelopmentSeeder
     */
    public function run(): void
    {
        $this->command->info('Starting Development Seeder...');

        // Create admin user
        $this->command->info('Creating admin user...');
        $admin = User::firstOrCreate(
            ['email' => 'admin@chabrin.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'super_admin',
            ],
        );

        // Ensure the role column is set (in case user already existed)
        if ($admin->role !== 'super_admin') {
            $admin->update(['role' => 'super_admin']);
        }

        // Assign super_admin role via Spatie
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole && ! $admin->hasRole('super_admin')) {
            $admin->assignRole('super_admin');
        }

        $this->command->info("Admin created: {$admin->email} / password (super_admin)");

        // Create test tenants
        $this->command->info('Creating tenants...');
        $tenants = $this->createTenants();
        $this->command->info("Created {$tenants->count()} tenants");

        // Create landlords
        $this->command->info('Creating landlords...');
        $landlords = $this->createLandlords();
        $this->command->info("Created {$landlords->count()} landlords");

        // Create properties and units
        $this->command->info('Creating properties and units...');
        $properties = $this->createProperties($landlords);
        $this->command->info("Created {$properties->count()} properties with units");

        // Create leases in various states
        $this->command->info('Creating leases...');
        $leases = $this->createLeases($tenants, $landlords, $properties);
        $this->command->info("Created {$leases->count()} leases");

        // Create guarantors for some leases
        $this->command->info('Creating guarantors...');
        $guarantorsCount = $this->createGuarantors($leases);
        $this->command->info("Created {$guarantorsCount} guarantors");

        $this->command->newLine();
        $this->command->info('Development seeder completed successfully!');
        $this->command->newLine();
        $this->command->table(
            ['Resource', 'Count', 'Notes'],
            [
                ['Admin Users', '1', 'admin@chabrin.com / password'],
                ['Tenants', $tenants->count(), 'Realistic names and phone numbers'],
                ['Landlords', $landlords->count(), 'Diverse property owners'],
                ['Properties', $properties->count(), 'With multiple units each'],
                ['Leases', $leases->count(), 'Various states and types'],
                ['Guarantors', $guarantorsCount, 'Attached to selected leases'],
            ],
        );

        $this->command->newLine();
        $this->command->info('Lease States Distribution:');
        $leaseStates = $leases->groupBy('workflow_state')->map->count();
        foreach ($leaseStates as $state => $count) {
            $this->command->info("  {$state}: {$count}");
        }

        $this->command->newLine();
        $this->command->warn('Next Steps:');
        $this->command->line('  1. Access admin panel: /admin');
        $this->command->line('  2. Login with: admin@chabrin.com / password');
        $this->command->line('  3. Test digital signing flow with a tenant');
    }

    private function createTenants()
    {
        // Schema: full_name, id_number, phone_number, email, notification_preference,
        //         kra_pin, occupation, employer_name, next_of_kin_name, next_of_kin_phone
        $tenantData = [
            ['full_name' => 'John Mwangi', 'id_number' => '12345678', 'phone_number' => '+254712345678', 'email' => 'john.mwangi@example.com', 'occupation' => 'Teacher', 'next_of_kin_name' => 'Mary Mwangi', 'next_of_kin_phone' => '+254700111111'],
            ['full_name' => 'Sarah Njeri', 'id_number' => '23456789', 'phone_number' => '+254723456789', 'email' => 'sarah.njeri@example.com', 'occupation' => 'Nurse', 'next_of_kin_name' => 'James Njeri', 'next_of_kin_phone' => '+254700222222'],
            ['full_name' => 'David Kamau', 'id_number' => '34567890', 'phone_number' => '+254734567890', 'email' => 'david.kamau@example.com', 'occupation' => 'Engineer', 'next_of_kin_name' => 'Grace Kamau', 'next_of_kin_phone' => '+254700333333'],
            ['full_name' => 'Grace Wanjiku', 'id_number' => '45678901', 'phone_number' => '+254745678901', 'email' => 'grace.wanjiku@example.com', 'occupation' => 'Accountant', 'next_of_kin_name' => 'Peter Wanjiku', 'next_of_kin_phone' => '+254700444444'],
            ['full_name' => 'Peter Omondi', 'id_number' => '56789012', 'phone_number' => '+254756789012', 'email' => 'peter.omondi@example.com', 'occupation' => 'Lawyer', 'next_of_kin_name' => 'Lucy Omondi', 'next_of_kin_phone' => '+254700555555'],
            ['full_name' => 'Mary Akinyi', 'id_number' => '67890123', 'phone_number' => '+254767890123', 'email' => 'mary.akinyi@example.com', 'occupation' => 'Business Owner', 'next_of_kin_name' => 'Daniel Akinyi', 'next_of_kin_phone' => '+254700666666'],
            ['full_name' => 'James Kipchoge', 'id_number' => '78901234', 'phone_number' => '+254778901234', 'email' => 'james.kipchoge@example.com', 'occupation' => 'Doctor', 'next_of_kin_name' => 'Ruth Kipchoge', 'next_of_kin_phone' => '+254700777777'],
            ['full_name' => 'Lucy Wambui', 'id_number' => '89012345', 'phone_number' => '+254789012345', 'email' => 'lucy.wambui@example.com', 'occupation' => 'Pharmacist', 'next_of_kin_name' => 'Michael Wambui', 'next_of_kin_phone' => '+254700888888'],
            ['full_name' => 'Daniel Otieno', 'id_number' => '90123456', 'phone_number' => '+254790123456', 'email' => 'daniel.otieno@example.com', 'occupation' => 'Architect', 'next_of_kin_name' => 'Sarah Otieno', 'next_of_kin_phone' => '+254700999999'],
            ['full_name' => 'Ruth Nyambura', 'id_number' => '01234567', 'phone_number' => '+254701234567', 'email' => 'ruth.nyambura@example.com', 'occupation' => 'Lecturer', 'next_of_kin_name' => 'David Nyambura', 'next_of_kin_phone' => '+254700101010'],
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
        // Schema: landlord_code, name, phone, email, id_number, kra_pin, bank_name, account_number, is_active
        $landlordData = [
            ['name' => 'Acme Properties Ltd', 'id_number' => 'P051234567', 'phone' => '+254711111111', 'email' => 'info@acmeproperties.co.ke', 'kra_pin' => 'A001234567Z', 'bank_name' => 'KCB Bank', 'account_number' => '1234567890', 'is_active' => true],
            ['name' => 'Skyline Estates', 'id_number' => 'P052345678', 'phone' => '+254722222222', 'email' => 'contact@skyline.co.ke', 'kra_pin' => 'A002345678Y', 'bank_name' => 'Equity Bank', 'account_number' => '2345678901', 'is_active' => true],
            ['name' => 'Urban Living Ltd', 'id_number' => 'P053456789', 'phone' => '+254733333333', 'email' => 'rentals@urbanliving.co.ke', 'kra_pin' => 'A003456789X', 'bank_name' => 'Cooperative Bank', 'account_number' => '3456789012', 'is_active' => true],
            ['name' => 'Greenview Properties', 'id_number' => 'P054567890', 'phone' => '+254744444444', 'email' => 'info@greenview.co.ke', 'kra_pin' => 'A004567890W', 'bank_name' => 'NCBA Bank', 'account_number' => '4567890123', 'is_active' => true],
            ['name' => 'Prime Real Estate', 'id_number' => 'P055678901', 'phone' => '+254755555555', 'email' => 'leasing@primerealestate.co.ke', 'kra_pin' => 'A005678901V', 'bank_name' => 'Stanbic Bank', 'account_number' => '5678901234', 'is_active' => true],
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

    private function createProperties($landlords)
    {
        // Schema: name, property_code, zone, location, landlord_id, management_commission
        // Units schema: property_id, unit_number, type, market_rent, deposit_required, status
        $propertyData = [
            [
                'name' => 'Sunrise Apartments', 'property_code' => 'PROP-001', 'zone' => 'A', 'location' => 'Ngong Road, Nairobi', 'management_commission' => 10.00,
                'units' => [
                    ['unit_number' => 'A101', 'type' => 'bedsitter', 'market_rent' => 12000, 'deposit_required' => 24000, 'status' => 'VACANT'],
                    ['unit_number' => 'A102', 'type' => '1br', 'market_rent' => 18000, 'deposit_required' => 36000, 'status' => 'VACANT'],
                    ['unit_number' => 'A103', 'type' => '2br', 'market_rent' => 25000, 'deposit_required' => 50000, 'status' => 'VACANT'],
                    ['unit_number' => 'A201', 'type' => 'bedsitter', 'market_rent' => 12000, 'deposit_required' => 24000, 'status' => 'VACANT'],
                    ['unit_number' => 'A202', 'type' => '1br', 'market_rent' => 18000, 'deposit_required' => 36000, 'status' => 'VACANT'],
                ],
            ],
            [
                'name' => 'Westgate Plaza', 'property_code' => 'PROP-002', 'zone' => 'B', 'location' => 'Waiyaki Way, Westlands', 'management_commission' => 8.00,
                'units' => [
                    ['unit_number' => 'B101', 'type' => 'commercial', 'market_rent' => 35000, 'deposit_required' => 70000, 'status' => 'VACANT'],
                    ['unit_number' => 'B102', 'type' => 'commercial', 'market_rent' => 45000, 'deposit_required' => 90000, 'status' => 'VACANT'],
                    ['unit_number' => 'B201', 'type' => 'commercial', 'market_rent' => 30000, 'deposit_required' => 60000, 'status' => 'VACANT'],
                ],
            ],
            [
                'name' => 'Green Valley Flats', 'property_code' => 'PROP-003', 'zone' => 'C', 'location' => 'Thika Road, Kasarani', 'management_commission' => 10.00,
                'units' => [
                    ['unit_number' => 'C101', 'type' => 'single_room', 'market_rent' => 8000, 'deposit_required' => 16000, 'status' => 'VACANT'],
                    ['unit_number' => 'C102', 'type' => 'single_room', 'market_rent' => 8000, 'deposit_required' => 16000, 'status' => 'VACANT'],
                    ['unit_number' => 'C103', 'type' => 'bedsitter', 'market_rent' => 10000, 'deposit_required' => 20000, 'status' => 'VACANT'],
                    ['unit_number' => 'C201', 'type' => '1br', 'market_rent' => 15000, 'deposit_required' => 30000, 'status' => 'VACANT'],
                ],
            ],
            [
                'name' => 'Moi Avenue Offices', 'property_code' => 'PROP-004', 'zone' => 'D', 'location' => 'Moi Avenue, CBD', 'management_commission' => 12.00,
                'units' => [
                    ['unit_number' => 'D301', 'type' => 'commercial', 'market_rent' => 50000, 'deposit_required' => 100000, 'status' => 'VACANT'],
                    ['unit_number' => 'D302', 'type' => 'commercial', 'market_rent' => 40000, 'deposit_required' => 80000, 'status' => 'VACANT'],
                ],
            ],
            [
                'name' => 'Uhuru Heights', 'property_code' => 'PROP-005', 'zone' => 'E', 'location' => 'Uhuru Highway, South B', 'management_commission' => 10.00,
                'units' => [
                    ['unit_number' => 'E101', 'type' => '2br', 'market_rent' => 22000, 'deposit_required' => 44000, 'status' => 'VACANT'],
                    ['unit_number' => 'E102', 'type' => '3br', 'market_rent' => 30000, 'deposit_required' => 60000, 'status' => 'VACANT'],
                    ['unit_number' => 'E201', 'type' => '1br', 'market_rent' => 16000, 'deposit_required' => 32000, 'status' => 'VACANT'],
                    ['unit_number' => 'E202', 'type' => '2br', 'market_rent' => 22000, 'deposit_required' => 44000, 'status' => 'VACANT'],
                ],
            ],
        ];

        $properties = collect();
        foreach ($propertyData as $pData) {
            $units = $pData['units'];
            unset($pData['units']);

            $landlord = $landlords->random();
            $pData['landlord_id'] = $landlord->id;

            $property = Property::firstOrCreate(
                ['property_code' => $pData['property_code']],
                $pData,
            );

            foreach ($units as $unitData) {
                Unit::firstOrCreate(
                    ['property_id' => $property->id, 'unit_number' => $unitData['unit_number']],
                    $unitData,
                );
            }

            $properties->push($property->load('units'));
        }

        return $properties;
    }

    private function createLeases($tenants, $landlords, $properties)
    {
        // Schema: source, lease_type, workflow_state, tenant_id, unit_id, property_id,
        //         landlord_id, zone, monthly_rent, deposit_amount, start_date, end_date, created_by
        $states = [
            'draft' => 3,
            'pending_landlord_approval' => 2,
            'approved' => 2,
            'sent_digital' => 3,
            'pending_otp' => 2,
            'tenant_signed' => 2,
            'active' => 1,
        ];

        $leases = collect();
        $allUnits = $properties->flatMap->units;
        $unitIndex = 0;
        $leaseCounter = 1;

        foreach ($states as $state => $count) {
            for ($i = 0; $i < $count; $i++) {
                $tenant = $tenants->random();
                $landlord = $landlords->random();

                // Get a unit (cycle through available units)
                $unit = $allUnits[$unitIndex % $allUnits->count()];
                $unitIndex++;
                $property = $properties->firstWhere('id', $unit->property_id);

                $leaseType = in_array($unit->type, ['commercial']) ? 'commercial' : 'residential';

                $startDate = now()->subDays(rand(0, 90));
                $endDate = $startDate->copy()->addYear();

                // Generate unique reference number
                $refNumber = sprintf('REF-%s-%04d', now()->format('Y'), $leaseCounter++);

                try {
                    $lease = Lease::create([
                        'reference_number' => $refNumber,
                        'tenant_id' => $tenant->id,
                        'unit_id' => $unit->id,
                        'property_id' => $property->id,
                        'landlord_id' => $landlord->id,
                        'lease_type' => $leaseType,
                        'source' => rand(0, 1) ? 'chabrin' : 'landlord',
                        'zone' => $property->zone,
                        'workflow_state' => $state,
                        'monthly_rent' => $unit->market_rent,
                        'deposit_amount' => $unit->deposit_required,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'created_by' => 1,
                    ]);

                    $leases->push($lease);
                } catch (Exception $e) {
                    $this->command->warn("Skipped lease creation: {$e->getMessage()}");
                }
            }
        }

        return $leases;
    }

    private function createGuarantors($leases)
    {
        $guarantorCount = 0;

        if ($leases->isEmpty()) {
            return $guarantorCount;
        }

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

    private function generateName()
    {
        $firstNames = ['Michael', 'Jane', 'Robert', 'Patricia', 'William', 'Elizabeth', 'Charles', 'Susan'];
        $lastNames = ['Kariuki', 'Mutua', 'Wekesa', 'Chebet', 'Ngugi', 'Achieng', 'Kimani', 'Muthoni'];

        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }
}
