<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->error('Sample data and test accounts are not seeded in production.');

            return;
        }

        User::query()->firstOrCreate(
            ['email' => 'admin@bantaypresyo.test'],
            ['name' => 'Admin', 'password' => 'password'],
        )->forceFill(['is_admin' => true, 'email_verified_at' => now()])->save();

        User::query()->firstOrCreate(
            ['email' => 'reporter@bantaypresyo.test'],
            ['name' => 'Juan Reporter', 'password' => 'password'],
        )->forceFill(['email_verified_at' => now()])->save();

        $this->call([
            CommoditySeeder::class,
            MarketSeeder::class,
            SrpSeeder::class,
            PriceReportSeeder::class,
        ]);
    }
}
