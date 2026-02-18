<?php

namespace Database\Factories;

use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Zone>
 */
class ZoneFactory extends Factory
{
    protected $model = Zone::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->city() . ' Zone',
            'code' => strtoupper($this->faker->lexify('ZN???')),
            'description' => $this->faker->sentence(),
            'zone_manager_id' => null,
            'is_active' => true,
            'metadata' => [],
        ];
    }
}
