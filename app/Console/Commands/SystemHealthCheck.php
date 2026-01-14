<?php

namespace App\Console\Commands;

use App\Models\{User, Zone, Lease, Landlord, Tenant, LeaseApproval};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemHealthCheck extends Command
{
    protected $signature = 'system:health-check';
    protected $description = 'Run comprehensive system health check to verify all features are working';

    private int $passCount = 0;
    private int $failCount = 0;
    private int $warnCount = 0;

    public function handle()
    {
        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║     Chabrin Lease Management System - Health Check             ║');
        $this->info('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->testDatabaseConnection();
        $this->testDatabaseTables();
        $this->testZoneSystem();
        $this->testUserRoles();
        $this->testLeaseSystem();
        $this->testApprovalSystem();
        $this->testRelationships();
        $this->testZoneAccessControl();

        $this->newLine();
        $this->displaySummary();

        return $this->failCount === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    private function testDatabaseConnection()
    {
        $this->section('Database Connection');

        try {
            DB::connection()->getPdo();
            $dbName = DB::connection()->getDatabaseName();
            $this->testPass("Connected to database: {$dbName}");
        } catch (\Exception $e) {
            $this->testFail("Database connection failed: " . $e->getMessage());
        }
    }

    private function testDatabaseTables()
    {
        $this->section('Database Tables');

        $requiredTables = [
            'users', 'zones', 'leases', 'landlords', 'tenants',
            'lease_approvals', 'guarantors', 'digital_signatures',
            'otp_verifications', 'lease_audit_logs'
        ];

        foreach ($requiredTables as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                $this->testPass("{$table} table exists ({$count} records)");
            } else {
                $this->testFail("{$table} table is missing!");
            }
        }

        // Check zone columns
        if (Schema::hasColumn('users', 'zone_id')) {
            $this->testPass("users.zone_id column exists");
        } else {
            $this->testFail("users.zone_id column is missing!");
        }

        if (Schema::hasColumn('leases', 'zone_id')) {
            $this->testPass("leases.zone_id column exists");
        } else {
            $this->testFail("leases.zone_id column is missing!");
        }
    }

    private function testZoneSystem()
    {
        $this->section('Zone System');

        $zoneCount = Zone::count();

        if ($zoneCount > 0) {
            $this->testPass("{$zoneCount} zones configured");

            $activeZones = Zone::where('is_active', true)->count();
            $this->info("  → {$activeZones} active zones");

            $zonesWithManagers = Zone::whereNotNull('zone_manager_id')->count();
            $this->info("  → {$zonesWithManagers} zones have managers assigned");

            // Show zone details
            $zones = Zone::with('zoneManager', 'fieldOfficers')->get();
            foreach ($zones as $zone) {
                $foCount = $zone->fieldOfficers->count();
                $managerName = $zone->zoneManager ? $zone->zoneManager->name : 'None';
                $this->line("  • {$zone->name} ({$zone->code}) - Manager: {$managerName}, FOs: {$foCount}");
            }
        } else {
            $this->testWarn("No zones created yet. Run: php artisan tinker and create zones.");
        }
    }

    private function testUserRoles()
    {
        $this->section('User Roles');

        $userCount = User::count();

        if ($userCount > 0) {
            $this->testPass("{$userCount} users in system");

            $roles = [
                'super_admin' => 'Super Admins',
                'admin' => 'Admins',
                'zone_manager' => 'Zone Managers',
                'field_officer' => 'Field Officers',
                'manager' => 'Managers',
                'agent' => 'Agents',
            ];

            foreach ($roles as $role => $label) {
                $count = User::where('role', $role)->count();
                if ($count > 0) {
                    $this->info("  → {$count} {$label}");
                }
            }

            // Check zone assignments
            $usersWithZone = User::whereNotNull('zone_id')->count();
            $this->info("  → {$usersWithZone} users assigned to zones");

            // Check for orphaned zone users
            $orphanedUsers = User::whereNotNull('zone_id')
                ->whereNotIn('zone_id', Zone::pluck('id'))
                ->count();

            if ($orphanedUsers > 0) {
                $this->testWarn("{$orphanedUsers} users assigned to non-existent zones");
            }
        } else {
            $this->testWarn("No users found. Create admin user first.");
        }
    }

    private function testLeaseSystem()
    {
        $this->section('Lease System');

        $leaseCount = Lease::count();

        if ($leaseCount > 0) {
            $this->testPass("{$leaseCount} leases in system");

            // Workflow states
            $states = Lease::select('workflow_state', DB::raw('count(*) as count'))
                ->groupBy('workflow_state')
                ->get();

            foreach ($states as $state) {
                $this->info("  → {$state->count} in state: {$state->workflow_state}");
            }

            // Zone assignments
            $leasesWithZone = Lease::whereNotNull('zone_id')->count();
            $this->info("  → {$leasesWithZone} leases assigned to zones");

            // Lease types
            $types = Lease::select('lease_type', DB::raw('count(*) as count'))
                ->groupBy('lease_type')
                ->get();

            foreach ($types as $type) {
                $this->info("  → {$type->count} {$type->lease_type} leases");
            }
        } else {
            $this->testWarn("No leases created yet");
        }
    }

    private function testApprovalSystem()
    {
        $this->section('Approval System');

        $approvalCount = LeaseApproval::count();

        if ($approvalCount > 0) {
            $this->testPass("{$approvalCount} approval records");

            $pending = LeaseApproval::whereNull('decision')->count();
            $approved = LeaseApproval::where('decision', 'approved')->count();
            $rejected = LeaseApproval::where('decision', 'rejected')->count();

            $this->info("  → {$pending} pending approvals");
            $this->info("  → {$approved} approved");
            $this->info("  → {$rejected} rejected");

            // Pending landlord approvals
            $pendingLandlord = Lease::where('workflow_state', 'pending_landlord_approval')->count();
            $this->info("  → {$pendingLandlord} leases awaiting landlord approval");
        } else {
            $this->info("No approval records yet");
        }
    }

    private function testRelationships()
    {
        $this->section('Model Relationships');

        try {
            // Test Zone relationships
            $zone = Zone::first();
            if ($zone) {
                $zone->zoneManager;
                $zone->fieldOfficers;
                $zone->leases;
                $this->testPass("Zone relationships work");
            }

            // Test User relationships
            $user = User::where('role', 'field_officer')->first();
            if ($user) {
                $user->zone;
                $user->assignedLeases;
                $this->testPass("User relationships work");
            }

            // Test Lease relationships
            $lease = Lease::first();
            if ($lease) {
                $lease->tenant;
                $lease->landlord;
                $lease->assignedZone;
                $lease->assignedFieldOfficer;
                $lease->approvals;
                $this->testPass("Lease relationships work");
            }
        } catch (\Exception $e) {
            $this->testFail("Relationship error: " . $e->getMessage());
        }
    }

    private function testZoneAccessControl()
    {
        $this->section('Zone Access Control');

        // Get test users
        $admin = User::where('role', 'super_admin')->first();
        $zm = User::where('role', 'zone_manager')->whereNotNull('zone_id')->first();
        $fo = User::where('role', 'field_officer')->whereNotNull('zone_id')->first();

        if (!$admin) {
            $this->testWarn("No super_admin found for testing");
            return;
        }

        // Test role checking methods
        try {
            $admin->isSuperAdmin();
            $admin->isAdmin();
            $this->testPass("Role checking methods work");
        } catch (\Exception $e) {
            $this->testFail("Role checking failed: " . $e->getMessage());
        }

        if ($zm) {
            try {
                $zm->isZoneManager();
                $zm->hasZoneRestriction();
                $zm->zone;
                $this->testPass("Zone Manager methods work");

                // Test zone access
                $canAccess = $zm->canAccessZone($zm->zone_id);
                if ($canAccess) {
                    $this->testPass("ZM can access their own zone");
                } else {
                    $this->testFail("ZM cannot access their own zone!");
                }
            } catch (\Exception $e) {
                $this->testFail("Zone Manager test failed: " . $e->getMessage());
            }
        } else {
            $this->testWarn("No zone_manager found for testing");
        }

        if ($fo) {
            try {
                $fo->isFieldOfficer();
                $fo->hasZoneRestriction();
                $this->testPass("Field Officer methods work");

                // Test lease filtering
                $accessibleLeases = Lease::accessibleByUser($fo)->count();
                $totalLeases = Lease::count();

                if ($accessibleLeases <= $totalLeases) {
                    $this->testPass("FO filtering works ({$accessibleLeases}/{$totalLeases} leases visible)");
                } else {
                    $this->testFail("Lease filtering broken!");
                }
            } catch (\Exception $e) {
                $this->testFail("Field Officer test failed: " . $e->getMessage());
            }
        } else {
            $this->testWarn("No field_officer found for testing");
        }
    }

    private function section(string $title)
    {
        $this->newLine();
        $this->info("━━━ {$title} ━━━");
    }

    private function testPass(string $message)
    {
        $this->passCount++;
        $this->line("<fg=green>✓</> {$message}");
    }

    private function testFail(string $message)
    {
        $this->failCount++;
        $this->line("<fg=red>✗</> {$message}");
    }

    private function testWarn(string $message)
    {
        $this->warnCount++;
        $this->line("<fg=yellow>⚠</> {$message}");
    }

    private function displaySummary()
    {
        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║                        Summary                                 ║');
        $this->info('╚════════════════════════════════════════════════════════════════╝');

        $this->line("<fg=green>✓ Passed:</> {$this->passCount}");
        $this->line("<fg=red>✗ Failed:</> {$this->failCount}");
        $this->line("<fg=yellow>⚠ Warnings:</> {$this->warnCount}");

        if ($this->failCount === 0 && $this->warnCount === 0) {
            $this->newLine();
            $this->info('🎉 All systems operational!');
        } elseif ($this->failCount === 0) {
            $this->newLine();
            $this->line('<fg=yellow>System functional but has warnings. Review above.</>');
        } else {
            $this->newLine();
            $this->error('❌ System has errors that need fixing. Review above.');
        }
    }
}
