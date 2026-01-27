<?php

namespace App\Services;

use App\Models\Lease;
use App\Models\Tenant;
use App\Models\Landlord;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Support\Carbon;

/**
 * Generates sample/mock lease data for template previewing
 */
class SampleLeaseDataService
{
    /**
     * Generate complete sample lease data for template preview
     *
     * @param string $leaseType residential_major|residential_micro|commercial
     * @return array
     */
    public static function generate(string $leaseType = 'residential_major'): array
    {
        return [
            'lease' => self::createSampleLease($leaseType),
            'tenant' => self::createSampleTenant($leaseType),
            'landlord' => self::createSampleLandlord(),
            'property' => self::createSampleProperty($leaseType),
            'unit' => self::createSampleUnit($leaseType),
            'today' => now()->format('d/m/Y'),
            'qrCode' => self::generateSampleQrCode(),
            'qr_code' => self::generateSampleQrCode(),
            // Helper functions
            'formatMoney' => fn($amount) => 'KES ' . number_format($amount, 2),
            'formatDate' => fn($date, $format = 'd/m/Y') => $date instanceof Carbon ? $date->format($format) : $date,
        ];
    }

    /**
     * Create sample lease object
     */
    protected static function createSampleLease(string $leaseType): object
    {
        $baseRent = match ($leaseType) {
            'residential_major' => 25000,
            'residential_micro' => 8000,
            'commercial' => 75000,
            default => 20000,
        };

        $duration = match ($leaseType) {
            'residential_major' => 12,
            'residential_micro' => 6,
            'commercial' => 60, // 5 years
            default => 12,
        };

        $lease = new \stdClass();
        $lease->id = 999;
        $lease->reference_number = 'SAMPLE-' . strtoupper($leaseType) . '-2026-001';
        $lease->lease_type = $leaseType;
        $lease->monthly_rent = $baseRent;
        $lease->deposit_amount = $baseRent * 2; // 2 months deposit
        $lease->water_deposit = 1000;
        $lease->start_date = now();
        $lease->end_date = now()->addMonths($duration);
        $lease->duration_months = $duration;
        $lease->workflow_state = 'approved';
        $lease->signing_mode = 'digital';
        $lease->rent_review_period = $leaseType === 'commercial' ? 2 : null;
        $lease->rent_review_percentage = $leaseType === 'commercial' ? 10 : null;
        $lease->template_version_used = 1;
        $lease->created_at = now();

        return $lease;
    }

    /**
     * Create sample tenant object
     */
    protected static function createSampleTenant(string $leaseType): object
    {
        $tenant = new \stdClass();

        if ($leaseType === 'commercial') {
            // Business tenant
            $tenant->id = 998;
            $tenant->full_name = 'TECHNOVATE SOLUTIONS LIMITED';
            $tenant->first_name = 'John';
            $tenant->last_name = 'Kamau';
            $tenant->id_number = 'P51234567'; // Company registration
            $tenant->phone = '+254 722 123 456';
            $tenant->email = 'info@technovate.co.ke';
            $tenant->address = 'Westlands, Nairobi';
            $tenant->postal_address = '12345-00100';
            $tenant->workplace = 'Self Employed';
            $tenant->next_of_kin_name = 'Mary Kamau';
            $tenant->next_of_kin_phone = '+254 733 987 654';
            $tenant->next_of_kin_relationship = 'Spouse';
        } else {
            // Individual tenant
            $tenant->id = 998;
            $tenant->full_name = 'JOHN MWANGI KARIUKI';
            $tenant->first_name = 'John';
            $tenant->last_name = 'Kariuki';
            $tenant->id_number = '12345678';
            $tenant->phone = '+254 712 345 678';
            $tenant->email = 'john.kariuki@email.com';
            $tenant->address = '123 Kenyatta Avenue, Nairobi';
            $tenant->postal_address = '54321-00200';
            $tenant->workplace = 'Kenya Commercial Bank';
            $tenant->next_of_kin_name = 'Jane Wanjiru Kariuki';
            $tenant->next_of_kin_phone = '+254 722 987 654';
            $tenant->next_of_kin_relationship = 'Spouse';
        }

        $tenant->created_at = now();

        return $tenant;
    }

    /**
     * Create sample landlord object
     */
    protected static function createSampleLandlord(): object
    {
        $landlord = new \stdClass();
        $landlord->id = 997;
        $landlord->name = 'SKYLINE PROPERTIES LIMITED';
        $landlord->contact_person = 'Peter Omondi';
        $landlord->contact_designation = 'Managing Director';
        $landlord->phone = '+254 720 111 222';
        $landlord->email = 'info@skylineproperties.co.ke';
        $landlord->postal_address = '98765-00100';
        $landlord->physical_address = 'Kilimani, Nairobi';
        $landlord->created_at = now();

        return $landlord;
    }

    /**
     * Create sample property object
     */
    protected static function createSampleProperty(string $leaseType): object
    {
        $property = new \stdClass();
        $property->id = 996;

        if ($leaseType === 'commercial') {
            $property->name = 'GREENVIEW BUSINESS PLAZA';
            $property->property_type = 'commercial';
            $property->plot_number = 'L.R. NO. 209/12345';
            $property->lr_number = '209/12345';
        } else {
            $property->name = 'SUNSET VIEW APARTMENTS';
            $property->property_type = 'residential';
            $property->plot_number = 'L.R. NO. 209/8765';
            $property->lr_number = '209/8765';
        }

        $property->address = 'Kilimani Road, Nairobi';
        $property->county = 'Nairobi';
        $property->sub_county = 'Westlands';
        $property->created_at = now();

        return $property;
    }

    /**
     * Create sample unit object
     */
    protected static function createSampleUnit(string $leaseType): object
    {
        $unit = new \stdClass();
        $unit->id = 995;

        if ($leaseType === 'commercial') {
            $unit->unit_number = 'SHOP G12';
            $unit->unit_type = 'shop';
            $unit->floor = 'Ground Floor';
            $unit->size_sqm = 85.5;
        } elseif ($leaseType === 'residential_micro') {
            $unit->unit_number = 'ROOM 204';
            $unit->unit_type = 'bedsitter';
            $unit->floor = '2nd Floor';
            $unit->bedrooms = 1;
            $unit->bathrooms = 1;
            $unit->size_sqm = 25;
        } else {
            $unit->unit_number = 'FLAT 3B';
            $unit->unit_type = '2-bedroom';
            $unit->floor = '3rd Floor';
            $unit->bedrooms = 2;
            $unit->bathrooms = 2;
            $unit->size_sqm = 75;
        }

        $unit->status = 'occupied';
        $unit->created_at = now();

        return $unit;
    }

    /**
     * Generate sample QR code as base64 data URI
     */
    protected static function generateSampleQrCode(): ?string
    {
        // Generate a simple sample QR code placeholder
        // In production, this would use the actual QRCodeService
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">
            <rect width="100" height="100" fill="white"/>
            <rect x="10" y="10" width="10" height="10" fill="black"/>
            <rect x="30" y="10" width="10" height="10" fill="black"/>
            <rect x="50" y="10" width="10" height="10" fill="black"/>
            <rect x="70" y="10" width="10" height="10" fill="black"/>
            <rect x="10" y="30" width="10" height="10" fill="black"/>
            <rect x="70" y="30" width="10" height="10" fill="black"/>
            <rect x="10" y="50" width="10" height="10" fill="black"/>
            <rect x="30" y="50" width="10" height="10" fill="black"/>
            <rect x="50" y="50" width="10" height="10" fill="black"/>
            <rect x="70" y="50" width="10" height="10" fill="black"/>
            <rect x="10" y="70" width="10" height="10" fill="black"/>
            <rect x="30" y="70" width="10" height="10" fill="black"/>
            <rect x="50" y="70" width="10" height="10" fill="black"/>
            <rect x="70" y="70" width="10" height="10" fill="black"/>
            <text x="50" y="95" font-size="8" text-anchor="middle" fill="black">SAMPLE</text>
        </svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
