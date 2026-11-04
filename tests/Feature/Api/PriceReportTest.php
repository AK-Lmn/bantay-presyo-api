<?php

use App\Models\Commodity;
use App\Models\Market;
use App\Models\PriceReport;

beforeEach(function () {
    $this->travelTo('2026-03-15 10:00:00');
    $this->commodity = Commodity::factory()->create();
    $this->market = Market::factory()->create();
});

it('requires authentication to submit a report', function () {
    $this->postJson('/api/v1/price-reports', [
        'commodity_id' => $this->commodity->id,
        'market_id' => $this->market->id,
        'price' => 52.5,
    ])
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'unauthenticated');

    expect(PriceReport::count())->toBe(0);
});

it('stores a report for the authenticated user', function () {
    $user = actingAsUser();

    $this->postJson('/api/v1/price-reports', [
        'commodity_id' => $this->commodity->id,
        'market_id' => $this->market->id,
        'price' => 52.5,
    ])
        ->assertCreated()
        ->assertJsonPath('data.price', 52.5)
        ->assertJsonPath('data.reported_at', '2026-03-15T10:00:00Z')
        ->assertJsonPath('data.commodity.id', $this->commodity->id)
        ->assertJsonPath('data.market.id', $this->market->id)
        ->assertJsonPath('data.user_id', $user->id);
});

it('validates price report input', function (array $override, string $field) {
    actingAsUser();

    $payload = array_merge([
        'commodity_id' => $this->commodity->id,
        'market_id' => $this->market->id,
        'price' => 52.5,
    ], $override);

    $this->postJson('/api/v1/price-reports', $payload)
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'validation_failed')
        ->assertJsonStructure(['error' => ['details' => [$field]]]);
})->with([
    'missing price' => [['price' => null], 'price'],
    'zero price' => [['price' => 0], 'price'],
    'negative price' => [['price' => -10], 'price'],
    'too many decimals' => [['price' => 10.555], 'price'],
    'unknown commodity' => [['commodity_id' => 9999], 'commodity_id'],
    'unknown market' => [['market_id' => 9999], 'market_id'],
    'future date' => [['reported_at' => '2026-03-16 08:00:00'], 'reported_at'],
]);

it('filters reports by commodity, market and date range', function () {
    $otherMarket = Market::factory()->create();

    $match = PriceReport::factory()->create([
        'commodity_id' => $this->commodity->id,
        'market_id' => $this->market->id,
        'reported_at' => '2026-03-10 09:00:00',
    ]);
    PriceReport::factory()->create([
        'commodity_id' => $this->commodity->id,
        'market_id' => $otherMarket->id,
        'reported_at' => '2026-03-10 09:00:00',
    ]);
    PriceReport::factory()->create([
        'commodity_id' => $this->commodity->id,
        'market_id' => $this->market->id,
        'reported_at' => '2026-03-01 09:00:00',
    ]);

    $query = http_build_query([
        'commodity_id' => $this->commodity->id,
        'market_id' => $this->market->id,
        'from' => '2026-03-05',
        'to' => '2026-03-10',
    ]);

    $this->getJson("/api/v1/price-reports?{$query}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id)
        ->assertJsonPath('meta.total', 1);
});

it('rejects a date range that ends before it starts', function () {
    $this->getJson('/api/v1/price-reports?from=2026-03-10&to=2026-03-01')
        ->assertUnprocessable()
        ->assertJsonStructure(['error' => ['details' => ['to']]]);
});
