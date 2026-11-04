<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'admin@bantaypresyo.test'],
            ['name' => 'Admin', 'password' => 'password'],
        )->forceFill(['is_admin' => true])->save();

        User::query()->firstOrCreate(
            ['email' => 'reporter@bantaypresyo.test'],
            ['name' => 'Juan Reporter', 'password' => 'password'],
        );

        $this->call([
            CommoditySeeder::class,
            MarketSeeder::class,
            SrpSeeder::class,
            PriceReportSeeder::class,
        ]);
    }
}
