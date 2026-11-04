<?php

namespace Database\Factories;

use App\Models\Commodity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Commodity>
 */
class CommodityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'category' => fake()->randomElement(['Rice', 'Meat', 'Fish', 'Vegetables', 'Basic Necessities']),
            'unit' => fake()->randomElement(['kg', 'pc', 'pack', 'can']),
        ];
    }
}
