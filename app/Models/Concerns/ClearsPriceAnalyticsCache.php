<?php

namespace App\Models\Concerns;

use App\Services\PriceAnalyticsService;

trait ClearsPriceAnalyticsCache
{
    protected static function bootClearsPriceAnalyticsCache(): void
    {
        static::saved(fn () => PriceAnalyticsService::clearCache());
        static::deleted(fn () => PriceAnalyticsService::clearCache());
    }
}
