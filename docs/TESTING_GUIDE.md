# Testing Guide: How to Verify the System is Working

## Quick Health Check (5 minutes)

### 1. Check Laravel is Running

```bash
cd /home/user/chabrin-lease-system

# Check PHP version
php -v

# Check Laravel version and configuration
php artisan about

# This should show:
# - Application name
# - Laravel version
# - PHP version
# - Database connection status
```

### 2. Check Database Connection

```bash
# Test database connection
php artisan tinker

# In tinker, run:
DB::connection()->getPdo();
// Should return: PDO object

// Check tables exist
DB::select("SELECT name FROM sqlite_master WHERE type='table'");
// Should show: users, leases, zones, landlords, tenants, etc.

exit
```

### 3. Check Migrations Status

```bash
# See which migrations have run
php artisan migrate:status

# You should see GREEN checkmarks for:
# ✓ create_zones_table
# ✓ create_lease_approvals_table
# ✓ All previous migrations
```

---

## Test Zone-Based Access Control (10 minutes)

### Step 1: Create Test Zones

```bash
php artisan tinker
```

```php
// Create 3 zones
$zoneA = App\Models\Zone::create([
    'name' => 'Zone A - Westlands',
    'code' => 'ZN-A',
    'description' => 'Test zone for Westlands area',
    'is_active' => true,
]);

$zoneB = App\Models\Zone::create([
    'name' => 'Zone B - Kilimani',
    'code' => 'ZN-B',
    'description' => 'Test zone for Kilimani area',
    'is_active' => true,
]);

$zoneC = App\Models\Zone::create([
    'name' => 'Zone C - CBD',
    'code' => 'ZN-C',
    'description' => 'Test zone for CBD area',
    'is_active' => true,
]);

// Verify zones were created
App\Models\Zone::all();
// Should show 3 zones

exit
```

### Step 2: Create Test Users

```bash
php artisan tinker
```

```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Create Super Admin
$superAdmin = User::create([
    'name' => 'Super Admin',
    'email' => 'admin@test.com',
    'password' => Hash::make('password123'),
    'role' => 'super_admin',
    'is_active' => true,
]);

// Get zones
$zoneA = App\Models\Zone::where('code', 'ZN-A')->first();
$zoneB = App\Models\Zone::where('code', 'ZN-B')->first();

// Create Zone Manager for Zone A
$zmA = User::create([
    'name' => 'Zone Manager A',
    'email' => 'zm-a@test.com',
    'password' => Hash::make('password123'),
    'role' => 'zone_manager',
    'zone_id' => $zoneA->id,
    'is_active' => true,
]);

// Assign ZM to zone
$zoneA->update(['zone_manager_id' => $zmA->id]);

// Create Field Officer for Zone A
$foA = User::create([
    'name' => 'Field Officer A',
    'email' => 'fo-a@test.com',
    'password' => Hash::make('password123'),
    'role' => 'field_officer',
    'zone_id' => $zoneA->id,
    'is_active' => true,
]);

// Create Field Officer for Zone B
$foB = User::create([
    'name' => 'Field Officer B',
    'email' => 'fo-b@test.com',
    'password' => Hash::make('password123'),
    'role' => 'field_officer',
    'zone_id' => $zoneB->id,
    'is_active' => true,
]);

// Verify users
User::whereIn('role', ['zone_manager', 'field_officer'])->get(['name', 'email', 'role', 'zone_id']);

exit
```

### Step 3: Test Zone Access Methods

```bash
php artisan tinker
```

```php
use App\Models\User;

// Get users
$superAdmin = User::where('email', 'admin@test.com')->first();
$zmA = User::where('email', 'zm-a@test.com')->first();
$foA = User::where('email', 'fo-a@test.com')->first();

// Test role checking
$superAdmin->isSuperAdmin();  // Should return: true
$superAdmin->isAdmin();        // Should return: true
$zmA->isZoneManager();         // Should return: true
$zmA->hasZoneRestriction();    // Should return: true
$foA->isFieldOfficer();        // Should return: true

// Test zone access
$zoneA = App\Models\Zone::where('code', 'ZN-A')->first();
$zoneB = App\Models\Zone::where('code', 'ZN-B')->first();

// Super admin can access all zones
$superAdmin->canAccessZone($zoneA->id);  // Should return: true
$superAdmin->canAccessZone($zoneB->id);  // Should return: true

// ZM can only access their zone
$zmA->canAccessZone($zoneA->id);  // Should return: true
$zmA->canAccessZone($zoneB->id);  // Should return: false

// FO can only access their zone
$foA->canAccessZone($zoneA->id);  // Should return: true
$foA->canAccessZone($zoneB->id);  // Should return: false

// Get zone relationships
$zmA->zone;          // Should return: Zone A object
$zmA->managedZone;   // Should return: Zone A object
$zoneA->zoneManager; // Should return: ZM A object
$zoneA->fieldOfficers; // Should return collection with FO A

exit
```

**✅ If all return expected values, Zone-Based Access Control is working!**

---

## Test Lease Access Control (15 minutes)

### Step 1: Create Test Data

```bash
php artisan tinker
```

```php
use App\Models\{Tenant, Landlord, Lease, Zone};

// Get zones
$zoneA = Zone::where('code', 'ZN-A')->first();
$zoneB = Zone::where('code', 'ZN-B')->first();

// Create landlords in different zones
$landlordA = App\Models\Landlord::create([
    'name' => 'Landlord Zone A',
    'phone' => '+254700111111',
    'email' => 'landlord-a@test.com',
    'zone_id' => $zoneA->id,
]);

$landlordB = App\Models\Landlord::create([
    'name' => 'Landlord Zone B',
    'phone' => '+254700222222',
    'email' => 'landlord-b@test.com',
    'zone_id' => $zoneB->id,
]);

// Create tenants
$tenant1 = App\Models\Tenant::create([
    'name' => 'Test Tenant 1',
    'phone' => '+254711111111',
    'email' => 'tenant1@test.com',
]);

$tenant2 = App\Models\Tenant::create([
    'name' => 'Test Tenant 2',
    'phone' => '+254722222222',
    'email' => 'tenant2@test.com',
]);

// Create leases in different zones
$leaseA = App\Models\Lease::create([
    'reference_number' => 'LSE-TEST-A-001-2026',
    'lease_type' => 'commercial',
    'workflow_state' => 'draft',
    'tenant_id' => $tenant1->id,
    'landlord_id' => $landlordA->id,
    'zone_id' => $zoneA->id,
    'monthly_rent' => 50000,
]);

$leaseB = App\Models\Lease::create([
    'reference_number' => 'LSE-TEST-B-001-2026',
    'lease_type' => 'residential_micro',
    'workflow_state' => 'draft',
    'tenant_id' => $tenant2->id,
    'landlord_id' => $landlordB->id,
    'zone_id' => $zoneB->id,
    'monthly_rent' => 30000,
]);

echo "Created 2 landlords, 2 tenants, 2 leases in different zones\n";

exit
```

### Step 2: Test Lease Access Filtering

```bash
php artisan tinker
```

```php
use App\Models\{User, Lease};

// Get users
$superAdmin = User::where('email', 'admin@test.com')->first();
$foA = User::where('email', 'fo-a@test.com')->first();
$foB = User::where('email', 'fo-b@test.com')->first();

// Get leases
$leaseA = Lease::where('reference_number', 'LSE-TEST-A-001-2026')->first();
$leaseB = Lease::where('reference_number', 'LSE-TEST-B-001-2026')->first();

// Test super admin access (should see all)
$superAdmin->canAccessLease($leaseA);  // Should return: true
$superAdmin->canAccessLease($leaseB);  // Should return: true

// Test FO A access (should only see Zone A leases)
$foA->canAccessLease($leaseA);  // Should return: true
$foA->canAccessLease($leaseB);  // Should return: false

// Test FO B access (should only see Zone B leases)
$foB->canAccessLease($leaseA);  // Should return: false
$foB->canAccessLease($leaseB);  // Should return: true

// Test automatic filtering using scopes
Lease::accessibleByUser($foA)->get(['reference_number', 'zone_id']);
// Should return: Only Lease A

Lease::accessibleByUser($foB)->get(['reference_number', 'zone_id']);
// Should return: Only Lease B

Lease::accessibleByUser($superAdmin)->get(['reference_number', 'zone_id']);
// Should return: Both leases

// Test zone filtering
Lease::inZone($foA->zone_id)->get(['reference_number']);
// Should return: Only leases in Zone A

exit
```

**✅ If filtering works correctly, Lease Access Control is working!**

---

## Test Filament Admin Panel (10 minutes)

### Step 1: Start Development Server

```bash
cd /home/user/chabrin-lease-system

# Start Laravel server
php artisan serve
```

Open browser to: **http://localhost:8000/admin**

### Step 2: Login and Test

1. **Login as Super Admin:**
   - Email: `admin@test.com`
   - Password: `password123`
   - ✅ Should see ALL leases (from all zones)

2. **Logout and Login as Field Officer A:**
   - Email: `fo-a@test.com`
   - Password: `password123`
   - ✅ Should ONLY see leases from Zone A
   - ❌ Should NOT see leases from Zone B

3. **Logout and Login as Field Officer B:**
   - Email: `fo-b@test.com`
   - Password: `password123`
   - ✅ Should ONLY see leases from Zone B
   - ❌ Should NOT see leases from Zone A

**✅ If users only see their zone's data, Filament filtering is working!**

---

## Test Landlord Approval Workflow (10 minutes)

```bash
php artisan tinker
```

```php
use App\Models\{Lease, Landlord};
use App\Services\LandlordApprovalService;

// Get a lease with landlord
$lease = Lease::whereNotNull('landlord_id')->first();

if (!$lease) {
    echo "No lease with landlord found. Create one first.\n";
    exit;
}

// Test 1: Request Approval
$result = LandlordApprovalService::requestApproval($lease, 'email');
print_r($result);
// Should show: success => true, approval created

// Check lease state changed
$lease->refresh();
echo "Workflow state: " . $lease->workflow_state . "\n";
// Should be: pending_landlord_approval

// Test 2: Check approval status
$lease->hasPendingApproval();  // Should return: true
$lease->hasBeenApproved();     // Should return: false
$lease->hasBeenRejected();     // Should return: false

// Test 3: Approve the lease
$result = LandlordApprovalService::approveLease($lease, 'Looks good!', 'email');
print_r($result);
// Should show: success => true

// Check state changed
$lease->refresh();
echo "Workflow state: " . $lease->workflow_state . "\n";
// Should be: approved

$lease->hasBeenApproved();  // Should return: true

exit
```

**✅ If approval workflow changes lease states correctly, it's working!**

---

## Test API Endpoints (15 minutes)

### Landlord API Endpoints

```bash
# Test 1: Get pending approvals for landlord
curl -X GET "http://localhost:8000/api/landlord/1/approvals" \
  -H "Accept: application/json" | json_pp

# Should return: JSON with pending leases

# Test 2: Get specific lease details
curl -X GET "http://localhost:8000/api/landlord/1/approvals/1" \
  -H "Accept: application/json" | json_pp

# Should return: Full lease details
```

### Field Officer API Endpoints

```bash
# Test 1: Dashboard stats
curl -X GET "http://localhost:8000/api/field-officer/dashboard" \
  -H "Accept: application/json" | json_pp

# Should return: Approval statistics

# Test 2: Pending approvals
curl -X GET "http://localhost:8000/api/field-officer/pending-approvals" \
  -H "Accept: application/json" | json_pp

# Should return: All pending leases

# Test 3: Overdue approvals
curl -X GET "http://localhost:8000/api/field-officer/overdue-approvals" \
  -H "Accept: application/json" | json_pp

# Should return: Overdue leases (if any)
```

**✅ If APIs return JSON responses, they're working!**

---

## Test Digital Signing Flow (20 minutes)

### Step 1: Generate Signing Link

```bash
php artisan tinker
```

```php
use App\Models\Lease;

$lease = Lease::where('workflow_state', 'approved')->first();

if (!$lease) {
    echo "Create an approved lease first\n";
    exit;
}

// Get signing URL
$signingUrl = route('tenant.sign-lease', ['lease' => $lease->id]);
echo "Signing URL: " . $signingUrl . "\n";
// Copy this URL

exit
```

### Step 2: Test Signing Portal

1. Open browser to the signing URL (from above)
2. ✅ Should see tenant signing portal
3. Click "Send Verification Code"
4. ✅ Should generate OTP (check database or logs)
5. Enter OTP and verify
6. ✅ Should proceed to lease preview
7. Sign the lease
8. ✅ Should save signature and update workflow state

---

## Test PDF Generation (10 minutes)

```bash
php artisan tinker
```

```php
use App\Models\Lease;

$lease = Lease::first();

// Test PDF generation (if you have the controller)
echo "Lease ID: " . $lease->id . "\n";
echo "Download URL: " . route('lease.download', $lease) . "\n";

exit
```

Visit the download URL in browser:
- ✅ Should download PDF
- ✅ Should have QR code
- ✅ Should have all lease data

---

## Test Database Integrity (5 minutes)

```bash
php artisan tinker
```

```php
// Test relationships work
$zone = App\Models\Zone::first();
$zone->leases()->count();        // Should return: number of leases in zone
$zone->fieldOfficers()->count(); // Should return: number of FOs in zone
$zone->zoneManager;              // Should return: User object or null

$user = App\Models\User::where('role', 'field_officer')->first();
$user->zone;                     // Should return: Zone object
$user->assignedLeases()->count(); // Should return: number

$lease = App\Models\Lease::first();
$lease->tenant;                  // Should return: Tenant object
$lease->landlord;                // Should return: Landlord object or null
$lease->assignedZone;            // Should return: Zone object or null
$lease->approvals()->count();    // Should return: number of approvals

exit
```

**✅ If all relationships return data without errors, database is correct!**

---

## Quick Verification Checklist

Run this comprehensive test script:

```bash
php artisan tinker
```

```php
echo "=== SYSTEM HEALTH CHECK ===\n\n";

// 1. Database connection
try {
    DB::connection()->getPdo();
    echo "✅ Database: Connected\n";
} catch (Exception $e) {
    echo "❌ Database: Failed - " . $e->getMessage() . "\n";
}

// 2. Zones
$zoneCount = App\Models\Zone::count();
echo "✅ Zones: {$zoneCount} zones created\n";

// 3. Users
$userCount = App\Models\User::count();
$zmCount = App\Models\User::where('role', 'zone_manager')->count();
$foCount = App\Models\User::where('role', 'field_officer')->count();
echo "✅ Users: {$userCount} total ({$zmCount} ZMs, {$foCount} FOs)\n";

// 4. Leases
$leaseCount = App\Models\Lease::count();
$leasesWithZone = App\Models\Lease::whereNotNull('zone_id')->count();
echo "✅ Leases: {$leaseCount} total ({$leasesWithZone} assigned to zones)\n";

// 5. Approvals
$approvalCount = App\Models\LeaseApproval::count();
$pendingCount = App\Models\Lease::where('workflow_state', 'pending_landlord_approval')->count();
echo "✅ Approvals: {$approvalCount} records ({$pendingCount} pending)\n";

// 6. Zone filtering test
$testUser = App\Models\User::where('role', 'field_officer')->first();
if ($testUser && $testUser->zone_id) {
    $accessibleLeases = App\Models\Lease::accessibleByUser($testUser)->count();
    $totalLeases = App\Models\Lease::count();
    echo "✅ Zone Filtering: FO sees {$accessibleLeases}/{$totalLeases} leases\n";
} else {
    echo "⚠️  Zone Filtering: No FO with zone assigned\n";
}

echo "\n=== END HEALTH CHECK ===\n";

exit
```

---

## Troubleshooting Common Issues

### Issue 1: "Class 'App\Models\Zone' not found"

```bash
# Regenerate autoload files
composer dump-autoload
```

### Issue 2: Migration errors

```bash
# Rollback and re-run
php artisan migrate:rollback
php artisan migrate --force
```

### Issue 3: Can't login to admin panel

```bash
# Create new admin user
php artisan tinker
$admin = App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@test.com',
    'password' => Hash::make('password123'),
    'role' => 'super_admin',
    'is_active' => true,
]);
```

### Issue 4: Leases not filtering by zone

```bash
# Check if getEloquentQuery is being called
# Add this to LeaseResource.php temporarily:
public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
{
    $query = parent::getEloquentQuery();
    $user = auth()->user();

    \Log::info('Zone filtering', [
        'user_id' => $user->id,
        'role' => $user->role,
        'zone_id' => $user->zone_id,
    ]);

    if ($user && $user->hasZoneRestriction() && $user->zone_id) {
        $query->where('zone_id', $user->zone_id);
    }

    return $query;
}

# Then check logs
tail -f storage/logs/laravel.log
```

---

## Summary: What Works

After following this guide, you should have verified:

- ✅ Database connection and migrations
- ✅ Zones created and assigned
- ✅ Users with correct roles
- ✅ Zone-based access control methods
- ✅ Lease filtering by zone
- ✅ Filament admin panel filtering
- ✅ Landlord approval workflow
- ✅ API endpoints responding
- ✅ Digital signing flow
- ✅ PDF generation (if tested)
- ✅ Database relationships

---

**Next Steps:** Once you confirm everything works, share your lease templates and I'll continue with:
1. Interactive dashboard
2. Century Gothic fonts
3. API security implementation
