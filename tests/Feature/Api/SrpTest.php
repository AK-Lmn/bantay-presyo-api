<?php

use App\Models\Commodity;
use App\Models\Srp;

it('lets admins publish an SRP once per commodity and date', function () {
    $commodity = Commodity::factory()->create();
    actingAsAdmin();

    $payload = ['commodity_id' => $commodity->id, 'price' => 43.00, 'effective_date' => '2026-03-01'];

    $this->postJson('/api/v1/srps', $payload)
        ->assertCreated()
        ->assertJsonPath('data.price', 43)
        ->assertJsonPath('data.effective_date', '2026-03-01');

    $this->postJson('/api/v1/srps', $payload)
        ->assertUnprocessable()
        ->assertJsonPath('error.details.effective_date.0', 'An SRP already exists for this commodity on that date.');
});

it('lists SRPs for a commodity, newest first', function () {
    $commodity = Commodity::factory()->create();
    Srp::factory()->for($commodity)->create(['effective_date' => '2026-01-01']);
    Srp::factory()->for($commodity)->create(['effective_date' => '2026-03-01']);
    Srp::factory()->create();

    $this->getJson("/api/v1/srps?commodity_id={$commodity->id}")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.effective_date', '2026-03-01');
});
