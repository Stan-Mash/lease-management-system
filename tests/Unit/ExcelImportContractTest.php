<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ChabrinExcelImportService;
use PHPUnit\Framework\TestCase;

/**
 * Contract tests for Excel Import Service
 *
 * These tests verify the service's public interface contracts remain stable.
 * They don't require database access and test pure logic only.
 */
class ExcelImportContractTest extends TestCase
{
    protected ChabrinExcelImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ChabrinExcelImportService(dryRun: true);
    }

    /*
    |--------------------------------------------------------------------------
    | Phone Formatting Contract Tests
    |--------------------------------------------------------------------------
    */

    /**
     * @dataProvider phoneFormattingProvider
     */
    public function test_phone_formatting_contract(mixed $input, ?string $expected): void
    {
        $result = $this->service->formatPhonePublic($input);
        $this->assertEquals($expected, $result);
    }

    public static function phoneFormattingProvider(): array
    {
        return [
            // Valid phone numbers
            'nine_digit_starting_with_7' => ['712345678', '0712345678'],
            'already_formatted_10_digit' => ['0712345678', '0712345678'],
            'with_dashes' => ['071-234-5678', '0712345678'],
            'with_spaces' => ['071 234 5678', '0712345678'],
            'with_parentheses' => ['(071) 234-5678', '0712345678'],
            'with_plus_254' => ['+254712345678', '254712345678'],

            // Edge cases
            'null_input' => [null, null],
            'empty_string' => ['', null],

            // International format
            'kenyan_international' => ['254712345678', '254712345678'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Placeholder Phone Generation Contract Tests
    |--------------------------------------------------------------------------
    */

    public function test_placeholder_phone_starts_with_0700(): void
    {
        $phone = $this->service->generatePlaceholderPhonePublic('any-seed');
        $this->assertStringStartsWith('0700', $phone);
    }

    public function test_placeholder_phone_has_correct_length(): void
    {
        $phone = $this->service->generatePlaceholderPhonePublic('any-seed');
        $this->assertEquals(10, strlen($phone));
    }

    public function test_placeholder_phone_is_numeric(): void
    {
        $phone = $this->service->generatePlaceholderPhonePublic('any-seed');
        $this->assertTrue(ctype_digit($phone));
    }

    public function test_placeholder_phone_is_deterministic(): void
    {
        $seed = 'test-seed-' . uniqid();
        $phone1 = $this->service->generatePlaceholderPhonePublic($seed);
        $phone2 = $this->service->generatePlaceholderPhonePublic($seed);
        $this->assertEquals($phone1, $phone2);
    }

    public function test_different_seeds_produce_different_phones(): void
    {
        $phone1 = $this->service->generatePlaceholderPhonePublic('seed-one');
        $phone2 = $this->service->generatePlaceholderPhonePublic('seed-two');
        $this->assertNotEquals($phone1, $phone2);
    }

    /*
    |--------------------------------------------------------------------------
    | Lease Type Determination Contract Tests
    |--------------------------------------------------------------------------
    */

    /**
     * @dataProvider leaseTypeProvider
     */
    public function test_lease_type_determination_contract(float $rent, string $expectedType): void
    {
        $result = $this->service->determinLeaseTypePublic($rent);
        $this->assertEquals($expectedType, $result);
    }

    public static function leaseTypeProvider(): array
    {
        return [
            // Commercial threshold: >= 100,000
            'commercial_at_threshold' => [100000, 'commercial'],
            'commercial_above_threshold' => [150000, 'commercial'],
            'commercial_high' => [500000, 'commercial'],

            // Residential Standard threshold: >= 30,000 and < 100,000
            'standard_at_threshold' => [30000, 'residential_standard'],
            'standard_middle' => [50000, 'residential_standard'],
            'standard_near_commercial' => [99999, 'residential_standard'],

            // Residential Micro threshold: < 30,000
            'micro_below_threshold' => [29999, 'residential_micro'],
            'micro_low' => [10000, 'residential_micro'],
            'micro_very_low' => [5000, 'residential_micro'],
            'micro_minimal' => [1000, 'residential_micro'],
            'micro_zero' => [0, 'residential_micro'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Position to Role Mapping Contract Tests
    |--------------------------------------------------------------------------
    */

    /**
     * @dataProvider positionRoleMappingProvider
     */
    public function test_position_to_role_mapping_contract(?string $position, string $expectedRole): void
    {
        $result = $this->service->mapPositionToRolePublic($position);
        $this->assertEquals($expectedRole, $result);
    }

    public static function positionRoleMappingProvider(): array
    {
        return [
            // Exact matches (case insensitive)
            'director_lowercase' => ['director', 'super_admin'],
            'director_uppercase' => ['DIRECTOR', 'super_admin'],
            'director_mixed_case' => ['Director', 'super_admin'],

            'zone_manager' => ['zone manager', 'zone_manager'],
            'zone_manager_caps' => ['Zone Manager', 'zone_manager'],

            'property_manager' => ['property manager', 'manager'],
            'property_manager_caps' => ['Property Manager', 'manager'],

            'assistant_property_manager' => ['assistant property manager', 'manager'],
            'assistant_pm_caps' => ['Assistant Property Manager', 'manager'],

            // Default fallback
            'null_position' => [null, 'agent'],
            'unknown_position' => ['Unknown Position', 'agent'],
            'empty_string' => ['', 'agent'],
            'random_text' => ['Some Random Text', 'agent'],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Service Configuration Contract Tests
    |--------------------------------------------------------------------------
    */

    public function test_dry_run_mode_can_be_enabled(): void
    {
        $service = new ChabrinExcelImportService(dryRun: true);
        $this->assertTrue($service->isDryRun());
    }

    public function test_dry_run_mode_can_be_disabled(): void
    {
        $service = new ChabrinExcelImportService(dryRun: false);
        $this->assertFalse($service->isDryRun());
    }

    public function test_default_dry_run_is_false(): void
    {
        $service = new ChabrinExcelImportService();
        $this->assertFalse($service->isDryRun());
    }

    /*
    |--------------------------------------------------------------------------
    | Stats Structure Contract Tests
    |--------------------------------------------------------------------------
    */

    public function test_stats_have_required_keys(): void
    {
        $result = $this->service->importAll([]);

        $requiredStatKeys = ['landlords', 'properties', 'units', 'tenants', 'leases', 'staff'];

        foreach ($requiredStatKeys as $key) {
            $this->assertArrayHasKey($key, $result['stats'], "Missing stat key: {$key}");
            $this->assertArrayHasKey('imported', $result['stats'][$key], "Missing 'imported' for {$key}");
            $this->assertArrayHasKey('failed', $result['stats'][$key], "Missing 'failed' for {$key}");
        }
    }

    public function test_stats_start_at_zero(): void
    {
        $service = new ChabrinExcelImportService(dryRun: true);
        $result = $service->importAll([]);

        foreach ($result['stats'] as $type => $counts) {
            $this->assertEquals(0, $counts['imported'], "{$type} imported should start at 0");
            $this->assertEquals(0, $counts['failed'], "{$type} failed should start at 0");
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Error Structure Contract Tests
    |--------------------------------------------------------------------------
    */

    public function test_error_has_required_fields(): void
    {
        $this->service->addErrorPublic('TEST', 1, 'Test message');
        $result = $this->service->importAll([]);

        $this->assertNotEmpty($result['errors']);
        $error = $result['errors'][0];

        $this->assertArrayHasKey('type', $error);
        $this->assertArrayHasKey('row', $error);
        $this->assertArrayHasKey('message', $error);
    }

    public function test_errors_preserve_order(): void
    {
        $this->service->addErrorPublic('FIRST', 1, 'First error');
        $this->service->addErrorPublic('SECOND', 2, 'Second error');
        $this->service->addErrorPublic('THIRD', 3, 'Third error');

        $result = $this->service->importAll([]);

        $this->assertEquals('FIRST', $result['errors'][0]['type']);
        $this->assertEquals('SECOND', $result['errors'][1]['type']);
        $this->assertEquals('THIRD', $result['errors'][2]['type']);
    }

    /*
    |--------------------------------------------------------------------------
    | Result Structure Contract Tests
    |--------------------------------------------------------------------------
    */

    public function test_import_result_has_required_keys(): void
    {
        $result = $this->service->importAll([]);

        $requiredKeys = ['stats', 'errors', 'duration', 'started_at', 'completed_at'];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $result, "Missing result key: {$key}");
        }
    }

    public function test_import_result_timestamps_are_valid(): void
    {
        $result = $this->service->importAll([]);

        $this->assertNotEmpty($result['started_at']);
        $this->assertNotEmpty($result['completed_at']);

        // Verify they can be parsed as dates
        $startedAt = \Carbon\Carbon::parse($result['started_at']);
        $completedAt = \Carbon\Carbon::parse($result['completed_at']);

        $this->assertTrue($completedAt->greaterThanOrEqualTo($startedAt));
    }

    public function test_errors_array_is_always_present(): void
    {
        $result = $this->service->importAll([]);
        $this->assertIsArray($result['errors']);
    }
}
