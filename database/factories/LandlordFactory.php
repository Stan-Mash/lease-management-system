<?php

namespace Database\Factories;

use App\Models\Landlord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Landlord>
 */
class LandlordFactory extends Factory
{
    protected $model = Landlord::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => '0' . $this->faker->numerify('#########'),
            'id_number' => $this->faker->unique()->numerify('########'),
            'kra_pin' => $this->faker->numerify('########'),
            'landlord_code' => 'LL-' . $this->faker->unique()->numerify('####'),
            'bank_name' => $this->faker->company(),
            'account_number' => $this->faker->bankAccountNumber(),
            'is_active' => true,
        ];
    }
}
