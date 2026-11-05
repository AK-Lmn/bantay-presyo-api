<?php

return [

    // Reports more than this many percent above the SRP in effect are flagged for review.
    'flag_threshold_percent' => (float) env('PRICE_FLAG_THRESHOLD_PERCENT', 30),

    'reports_per_minute' => (int) env('PRICE_REPORTS_PER_MINUTE', 10),

    'reports_per_day' => (int) env('PRICE_REPORTS_PER_DAY', 200),

    // Trend and compare responses are cached; any change to reports or SRPs clears them.
    'analytics_cache_seconds' => (int) env('PRICE_ANALYTICS_CACHE_SECONDS', 300),

];
