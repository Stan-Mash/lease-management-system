<?php

namespace Database\Factories;

use App\Models\Unit;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'unit_number' => $this->faker->numerify('###'),
            'status' => $this->faker->randomElement(['VACANT', 'OCCUPIED', 'MAINTENANCE']),
            'type' => $this->faker->randomElement(['studio', 'one_bedroom', 'two_bedroom', 'three_bedroom']),
            'market_rent' => $this->faker->numberBetween(10000, 50000),
            'deposit_required' => $this->faker->numberBetween(10000, 50000),
        ];
    }
}
