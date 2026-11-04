<?php

namespace Database\Factories;

use App\Models\Commodity;
use App\Models\Srp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Srp>
 */
class SrpFactory extends Factory
{
    public function definition(): array
    {
        return [
            'commodity_id' => Commodity::factory(),
            'price' => fake()->randomFloat(2, 20, 400),
            'effective_date' => fake()->unique()->dateTimeBetween('-1 year', '-1 day')->format('Y-m-d'),
        ];
    }
}
