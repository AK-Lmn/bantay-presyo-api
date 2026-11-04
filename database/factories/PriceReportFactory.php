<?php

namespace Database\Factories;

use App\Models\Commodity;
use App\Models\Market;
use App\Models\PriceReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PriceReport>
 */
class PriceReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'commodity_id' => Commodity::factory(),
            'market_id' => Market::factory(),
            'user_id' => User::factory(),
            'price' => fake()->randomFloat(2, 20, 400),
            'reported_at' => fake()->dateTimeBetween('-30 days'),
        ];
    }
}
