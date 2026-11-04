<?php

namespace Database\Factories;

use App\Models\Market;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Market>
 */
class MarketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->lastName().' Public Market',
            'city' => fake()->city(),
            'region' => fake()->randomElement(['NCR', 'Region III', 'Region IV-A', 'Region VII', 'Region XI']),
        ];
    }
}
