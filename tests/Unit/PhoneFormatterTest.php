<?php

namespace Tests\Unit;

use App\Support\PhoneFormatter;
use PHPUnit\Framework\TestCase;

class PhoneFormatterTest extends TestCase
{
    public function test_formats_local_number_starting_with_zero(): void
    {
        $result = PhoneFormatter::toInternational('0712345678');

        $this->assertEquals('+254712345678', $result);
    }

    public function test_formats_number_without_country_code(): void
    {
        $result = PhoneFormatter::toInternational('712345678');

        $this->assertEquals('+254712345678', $result);
    }

    public function test_preserves_international_number_with_plus(): void
    {
        $result = PhoneFormatter::toInternational('+254712345678');

        $this->assertEquals('+254712345678', $result);
    }

    public function test_adds_plus_to_international_number(): void
    {
        $result = PhoneFormatter::toInternational('254712345678');

        $this->assertEquals('+254712345678', $result);
    }

    public function test_removes_spaces_and_dashes(): void
    {
        $result = PhoneFormatter::toInternational('0712 345 678');

        $this->assertEquals('+254712345678', $result);
    }

    public function test_removes_brackets(): void
    {
        $result = PhoneFormatter::toInternational('(0712) 345-678');

        $this->assertEquals('+254712345678', $result);
    }

    public function test_formats_for_display(): void
    {
        $result = PhoneFormatter::forDisplay('0712345678');

        $this->assertEquals('+254 712 345 678', $result);
    }

    public function test_masks_phone_number(): void
    {
        $result = PhoneFormatter::mask('0712345678');

        $this->assertEquals('+254****678', $result);
    }

    public function test_masks_short_number(): void
    {
        $result = PhoneFormatter::mask('12345');

        $this->assertStringEndsWith('45', $result);
    }

    public function test_validates_valid_phone_number(): void
    {
        $this->assertTrue(PhoneFormatter::isValid('0712345678'));
        $this->assertTrue(PhoneFormatter::isValid('+254712345678'));
        $this->assertTrue(PhoneFormatter::isValid('254712345678'));
    }

    public function test_invalidates_short_phone_number(): void
    {
        $this->assertFalse(PhoneFormatter::isValid('12345'));
    }

    public function test_invalidates_too_long_phone_number(): void
    {
        $this->assertFalse(PhoneFormatter::isValid('123456789012345678'));
    }

    public function test_extracts_country_code(): void
    {
        $result = PhoneFormatter::extractCountryCode('+254712345678');

        $this->assertEquals('254', $result);
    }

    public function test_uses_custom_country_code(): void
    {
        $result = PhoneFormatter::toInternational('0712345678', '255');

        $this->assertEquals('+255712345678', $result);
    }
}
