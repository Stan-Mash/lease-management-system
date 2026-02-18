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
            'names' => $this->faker->name(),
            'email_address' => $this->faker->unique()->safeEmail(),
            'mobile_number' => '0' . $this->faker->numerify('#########'),
            'national_id' => $this->faker->numerify('########'),
            'occupation' => $this->faker->jobTitle(),
            'employer_name' => $this->faker->company(),
            'pin_number' => $this->faker->numerify('########'),
            'notification_preference' => 'SMS',
        ];
    }
}
