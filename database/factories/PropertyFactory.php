<?php

namespace Database\Factories;

use App\Models\Landlord;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Property>
 */
class PropertyFactory extends Factory
{
    protected $model = Property::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'landlord_id' => Landlord::factory(),
            'property_name' => $this->faker->streetName() . ' Building',
            'reference_number' => $this->faker->unique()->bothify('###?'),
            'description' => $this->faker->address(),
            'zone' => $this->faker->randomElement(['A', 'B', 'C', 'D', 'E']),
            'commission' => $this->faker->numberBetween(5, 15),
        ];
    }
}
