<?php

namespace Database\Seeders;

use App\Models\Market;
use Illuminate\Database\Seeder;

class MarketSeeder extends Seeder
{
    public function run(): void
    {
        $markets = [
            ['name' => 'Commonwealth Market', 'city' => 'Quezon City', 'region' => 'NCR'],
            ['name' => 'Mega Q-Mart', 'city' => 'Quezon City', 'region' => 'NCR'],
            ['name' => 'Marikina Public Market', 'city' => 'Marikina', 'region' => 'NCR'],
            ['name' => 'Pasig Mega Market', 'city' => 'Pasig', 'region' => 'NCR'],
            ['name' => 'Guadalupe Public Market', 'city' => 'Makati', 'region' => 'NCR'],
            ['name' => 'San Fernando Public Market', 'city' => 'San Fernando', 'region' => 'Region III'],
            ['name' => 'Carbon Market', 'city' => 'Cebu City', 'region' => 'Region VII'],
            ['name' => 'Bankerohan Public Market', 'city' => 'Davao City', 'region' => 'Region XI'],
        ];

        foreach ($markets as $market) {
            Market::query()->firstOrCreate(
                ['name' => $market['name'], 'city' => $market['city']],
                $market,
            );
        }
    }
}
