<?php

use App\Services\PriceAnalyticsService;

it('computes the percent difference against SRP', function (float $price, float $srp, float $expected) {
    expect(PriceAnalyticsService::percentDifference($price, $srp))->toBe($expected);
})->with([
    'above' => [55.00, 50.00, 10.0],
    'below' => [45.00, 50.00, -10.0],
    'equal' => [50.00, 50.00, 0.0],
    'rounded' => [52.00, 45.00, 15.56],
]);

it('refuses a zero SRP', function () {
    PriceAnalyticsService::percentDifference(10, 0);
})->throws(InvalidArgumentException::class);

it('labels the comparison status', function () {
    expect(PriceAnalyticsService::status(12.5))->toBe('above_srp')
        ->and(PriceAnalyticsService::status(-3.0))->toBe('below_srp')
        ->and(PriceAnalyticsService::status(0.0))->toBe('at_srp')
        ->and(PriceAnalyticsService::status(null))->toBeNull();
});
