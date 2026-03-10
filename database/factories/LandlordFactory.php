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
            'names' => $this->faker->name(),
            'email_address' => $this->faker->unique()->safeEmail(),
            'mobile_number' => '0' . $this->faker->numerify('#########'),
            'national_id' => $this->faker->unique()->numerify('########'),
            'pin_number' => $this->faker->numerify('########'),
            'lan_id' => 'LL-' . $this->faker->unique()->numerify('####'),
            'bank_name' => $this->faker->company(),
            'account_number' => $this->faker->bankAccountNumber(),
            'is_active' => true,
        ];
    }
}
