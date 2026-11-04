<?php

namespace App\Services;

use App\Models\Commodity;
use App\Models\PriceReport;
use Illuminate\Support\Carbon;

class PriceAnalyticsService
{
    /**
     * Daily average, min and max price for a commodity between two dates (inclusive).
     *
     * @return list<array{date: string, average: float, min: float, max: float, report_count: int}>
     */
    public function dailyTrend(Commodity $commodity, Carbon $from, Carbon $to, ?int $marketId = null): array
    {
        return PriceReport::query()
            ->toBase()
            ->selectRaw('DATE(reported_at) as day')
            ->selectRaw('AVG(price) as average, MIN(price) as min, MAX(price) as max, COUNT(*) as report_count')
            ->where('commodity_id', $commodity->id)
            ->when($marketId, fn ($query) => $query->where('market_id', $marketId))
            ->whereBetween('reported_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->groupByRaw('DATE(reported_at)')
            ->orderBy('day')
            ->get()
            ->map(fn (object $row) => [
                'date' => Carbon::parse($row->day)->toDateString(),
                'average' => round((float) $row->average, 2),
                'min' => round((float) $row->min, 2),
                'max' => round((float) $row->max, 2),
                'report_count' => (int) $row->report_count,
            ])
            ->values()
            ->all();
    }

    /**
     * Average reported price over the last $days days compared with the SRP in effect today.
     *
     * @return array<string, mixed>
     */
    public function compareWithSrp(Commodity $commodity, int $days, ?int $marketId = null): array
    {
        $to = now();
        $from = $to->copy()->subDays($days - 1)->startOfDay();

        $stats = PriceReport::query()
            ->toBase()
            ->selectRaw('AVG(price) as average, COUNT(*) as report_count')
            ->where('commodity_id', $commodity->id)
            ->when($marketId, fn ($query) => $query->where('market_id', $marketId))
            ->whereBetween('reported_at', [$from, $to])
            ->first();

        $count = (int) ($stats->report_count ?? 0);
        $average = $count > 0 ? round((float) $stats->average, 2) : null;
        $srp = $commodity->currentSrp($to);
        $srpPrice = $srp !== null ? (float) $srp->price : null;

        $difference = null;
        $percent = null;

        if ($average !== null && $srpPrice !== null) {
            $difference = round($average - $srpPrice, 2);
            $percent = self::percentDifference($average, $srpPrice);
        }

        return [
            'commodity_id' => $commodity->id,
            'market_id' => $marketId,
            'window' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'days' => $days,
            ],
            'average_price' => $average,
            'report_count' => $count,
            'srp' => $srp === null ? null : [
                'price' => $srpPrice,
                'effective_date' => $srp->effective_date->toDateString(),
            ],
            'difference' => $difference,
            'percent_difference' => $percent,
            'status' => self::status($percent),
        ];
    }

    public static function percentDifference(float $price, float $srp): float
    {
        if ($srp <= 0) {
            throw new \InvalidArgumentException('SRP must be greater than zero.');
        }

        return round(($price - $srp) / $srp * 100, 2);
    }

    public static function status(?float $percent): ?string
    {
        return match (true) {
            $percent === null => null,
            $percent > 0 => 'above_srp',
            $percent < 0 => 'below_srp',
            default => 'at_srp',
        };
    }
}
