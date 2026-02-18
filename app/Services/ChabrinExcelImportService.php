<?php

namespace App\Services;

use App\Models\Landlord;
use App\Models\Lease;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

class ChabrinExcelImportService
{
    protected array $errors = [];

    protected array $stats = [
        'landlords' => ['imported' => 0, 'failed' => 0],
        'properties' => ['imported' => 0, 'failed' => 0],
        'units' => ['imported' => 0, 'failed' => 0],
        'tenants' => ['imported' => 0, 'failed' => 0],
        'leases' => ['imported' => 0, 'failed' => 0],
        'staff' => ['imported' => 0, 'failed' => 0],
    ];

    protected bool $dryRun = false;

    public function __construct(bool $dryRun = false)
    {
        $this->dryRun = $dryRun;
        // Increase memory limit for large Excel files
        ini_set('memory_limit', '512M');
    }

    /**
     * Import all data from Excel files
     */
    public function importAll(array $filePaths, bool $cleanImport = false): array
    {
        $startTime = now();

        try {
            if ($cleanImport && ! $this->dryRun) {
                $this->cleanDatabase();
            }

            // Import in correct order respecting foreign keys
            if (isset($filePaths['landlords'])) {
                $this->importLandlords($filePaths['landlords']);
            }

            if (isset($filePaths['staff'])) {
                $this->importStaff($filePaths['staff']);
            }

            if (isset($filePaths['properties'])) {
                $this->importProperties($filePaths['properties']);
            }

            if (isset($filePaths['units'])) {
                $this->importUnits($filePaths['units']);
            }

            if (isset($filePaths['tenants'])) {
                $this->importTenants($filePaths['tenants']);
            }

        } catch (Exception $e) {
            $this->addErrorPublic('GENERAL', 0, 'Fatal error: ' . $e->getMessage());
        }

        $endTime = now();

        return [
            'stats' => $this->stats,
            'errors' => $this->errors,
            'duration' => $endTime->diffForHumans($startTime, true),
            'started_at' => $startTime->toDateTimeString(),
            'completed_at' => $endTime->toDateTimeString(),
        ];
    }

    // Public helper methods for anonymous classes
    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    public function incrementStat(string $type, string $key): void
    {
        $this->stats[$type][$key]++;
    }

    public function addErrorPublic(string $type, int $row, string $message): void
    {
        $this->errors[] = ['type' => $type, 'row' => $row, 'message' => $message];
    }

    public function formatPhonePublic(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) === 9 && substr($phone, 0, 1) === '7') {
            return '0' . $phone;
        }

        return $phone;
    }

    public function generatePlaceholderPhonePublic(string $seed): string
    {
        $hash = crc32($seed);

        return '0700' . str_pad(substr((string) abs($hash), 0, 6), 6, '0', STR_PAD_LEFT);
    }

    public function determinLeaseTypePublic(float $rent): string
    {
        if ($rent >= 100000) {
            return 'commercial';
        } elseif ($rent >= 30000) {
            return 'residential_standard';
        } else {
            return 'residential_micro';
        }
    }

    public function mapPositionToRolePublic(?string $position): string
    {
        if (! $position) {
            return 'agent';
        }
        $position = strtolower(trim($position));

        // Exact matches first
        $roleMap = [
            'director' => 'super_admin',
            'zone manager' => 'zone_manager',
            'property manager' => 'manager',
            'assistant property manager' => 'manager',
            'senior field officer' => 'field_officer',
            'field officer' => 'field_officer',
            'chief accountant' => 'admin',
            'hr & op manager' => 'admin',
            'quality control and assurance manager' => 'admin',
            'internal auditor' => 'viewer',
            'it support' => 'admin',
            'office administrator' => 'admin',
            'office administrator assistant' => 'agent',
            'office assistant' => 'agent',
            'records staff' => 'viewer',
        ];

        if (isset($roleMap[$position])) {
            return $roleMap[$position];
        }

        // Fallback pattern matching
        if (str_contains($position, 'director')) {
            return 'super_admin';
        }
        if (str_contains($position, 'zone') && str_contains($position, 'manager')) {
            return 'zone_manager';
        }
        if (str_contains($position, 'manager')) {
            return 'manager';
        }
        if (str_contains($position, 'field') && str_contains($position, 'officer')) {
            return 'field_officer';
        }
        if (str_contains($position, 'senior')) {
            return 'manager';
        }
        if (str_contains($position, 'admin')) {
            return 'admin';
        }
        if (str_contains($position, 'accountant')) {
            return 'admin';
        }
        if (str_contains($position, 'auditor')) {
            return 'viewer';
        }

        return 'agent';
    }

    /**
     * Import landlords from Excel
     */
    protected function importLandlords(string $filePath): void
    {
        $rowNumber = 1;

        Excel::import(new class($this, $rowNumber) implements ToCollection, WithChunkReading, WithHeadingRow
        {
            protected $service;

            protected $rowNumber;

            public function __construct($service, &$rowNumber)
            {
                $this->service = $service;
                $this->rowNumber = &$rowNumber;
            }

            public function chunkSize(): int
            {
                return 200;
            }

            public function collection(Collection $rows)
            {
                foreach ($rows as $row) {
                    $this->rowNumber++;

                    try {
                        $lanid = $this->getValue($row, 'vendorid');
                        $name = $this->getValue($row, 'lfname');
                        $email = $this->getValue($row, 'addrss4');
                        $phone = $this->getValue($row, 'tlphon');

                        // Skip empty rows
                        if (empty($lanid) && empty($name)) {
                            continue;
                        }

                        if (empty($lanid) || empty($name)) {
                            throw new Exception('Missing LANID or Name');
                        }

                        // Skip if already exists
                        if (Landlord::where('landlord_code', $lanid)->exists()) {
                            continue;
                        }

                        if ($email && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $email = null;
                        }

                        if (! $this->service->isDryRun()) {
                            Landlord::create([
                                'landlord_code' => $lanid,
                                'name' => $name,
                                'email' => $email,
                                'phone' => $this->service->formatPhonePublic($phone),
                                'is_active' => true,
                            ]);
                        }

                        $this->service->incrementStat('landlords', 'imported');

                    } catch (Exception $e) {
                        $this->service->addErrorPublic('LANDLORDS', $this->rowNumber, $e->getMessage());
                        $this->service->incrementStat('landlords', 'failed');
                    }
                }
            }

            protected function getValue($row, $key)
            {
                return isset($row[$key]) ? trim((string) $row[$key]) : null;
            }
        }, $filePath);
    }

    /**
     * Import properties from Excel
     */
    protected function importProperties(string $filePath): void
    {
        $rowNumber = 1;

        Excel::import(new class($this, $rowNumber) implements ToCollection, WithChunkReading, WithHeadingRow
        {
            protected $service;

            protected $rowNumber;

            public function __construct($service, &$rowNumber)
            {
                $this->service = $service;
                $this->rowNumber = &$rowNumber;
            }

            public function chunkSize(): int
            {
                return 200;
            }

            public function collection(Collection $rows)
            {
                foreach ($rows as $row) {
                    $this->rowNumber++;

                    try {
                        $blockId = $this->getValue($row, 'blockid');
                        $blockDesc = $this->getValue($row, 'blockdesc');
                        $zone = $this->getValue($row, 'zone');
                        $llordId = $this->getValue($row, 'llordid');

                        // Skip empty rows
                        if (empty($blockId) && empty($blockDesc)) {
                            continue;
                        }

                        if (empty($blockId) || empty($blockDesc)) {
                            throw new Exception('Missing BLOCKID or BLOCKDESC');
                        }

                        // Skip if already exists
                        if (Property::where('reference_number', $blockId)->exists()) {
                            continue;
                        }

                        // Try to find landlord by exact match or without prefix
                        $landlord = Landlord::where('landlord_code', $llordId)->first();
                        if (! $landlord && str_starts_with($llordId, 'LAN-')) {
                            // Try without LAN- prefix
                            $landlord = Landlord::where('landlord_code', substr($llordId, 4))->first();
                        }
                        if (! $landlord) {
                            // Try adding LAN- prefix
                            $landlord = Landlord::where('landlord_code', 'LAN-' . $llordId)->first();
                        }
                        if (! $landlord) {
                            throw new Exception("Landlord '{$llordId}' not found");
                        }

                        if ($zone && ! in_array(strtoupper($zone), ['A', 'B', 'C', 'D', 'E', 'F', 'G'])) {
                            $zone = 'A';
                        }

                        if (! $this->service->isDryRun()) {
                            Property::create([
                                'reference_number' => $blockId,
                                'property_name' => $blockDesc,
                                'zone' => strtoupper($zone) ?: 'A',
                                'landlord_id' => $landlord->id,
                                'commission' => 10.00,
                            ]);
                        }

                        $this->service->incrementStat('properties', 'imported');

                    } catch (Exception $e) {
                        $this->service->addErrorPublic('PROPERTIES', $this->rowNumber, $e->getMessage());
                        $this->service->incrementStat('properties', 'failed');
                    }
                }
            }

            protected function getValue($row, $key)
            {
                return isset($row[$key]) ? trim((string) $row[$key]) : null;
            }
        }, $filePath);
    }

    /**
     * Import units from Excel
     */
    protected function importUnits(string $filePath): void
    {
        $rowNumber = 1;

        // Pre-cache properties for faster lookup
        $propertyCache = Property::pluck('id', 'reference_number')->toArray();

        Excel::import(new class($this, $rowNumber, $propertyCache) implements ToCollection, WithChunkReading, WithHeadingRow
        {
            protected $service;

            protected $rowNumber;

            protected $propertyCache;

            public function __construct($service, &$rowNumber, $propertyCache)
            {
                $this->service = $service;
                $this->rowNumber = &$rowNumber;
                $this->propertyCache = $propertyCache;
            }

            public function chunkSize(): int
            {
                return 500;
            }

            public function collection(Collection $rows)
            {
                foreach ($rows as $row) {
                    $this->rowNumber++;

                    try {
                        $unitNo = $this->getValue($row, 'unitno');
                        $blockId = $this->getValue($row, 'blockid');
                        $unitDesc = $this->getValue($row, 'unitdesc');
                        $rentAmt = $this->getValue($row, 'rntamt');

                        // Skip empty rows
                        if (empty($unitNo) && empty($blockId)) {
                            continue;
                        }

                        if (empty($unitNo) || empty($blockId)) {
                            throw new Exception('Missing UNITNO or BLOCKID');
                        }

                        if (! isset($this->propertyCache[$blockId])) {
                            throw new Exception("Property '{$blockId}' not found");
                        }

                        $propertyId = $this->propertyCache[$blockId];

                        // Skip if already exists
                        if (Unit::where('property_id', $propertyId)->where('unit_number', $unitNo)->exists()) {
                            continue;
                        }

                        $rentAmt = floatval($rentAmt);
                        if ($rentAmt <= 0) {
                            $rentAmt = 5000; // Default rent if invalid
                        }

                        if (! $this->service->isDryRun()) {
                            Unit::create([
                                'property_id' => $propertyId,
                                'unit_number' => $unitNo,
                                'type' => $unitDesc ?: $blockId . '-' . $unitNo,
                                'rent_amount' => $rentAmt,
                                'deposit_required' => 0,
                                'status_legacy' => 'VACANT',
                            ]);
                        }

                        $this->service->incrementStat('units', 'imported');

                    } catch (Exception $e) {
                        $this->service->addErrorPublic('UNITS', $this->rowNumber, $e->getMessage());
                        $this->service->incrementStat('units', 'failed');
                    }
                }
            }

            protected function getValue($row, $key)
            {
                return isset($row[$key]) ? trim((string) $row[$key]) : null;
            }
        }, $filePath);
    }

    /**
     * Import tenants and create leases from Excel
     */
    protected function importTenants(string $filePath): void
    {
        $rowNumber = 1;
        $tenantCache = [];

        // Pre-cache all properties and units in memory to avoid repeated DB queries
        $propertyCache = [];
        $unitCache = [];

        foreach (Property::all() as $property) {
            $propertyCache[$property->property_code] = $property;
        }

        foreach (Unit::with('property')->get() as $unit) {
            $key = $unit->property->property_code . '-' . $unit->unit_number;
            $unitCache[$key] = $unit;
        }

        Excel::import(new class($this, $rowNumber, $tenantCache, $propertyCache, $unitCache) implements ToCollection, WithChunkReading, WithHeadingRow
        {
            protected $service;

            protected $rowNumber;

            protected $tenantCache;

            protected $propertyCache;

            protected $unitCache;

            public function __construct($service, &$rowNumber, &$tenantCache, &$propertyCache, &$unitCache)
            {
                $this->service = $service;
                $this->rowNumber = &$rowNumber;
                $this->tenantCache = &$tenantCache;
                $this->propertyCache = &$propertyCache;
                $this->unitCache = &$unitCache;
            }

            public function chunkSize(): int
            {
                return 500;
            }

            public function collection(Collection $rows)
            {
                foreach ($rows as $row) {
                    $this->rowNumber++;

                    try {
                        $tenantName = $this->getValue($row, 'tenant');
                        $tenantPhone = $this->getValue($row, 'tenant_number');
                        $tenantId = $this->getValue($row, 'tenant_id');
                        $blockId = $this->getValue($row, 'blockid');
                        $unitNo = $this->getValue($row, 'unitno');

                        // Skip empty rows
                        if (empty($tenantName) && empty($blockId)) {
                            continue;
                        }

                        if (empty($tenantName)) {
                            throw new Exception('Missing tenant name');
                        }

                        // Use actual phone if available, otherwise generate placeholder
                        $phoneNumber = $tenantPhone
                            ? $this->service->formatPhonePublic($tenantPhone)
                            : ($tenantId
                                ? $this->service->generatePlaceholderPhonePublic($tenantId)
                                : $this->service->generatePlaceholderPhonePublic($tenantName));

                        $tenantKey = strtolower(trim($tenantName));
                        if (! isset($this->tenantCache[$tenantKey])) {
                            if (! $this->service->isDryRun()) {
                                $tenant = Tenant::firstOrCreate(
                                    ['phone_number' => $phoneNumber],
                                    [
                                        'full_name' => $tenantName,
                                        'id_number' => $tenantId,
                                        'notification_preference' => 'SMS',
                                    ],
                                );
                                $this->tenantCache[$tenantKey] = $tenant->id;
                            } else {
                                $this->tenantCache[$tenantKey] = 0;
                            }
                            $this->service->incrementStat('tenants', 'imported');
                        }

                        // Create lease using cached data
                        if ($blockId && $unitNo) {
                            if (! isset($this->propertyCache[$blockId])) {
                                throw new Exception("Property '{$blockId}' not found");
                            }

                            $property = $this->propertyCache[$blockId];
                            $unitKey = $blockId . '-' . $unitNo;

                            if (! isset($this->unitCache[$unitKey])) {
                                throw new Exception("Unit '{$unitNo}' not found in '{$blockId}'");
                            }

                            $unit = $this->unitCache[$unitKey];

                            // Check if lease already exists for this unit
                            if (Lease::where('unit_id', $unit->id)->where('workflow_state', 'active')->exists()) {
                                continue; // Skip - unit already has an active lease
                            }

                            if (! $this->service->isDryRun()) {
                                $referenceNumber = 'CL-' . date('Y') . '-' . strtoupper(Str::random(8));

                                Lease::create([
                                    'reference_number' => $referenceNumber,
                                    'source' => 'landlord',
                                    'lease_type' => $this->service->determinLeaseTypePublic($unit->market_rent),
                                    'workflow_state' => 'active',
                                    'tenant_id' => $this->tenantCache[$tenantKey],
                                    'unit_id' => $unit->id,
                                    'property_id' => $property->id,
                                    'landlord_id' => $property->landlord_id,
                                    'zone' => $property->zone,
                                    'monthly_rent' => $unit->market_rent,
                                    'deposit_amount' => $unit->market_rent * 2,
                                    'start_date' => now(),
                                    'signing_mode' => 'physical',
                                ]);

                                $unit->update(['status_legacy' => 'OCCUPIED']);
                            }

                            $this->service->incrementStat('leases', 'imported');
                        }

                    } catch (Exception $e) {
                        $this->service->addErrorPublic('TENANTS', $this->rowNumber, $e->getMessage());
                        $this->service->incrementStat('tenants', 'failed');
                    }
                }
            }

            protected function getValue($row, $key)
            {
                return isset($row[$key]) ? trim((string) $row[$key]) : null;
            }
        }, $filePath);
    }

    /**
     * Import staff from Excel
     */
    protected function importStaff(string $filePath): void
    {
        $rowNumber = 1;

        Excel::import(new class($this, $rowNumber) implements ToCollection, WithChunkReading, WithHeadingRow
        {
            protected $service;

            protected $rowNumber;

            public function __construct($service, &$rowNumber)
            {
                $this->service = $service;
                $this->rowNumber = &$rowNumber;
            }

            public function chunkSize(): int
            {
                return 100;
            }

            public function collection(Collection $rows)
            {
                foreach ($rows as $row) {
                    $this->rowNumber++;

                    try {
                        $empNumber = $this->getValue($row, 'empnumb');
                        $fullName = $this->getValue($row, 'fullname');
                        $position = $this->getValue($row, 'rtncat');
                        $dept = $this->getValue($row, 'dept');
                        $mobile = $this->getValue($row, 'mobnum1');
                        $email = $this->getValue($row, 'email');

                        // Skip empty rows (Excel file has many empty rows)
                        if (empty($fullName) && empty($email) && empty($empNumber)) {
                            continue;
                        }

                        if (empty($fullName)) {
                            throw new Exception('Missing Name');
                        }

                        // Skip rows without valid email (but don't fail, just skip)
                        if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            // Generate a placeholder email if name exists but no email
                            $email = strtolower(str_replace(' ', '.', $fullName)) . '@chabrin.placeholder.local';
                        }

                        // Check if user already exists
                        if (User::where('email', $email)->exists()) {
                            continue; // Skip duplicate
                        }

                        $role = $this->service->mapPositionToRolePublic($position);

                        if (! $this->service->isDryRun()) {
                            $defaultPassword = config('import.staff_default_password');
                            User::create([
                                'name' => $fullName,
                                'email' => $email,
                                'password' => Hash::make($defaultPassword ?? Str::random(32)),
                                'role' => $role,
                                'phone' => $this->service->formatPhonePublic($mobile),
                                'department' => $dept,
                                'bio' => $empNumber ? "Staff Number: {$empNumber}" : null,
                                'is_active' => true,
                            ]);
                        }

                        $this->service->incrementStat('staff', 'imported');

                    } catch (Exception $e) {
                        $this->service->addErrorPublic('STAFF', $this->rowNumber, $e->getMessage());
                        $this->service->incrementStat('staff', 'failed');
                    }
                }
            }

            protected function getValue($row, $key)
            {
                return isset($row[$key]) ? trim((string) $row[$key]) : null;
            }
        }, $filePath);
    }

    /**
     * Clean database tables
     */
    protected function cleanDatabase(): void
    {
        // Use CASCADE to handle foreign key constraints (works for PostgreSQL)
        DB::statement('TRUNCATE TABLE leases CASCADE');
        DB::statement('TRUNCATE TABLE units CASCADE');
        DB::statement('TRUNCATE TABLE properties CASCADE');
        DB::statement('TRUNCATE TABLE landlords CASCADE');
        DB::statement('TRUNCATE TABLE tenants CASCADE');
    }
}
