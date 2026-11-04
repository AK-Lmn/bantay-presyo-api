<?php

use App\Models\Commodity;

it('lists commodities with pagination meta', function () {
    Commodity::factory()->count(25)->create();

    $this->getJson('/api/v1/commodities?per_page=10&page=2')
        ->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('meta', ['page' => 2, 'per_page' => 10, 'total' => 25]);
});

it('filters commodities by category and name', function () {
    Commodity::factory()->create(['name' => 'Pork kasim', 'category' => 'Meat']);
    Commodity::factory()->create(['name' => 'Pork liempo', 'category' => 'Meat']);
    Commodity::factory()->create(['name' => 'Well-milled rice', 'category' => 'Rice']);

    $this->getJson('/api/v1/commodities?category=Meat&q=kasim')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Pork kasim');
});

it('returns the error envelope for a missing commodity', function () {
    $this->getJson('/api/v1/commodities/999')
        ->assertNotFound()
        ->assertExactJson(['error' => [
            'code' => 'not_found',
            'message' => 'The requested resource was not found.',
            'details' => null,
        ]]);
});

it('only lets admins manage commodities', function () {
    $payload = ['name' => 'Galunggong', 'category' => 'Fish', 'unit' => 'kg'];

    $this->postJson('/api/v1/commodities', $payload)->assertUnauthorized();

    actingAsUser();
    $this->postJson('/api/v1/commodities', $payload)
        ->assertForbidden()
        ->assertJsonPath('error.code', 'forbidden');

    actingAsAdmin();
    $this->postJson('/api/v1/commodities', $payload)
        ->assertCreated()
        ->assertJsonPath('data.name', 'Galunggong');

    $this->assertDatabaseHas('commodities', $payload);
});

it('lets admins update and delete markets', function () {
    actingAsAdmin();

    $id = $this->postJson('/api/v1/markets', ['name' => 'Carbon Market', 'city' => 'Cebu City', 'region' => 'Region VII'])
        ->assertCreated()
        ->json('data.id');

    $this->putJson("/api/v1/markets/{$id}", ['region' => 'Central Visayas'])
        ->assertOk()
        ->assertJsonPath('data.region', 'Central Visayas');

    $this->deleteJson("/api/v1/markets/{$id}")->assertNoContent();
    $this->assertDatabaseMissing('markets', ['id' => $id]);
});
