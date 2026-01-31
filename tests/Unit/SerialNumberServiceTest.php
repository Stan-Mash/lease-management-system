<?php

namespace Tests\Unit;

use App\Models\Lease;
use App\Services\SerialNumberService;
use Tests\TestCase;

class SerialNumberServiceTest extends TestCase
{
    public function test_generate_creates_valid_serial_number(): void
    {
        $serial = SerialNumberService::generate();
        $this->assertTrue(SerialNumberService::isValid($serial));
    }

    public function test_generate_format_is_correct(): void
    {
        $serial = SerialNumberService::generate();
        $this->assertMatchesRegularExpression('/^LSE-\d{4}-\d{4}$/', $serial);
    }

    public function test_generate_increments_sequence(): void
    {
        Lease::query()->delete(); // Clean slate

        $serial1 = SerialNumberService::generateUnique();
        $serial2 = SerialNumberService::generateUnique();

        $this->assertNotEquals($serial1, $serial2);
        $this->assertTrue(str_ends_with($serial2, '-0002'));
    }

    public function test_is_valid_accepts_correct_format(): void
    {
        $this->assertTrue(SerialNumberService::isValid('LSE-2026-0001'));
        $this->assertTrue(SerialNumberService::isValid('DOC-2025-9999'));
    }

    public function test_is_valid_rejects_incorrect_format(): void
    {
        $this->assertFalse(SerialNumberService::isValid('invalid'));
        $this->assertFalse(SerialNumberService::isValid('LSE-2026'));
        $this->assertFalse(SerialNumberService::isValid('LSE-26-0001'));
    }

    public function test_parse_extracts_components(): void
    {
        $parsed = SerialNumberService::parse('LSE-2026-0042');

        $this->assertNotNull($parsed);
        $this->assertEquals('LSE', $parsed['prefix']);
        $this->assertEquals('2026', $parsed['year']);
        $this->assertEquals('0042', $parsed['sequence']);
    }
}
