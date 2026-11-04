<?php

namespace Database\Seeders;

use App\Models\Commodity;
use App\Models\Srp;
use Illuminate\Database\Seeder;

class SrpSeeder extends Seeder
{
    /**
     * Sample prices for local development only, not an official bulletin.
     *
     * @var array<string, array{0: float, 1: float}>
     */
    private const PRICES = [
        'Well-milled rice' => [45.00, 43.00],
        'Regular-milled rice' => [40.00, 38.00],
        'Pork kasim' => [340.00, 350.00],
        'Pork liempo' => [390.00, 400.00],
        'Whole chicken' => [180.00, 190.00],
        'Chicken egg (medium)' => [7.50, 8.00],
        'Galunggong' => [220.00, 200.00],
        'Tilapia' => [150.00, 150.00],
        'Red onion' => [160.00, 150.00],
        'Garlic (imported)' => [130.00, 140.00],
        'Refined sugar' => [85.00, 82.00],
        'Canned sardines (155 g)' => [21.50, 22.00],
        'Instant noodles (55 g)' => [8.50, 9.00],
    ];

    public function run(): void
    {
        $previous = now()->subDays(90)->toDateString();
        $current = now()->subDays(30)->toDateString();

        foreach (self::PRICES as $name => [$oldPrice, $newPrice]) {
            $commodity = Commodity::query()->where('name', $name)->first();

            if ($commodity === null) {
                continue;
            }

            Srp::query()->firstOrCreate(
                ['commodity_id' => $commodity->id, 'effective_date' => $previous],
                ['price' => $oldPrice],
            );
            Srp::query()->firstOrCreate(
                ['commodity_id' => $commodity->id, 'effective_date' => $current],
                ['price' => $newPrice],
            );
        }
    }
}
