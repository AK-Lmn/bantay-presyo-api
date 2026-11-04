<?php

use App\Models\Commodity;
use App\Models\Market;

beforeEach(function () {
    $this->travelTo('2026-03-15 12:00:00');
    $this->commodity = Commodity::factory()->create();
    $this->market = Market::factory()->create();
});

it('aggregates daily average, min and max', function () {
    reportPrice($this->commodity, $this->market, 50.00, '2026-03-10 06:00:00');
    reportPrice($this->commodity, $this->market, 55.00, '2026-03-10 12:30:00');
    reportPrice($this->commodity, $this->market, 60.25, '2026-03-10 23:59:59');
    reportPrice($this->commodity, $this->market, 48.00, '2026-03-11 08:00:00');
    reportPrice(Commodity::factory()->create(), $this->market, 999.00, '2026-03-10 08:00:00');

    $this->getJson("/api/v1/commodities/{$this->commodity->id}/trend?from=2026-03-10&to=2026-03-11")
        ->assertOk()
        ->assertJsonPath('data.from', '2026-03-10')
        ->assertJsonPath('data.to', '2026-03-11')
        ->assertJsonPath('data.days', [
            ['date' => '2026-03-10', 'average' => 55.08, 'min' => 50, 'max' => 60.25, 'report_count' => 3],
            ['date' => '2026-03-11', 'average' => 48, 'min' => 48, 'max' => 48, 'report_count' => 1],
        ]);
});

it('excludes reports outside the range and can filter by market', function () {
    $other = Market::factory()->create();

    reportPrice($this->commodity, $this->market, 40.00, '2026-03-09 23:59:59');
    reportPrice($this->commodity, $this->market, 50.00, '2026-03-12 10:00:00');
    reportPrice($this->commodity, $other, 70.00, '2026-03-12 10:00:00');
    reportPrice($this->commodity, $this->market, 40.00, '2026-03-13 00:00:00');

    $this->getJson("/api/v1/commodities/{$this->commodity->id}/trend?from=2026-03-10&to=2026-03-12&market_id={$this->market->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data.days')
        ->assertJsonPath('data.days.0.average', 50);
});

it('defaults to the last 30 days', function () {
    reportPrice($this->commodity, $this->market, 50.00, '2026-02-14 10:00:00');
    reportPrice($this->commodity, $this->market, 52.00, '2026-02-13 10:00:00');

    $this->getJson("/api/v1/commodities/{$this->commodity->id}/trend")
        ->assertOk()
        ->assertJsonPath('data.from', '2026-02-14')
        ->assertJsonPath('data.to', '2026-03-15')
        ->assertJsonCount(1, 'data.days');
});

it('rejects ranges longer than a year', function () {
    $this->getJson("/api/v1/commodities/{$this->commodity->id}/trend?from=2025-01-01&to=2026-03-01")
        ->assertUnprocessable()
        ->assertJsonStructure(['error' => ['details' => ['from']]]);
});
