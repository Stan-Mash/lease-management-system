<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\Landlord;
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
            'name' => $this->faker->streetName() . ' Building',
            'property_code' => $this->faker->unique()->bothify('###?'),
            'location' => $this->faker->address(),
            'zone' => $this->faker->randomElement(['A', 'B', 'C', 'D', 'E']),
            'management_commission' => $this->faker->numberBetween(5, 15),
        ];
    }
}
