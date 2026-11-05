<?php

use App\Models\Commodity;
use App\Models\Market;
use App\Models\PriceReport;
use App\Models\Srp;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->travelTo('2026-03-15 10:00:00');
    $this->commodity = Commodity::factory()->create();
    $this->market = Market::factory()->create();
    Srp::factory()->for($this->commodity)->create(['price' => 100.00, 'effective_date' => '2026-03-01']);
});

function submitPrice(float $price): TestResponse
{
    return test()->postJson('/api/v1/price-reports', [
        'commodity_id' => test()->commodity->id,
        'market_id' => test()->market->id,
        'price' => $price,
    ]);
}

it('flags reports more than 30 percent above the SRP', function (float $price, bool $flagged) {
    actingAsUser();

    submitPrice($price)
        ->assertCreated()
        ->assertJsonPath('data.srp_price', 100)
        ->assertJsonPath('data.flagged', $flagged);
})->with([
    'well above' => [150.00, true],
    'just above the line' => [130.01, true],
    'exactly 30 percent' => [130.00, false],
    'below SRP' => [80.00, false],
]);

it('uses the SRP in effect when the price was seen', function () {
    Srp::factory()->for($this->commodity)->create(['price' => 200.00, 'effective_date' => '2026-03-10']);
    actingAsUser();

    $this->postJson('/api/v1/price-reports', [
        'commodity_id' => $this->commodity->id,
        'market_id' => $this->market->id,
        'price' => 150,
        'reported_at' => '2026-03-05 08:00:00',
    ])
        ->assertCreated()
        ->assertJsonPath('data.srp_price', 100)
        ->assertJsonPath('data.flagged', true);
});

it('does not flag reports for commodities without an SRP', function () {
    actingAsUser();

    $this->postJson('/api/v1/price-reports', [
        'commodity_id' => Commodity::factory()->create()->id,
        'market_id' => $this->market->id,
        'price' => 999,
    ])
        ->assertCreated()
        ->assertJsonPath('data.srp_price', null)
        ->assertJsonPath('data.flagged', false);
});

it('filters reports by flag', function () {
    $flagged = PriceReport::factory()->create(['is_flagged' => true]);
    PriceReport::factory()->create(['is_flagged' => false]);

    $this->getJson('/api/v1/price-reports?flagged=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $flagged->id);
});

it('lets admins hide a report so it drops out of listings and stats', function () {
    reportPrice($this->commodity, $this->market, 100.00, '2026-03-14 08:00:00');
    $bad = reportPrice($this->commodity, $this->market, 1000.00, '2026-03-14 09:00:00');

    $this->getJson("/api/v1/commodities/{$this->commodity->id}/compare")
        ->assertJsonPath('data.average_price', 550);

    actingAsAdmin();
    $this->postJson("/api/v1/price-reports/{$bad->id}/hide", ['reason' => 'Price per sack, not per kg.'])
        ->assertOk()
        ->assertJsonPath('data.hidden_reason', 'Price per sack, not per kg.');

    $this->getJson('/api/v1/price-reports')->assertJsonCount(1, 'data');
    $this->getJson("/api/v1/commodities/{$this->commodity->id}/compare")
        ->assertJsonPath('data.average_price', 100)
        ->assertJsonPath('data.report_count', 1);
    $this->getJson("/api/v1/commodities/{$this->commodity->id}/trend?from=2026-03-14&to=2026-03-14")
        ->assertJsonPath('data.days.0.max', 100);

    $this->getJson('/api/v1/price-reports?include_hidden=1')->assertJsonCount(2, 'data');

    $this->deleteJson("/api/v1/price-reports/{$bad->id}/hide")->assertOk()->assertJsonPath('data.hidden_at', null);
    $this->getJson('/api/v1/price-reports')->assertJsonCount(2, 'data');
});

it('only shows hidden reports to admins', function () {
    $report = PriceReport::factory()->create();
    $report->forceFill(['hidden_at' => now()])->save();

    $this->getJson('/api/v1/price-reports?include_hidden=1')->assertJsonCount(0, 'data');

    actingAsUser();
    $this->getJson('/api/v1/price-reports?include_hidden=1')->assertJsonCount(0, 'data');
    $this->postJson("/api/v1/price-reports/{$report->id}/hide")->assertForbidden();
});

it('limits how many reports a user can submit per minute', function () {
    config(['prices.reports_per_minute' => 2]);
    actingAsUser();

    submitPrice(100)->assertCreated();
    submitPrice(100)->assertCreated();
    submitPrice(100)
        ->assertStatus(429)
        ->assertJsonPath('error.code', 'too_many_requests');

    actingAsUser();
    submitPrice(100)->assertCreated();
});
