<?php

namespace Database\Factories;

use App\Models\Lease;
use App\Models\Landlord;
use App\Models\Tenant;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lease>
 */
class LeaseFactory extends Factory
{
    protected $model = Lease::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 year', 'now');
        $endDate = $this->faker->dateTimeBetween($startDate, '+2 years');

        return [
            'reference_number' => 'LSE-' . $this->faker->unique()->bothify('###-#'),
            'source' => $this->faker->randomElement(['chabrin', 'landlord']),
            'lease_type' => $this->faker->randomElement(['commercial', 'residential_micro', 'residential_standard']),
            'signing_mode' => $this->faker->randomElement(['digital', 'physical']),
            'workflow_state' => $this->faker->randomElement(['DRAFT', 'PENDING_SIGNATURE', 'ACTIVE', 'EXPIRED']),
            'tenant_id' => Tenant::factory(),
            'unit_id' => Unit::factory(),
            'property_id' => Property::factory(),
            'landlord_id' => Landlord::factory(),
            'zone' => $this->faker->randomElement(['A', 'B', 'C', 'D', 'E', 'F', 'G']),
            'monthly_rent' => $this->faker->numberBetween(10000, 100000),
            'deposit_amount' => $this->faker->numberBetween(10000, 50000),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'requires_lawyer' => $this->faker->boolean(),
            'document_version' => 1,
            'signature_latitude' => $this->faker->latitude(),
            'signature_longitude' => $this->faker->longitude(),
            'signing_location_type' => $this->faker->randomElement(['on_site', 'off_site']),
        ];
    }
}

