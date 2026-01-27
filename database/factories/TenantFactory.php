<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone_number' => '0' . $this->faker->numerify('#########'),
            'id_number' => $this->faker->numerify('########'),
            'occupation' => $this->faker->jobTitle(),
            'employer_name' => $this->faker->company(),
            'kra_pin' => $this->faker->numerify('########'),
            'notification_preference' => 'SMS',
        ];
    }
}
