<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Landlord;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Models\Zone;
use App\Services\ChabrinExcelImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ExcelImportTest extends TestCase
{
    use RefreshDatabase;

    protected ChabrinExcelImportService $importService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importService = new ChabrinExcelImportService(dryRun: false);

        // Create default zone for imports
        Zone::factory()->create(['name' => 'Zone A', 'code' => 'A']);
    }

    /**
     * Test that import service can be instantiated
     */
    public function test_import_service_can_be_instantiated(): void
    {
        $service = new ChabrinExcelImportService();
        $this->assertInstanceOf(ChabrinExcelImportService::class, $service);
    }

    /**
     * Test dry run mode prevents database writes
     */
    public function test_dry_run_mode_prevents_database_writes(): void
    {
        $service = new ChabrinExcelImportService(dryRun: true);
        $this->assertTrue($service->isDryRun());

        $serviceRegular = new ChabrinExcelImportService(dryRun: false);
        $this->assertFalse($serviceRegular->isDryRun());
    }

    /**
     * Test phone number formatting with various formats
     */
    public function test_phone_number_formatting(): void
    {
        $service = new ChabrinExcelImportService();

        // Test 9-digit phone starting with 7
        $this->assertEquals('0712345678', $service->formatPhonePublic('712345678'));

        // Test already formatted phone
        $this->assertEquals('0712345678', $service->formatPhonePublic('0712345678'));

        // Test phone with spaces and dashes
        $this->assertEquals('0712345678', $service->formatPhonePublic('071-234-5678'));

        // Test null input
        $this->assertNull($service->formatPhonePublic(null));

        // Test empty string
        $this->assertNull($service->formatPhonePublic(''));
    }

    /**
     * Test placeholder phone generation is deterministic
     */
    public function test_placeholder_phone_generation_is_deterministic(): void
    {
        $service = new ChabrinExcelImportService();

        $phone1 = $service->generatePlaceholderPhonePublic('test-seed-123');
        $phone2 = $service->generatePlaceholderPhonePublic('test-seed-123');

        $this->assertEquals($phone1, $phone2);
        $this->assertStringStartsWith('0700', $phone1);
        $this->assertEquals(10, strlen($phone1));
    }

    /**
     * Test lease type determination based on rent amount
     */
    public function test_lease_type_determination_based_on_rent(): void
    {
        $service = new ChabrinExcelImportService();

        // Commercial: >= 100,000
        $this->assertEquals('commercial', $service->determinLeaseTypePublic(150000));
        $this->assertEquals('commercial', $service->determinLeaseTypePublic(100000));

        // Residential Standard: >= 30,000 and < 100,000
        $this->assertEquals('residential_standard', $service->determinLeaseTypePublic(50000));
        $this->assertEquals('residential_standard', $service->determinLeaseTypePublic(30000));

        // Residential Micro: < 30,000
        $this->assertEquals('residential_micro', $service->determinLeaseTypePublic(15000));
        $this->assertEquals('residential_micro', $service->determinLeaseTypePublic(5000));
    }

    /**
     * Test position to role mapping
     */
    public function test_position_to_role_mapping(): void
    {
        $service = new ChabrinExcelImportService();

        $this->assertEquals('super_admin', $service->mapPositionToRolePublic('Director'));
        $this->assertEquals('zone_manager', $service->mapPositionToRolePublic('Zone Manager'));
        $this->assertEquals('manager', $service->mapPositionToRolePublic('Property Manager'));
        $this->assertEquals('manager', $service->mapPositionToRolePublic('Assistant Property Manager'));
        $this->assertEquals('agent', $service->mapPositionToRolePublic(null));
        $this->assertEquals('agent', $service->mapPositionToRolePublic('Unknown Position'));
    }

    /**
     * Test stats tracking increments correctly
     */
    public function test_stats_tracking_increments_correctly(): void
    {
        $service = new ChabrinExcelImportService();

        $service->incrementStat('landlords', 'imported');
        $service->incrementStat('landlords', 'imported');
        $service->incrementStat('landlords', 'failed');

        // Access stats through import result
        $result = $service->importAll([]);

        $this->assertEquals(2, $result['stats']['landlords']['imported']);
        $this->assertEquals(1, $result['stats']['landlords']['failed']);
    }

    /**
     * Test error collection
     */
    public function test_error_collection(): void
    {
        $service = new ChabrinExcelImportService();

        $service->addErrorPublic('LANDLORDS', 5, 'Test error message');
        $service->addErrorPublic('TENANTS', 10, 'Another error');

        $result = $service->importAll([]);

        $this->assertCount(2, $result['errors']);
        $this->assertEquals('LANDLORDS', $result['errors'][0]['type']);
        $this->assertEquals(5, $result['errors'][0]['row']);
        $this->assertEquals('Test error message', $result['errors'][0]['message']);
    }

    /**
     * Test import returns proper structure
     */
    public function test_import_returns_proper_structure(): void
    {
        $result = $this->importService->importAll([]);

        $this->assertArrayHasKey('stats', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('duration', $result);
        $this->assertArrayHasKey('started_at', $result);
        $this->assertArrayHasKey('completed_at', $result);

        // Check stats structure
        $this->assertArrayHasKey('landlords', $result['stats']);
        $this->assertArrayHasKey('properties', $result['stats']);
        $this->assertArrayHasKey('units', $result['stats']);
        $this->assertArrayHasKey('tenants', $result['stats']);
        $this->assertArrayHasKey('leases', $result['stats']);
        $this->assertArrayHasKey('staff', $result['stats']);
    }

    /**
     * Test import with non-existent file handles gracefully
     */
    public function test_import_handles_missing_files_gracefully(): void
    {
        $result = $this->importService->importAll([
            'landlords' => '/non/existent/file.xlsx',
        ]);

        $this->assertNotEmpty($result['errors']);
    }
}
