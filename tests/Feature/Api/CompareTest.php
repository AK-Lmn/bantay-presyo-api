<?php

use App\Models\Commodity;
use App\Models\Market;
use App\Models\Srp;

beforeEach(function () {
    $this->travelTo('2026-03-15 12:00:00');
    $this->commodity = Commodity::factory()->create();
    $this->market = Market::factory()->create();
});

it('compares the recent average with the SRP in effect today', function () {
    Srp::factory()->for($this->commodity)->create(['price' => 40.00, 'effective_date' => '2026-01-01']);
    Srp::factory()->for($this->commodity)->create(['price' => 45.00, 'effective_date' => '2026-03-01']);
    Srp::factory()->for($this->commodity)->create(['price' => 60.00, 'effective_date' => '2026-04-01']);

    reportPrice($this->commodity, $this->market, 50.00, '2026-03-10 08:00:00');
    reportPrice($this->commodity, $this->market, 52.00, '2026-03-14 08:00:00');
    reportPrice($this->commodity, $this->market, 54.00, '2026-03-15 08:00:00');
    reportPrice($this->commodity, $this->market, 99.00, '2026-03-01 08:00:00');

    $this->getJson("/api/v1/commodities/{$this->commodity->id}/compare")
        ->assertOk()
        ->assertJsonPath('data.window', ['from' => '2026-03-09', 'to' => '2026-03-15', 'days' => 7])
        ->assertJsonPath('data.average_price', 52)
        ->assertJsonPath('data.report_count', 3)
        ->assertJsonPath('data.srp', ['price' => 45, 'effective_date' => '2026-03-01'])
        ->assertJsonPath('data.difference', 7)
        ->assertJsonPath('data.percent_difference', 15.56)
        ->assertJsonPath('data.status', 'above_srp');
});

it('reports prices below SRP as a negative difference', function () {
    Srp::factory()->for($this->commodity)->create(['price' => 50.00, 'effective_date' => '2026-03-01']);
    reportPrice($this->commodity, $this->market, 45.00, '2026-03-14 08:00:00');

    $this->getJson("/api/v1/commodities/{$this->commodity->id}/compare")
        ->assertOk()
        ->assertJsonPath('data.percent_difference', -10)
        ->assertJsonPath('data.status', 'below_srp');
});

it('returns nulls when there is no SRP or no reports', function () {
    reportPrice($this->commodity, $this->market, 45.00, '2026-03-14 08:00:00');

    $this->getJson("/api/v1/commodities/{$this->commodity->id}/compare")
        ->assertOk()
        ->assertJsonPath('data.average_price', 45)
        ->assertJsonPath('data.srp', null)
        ->assertJsonPath('data.percent_difference', null)
        ->assertJsonPath('data.status', null);

    $empty = Commodity::factory()->create();
    Srp::factory()->for($empty)->create(['price' => 50.00, 'effective_date' => '2026-03-01']);

    $this->getJson("/api/v1/commodities/{$empty->id}/compare")
        ->assertOk()
        ->assertJsonPath('data.average_price', null)
        ->assertJsonPath('data.report_count', 0)
        ->assertJsonPath('data.srp.price', 50)
        ->assertJsonPath('data.percent_difference', null);
});

it('validates the days window', function () {
    $this->getJson("/api/v1/commodities/{$this->commodity->id}/compare?days=0")
        ->assertUnprocessable()
        ->assertJsonStructure(['error' => ['details' => ['days']]]);
});
