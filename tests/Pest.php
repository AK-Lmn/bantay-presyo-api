<?php

use App\Models\Commodity;
use App\Models\Market;
use App\Models\PriceReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

function actingAsUser(): User
{
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    return $user;
}

function actingAsAdmin(): User
{
    $user = User::factory()->admin()->create();
    Sanctum::actingAs($user);

    return $user;
}

function reportPrice(Commodity $commodity, Market $market, float $price, string $reportedAt): PriceReport
{
    return PriceReport::factory()->create([
        'commodity_id' => $commodity->id,
        'market_id' => $market->id,
        'price' => $price,
        'reported_at' => $reportedAt,
    ]);
}
