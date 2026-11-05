<?php

namespace Database\Seeders;

use App\Models\Commodity;
use App\Models\Market;
use App\Models\PriceReport;
use App\Models\User;
use App\Services\PriceAnalyticsService;
use Illuminate\Database\Seeder;

class PriceReportSeeder extends Seeder
{
    public function run(): void
    {
        if (PriceReport::query()->exists()) {
            return;
        }

        mt_srand(2026);

        $reporter = User::query()->where('email', 'reporter@bantaypresyo.test')->firstOrFail();
        $markets = Market::query()->pluck('id')->all();
        $rows = [];

        foreach (Commodity::query()->get() as $commodity) {
            $current = $commodity->currentSrp();
            $srpPrice = $current === null ? null : (float) $current->price;
            $srp = $srpPrice ?? 100.0;

            for ($day = 29; $day >= 0; $day--) {
                foreach ($this->pick($markets, 3) as $marketId) {
                    // Most reports land between 10% below and 35% above SRP.
                    $price = round($srp * mt_rand(90, 135) / 100, 2);
                    $reportedAt = now()->subDays($day)->setTime(mt_rand(6, 18), mt_rand(0, 59));

                    $rows[] = [
                        'commodity_id' => $commodity->id,
                        'market_id' => $marketId,
                        'user_id' => $reporter->id,
                        'price' => $price,
                        'srp_price' => $srpPrice,
                        'is_flagged' => PriceAnalyticsService::exceedsFlagThreshold($price, $srpPrice),
                        'reported_at' => $reportedAt,
                        'created_at' => $reportedAt,
                        'updated_at' => $reportedAt,
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            PriceReport::query()->insert($chunk);
        }
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    private function pick(array $ids, int $count): array
    {
        $keys = (array) array_rand($ids, min($count, count($ids)));

        return array_map(fn ($key) => $ids[$key], $keys);
    }
}
