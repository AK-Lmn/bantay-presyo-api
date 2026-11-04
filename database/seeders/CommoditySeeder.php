<?php

namespace Database\Seeders;

use App\Models\Commodity;
use Illuminate\Database\Seeder;

class CommoditySeeder extends Seeder
{
    /**
     * @var list<array{name: string, category: string, unit: string}>
     */
    public const COMMODITIES = [
        ['name' => 'Well-milled rice', 'category' => 'Rice', 'unit' => 'kg'],
        ['name' => 'Regular-milled rice', 'category' => 'Rice', 'unit' => 'kg'],
        ['name' => 'Pork kasim', 'category' => 'Meat', 'unit' => 'kg'],
        ['name' => 'Pork liempo', 'category' => 'Meat', 'unit' => 'kg'],
        ['name' => 'Whole chicken', 'category' => 'Poultry', 'unit' => 'kg'],
        ['name' => 'Chicken egg (medium)', 'category' => 'Poultry', 'unit' => 'pc'],
        ['name' => 'Galunggong', 'category' => 'Fish', 'unit' => 'kg'],
        ['name' => 'Tilapia', 'category' => 'Fish', 'unit' => 'kg'],
        ['name' => 'Red onion', 'category' => 'Vegetables', 'unit' => 'kg'],
        ['name' => 'Garlic (imported)', 'category' => 'Vegetables', 'unit' => 'kg'],
        ['name' => 'Refined sugar', 'category' => 'Basic Necessities', 'unit' => 'kg'],
        ['name' => 'Canned sardines (155 g)', 'category' => 'Basic Necessities', 'unit' => 'can'],
        ['name' => 'Instant noodles (55 g)', 'category' => 'Basic Necessities', 'unit' => 'pack'],
    ];

    public function run(): void
    {
        foreach (self::COMMODITIES as $commodity) {
            Commodity::query()->firstOrCreate(['name' => $commodity['name']], $commodity);
        }
    }
}
