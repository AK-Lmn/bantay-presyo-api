<?php

use App\Models\Commodity;
use App\Models\Market;
use App\Models\Srp;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('promotes and demotes admins from the command line', function () {
    $user = User::factory()->create(['email' => 'ana@example.com']);
    $user->createToken('phone');

    $this->artisan('users:admin', ['email' => 'ana@example.com'])->assertSuccessful();
    expect($user->refresh()->is_admin)->toBeTrue()
        ->and($user->tokens()->count())->toBe(0);

    $this->artisan('users:admin', ['email' => 'ana@example.com', '--revoke' => true])->assertSuccessful();
    expect($user->refresh()->is_admin)->toBeFalse();

    $this->artisan('users:admin', ['email' => 'nobody@example.com'])->assertFailed();
});

it('returns an expiry with each token and rejects expired tokens', function () {
    $this->travelTo('2026-03-15 10:00:00');
    User::factory()->create(['email' => 'juan@example.com', 'password' => 'correct-horse']);

    $token = $this->postJson('/api/v1/auth/login', ['email' => 'juan@example.com', 'password' => 'correct-horse'])
        ->assertOk()
        ->assertJsonPath('data.expires_at', '2026-04-14T10:00:00Z')
        ->json('data.token');

    $this->travelTo('2026-04-15 10:00:00');
    app('auth')->forgetGuards();

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertUnauthorized();
});

it('refreshes cached comparisons when a new report comes in', function () {
    config(['prices.analytics_cache_seconds' => 300]);
    $this->travelTo('2026-03-15 10:00:00');
    $commodity = Commodity::factory()->create();
    $market = Market::factory()->create();
    Srp::factory()->for($commodity)->create(['price' => 50.00, 'effective_date' => '2026-03-01']);
    reportPrice($commodity, $market, 50.00, '2026-03-14 08:00:00');

    $this->getJson("/api/v1/commodities/{$commodity->id}/compare")->assertJsonPath('data.average_price', 50);

    actingAsUser();
    $this->postJson('/api/v1/price-reports', ['commodity_id' => $commodity->id, 'market_id' => $market->id, 'price' => 60])
        ->assertCreated();

    $this->getJson("/api/v1/commodities/{$commodity->id}/compare")->assertJsonPath('data.average_price', 55);
});

it('answers CORS preflight requests for allowed origins', function () {
    config(['cors.allowed_origins' => ['https://bantaypresyo.example']]);

    $this->call('OPTIONS', '/api/v1/commodities', server: [
        'HTTP_ORIGIN' => 'https://bantaypresyo.example',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
    ])
        ->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', 'https://bantaypresyo.example');
});

it('serves the API docs and a health check', function () {
    $this->get('/docs')->assertOk()->assertSee('Bantay Presyo API');
    $this->get('/docs.openapi')->assertOk();
    $this->get('/up')->assertOk();
});

it('reports unhealthy when the database is down', function () {
    config([
        'app.debug' => false,
        'database.connections.unreachable' => [...config('database.connections.pgsql'), 'port' => 1],
        'database.default' => 'unreachable',
    ]);

    $this->get('/up')->assertServerError();

    config(['database.default' => 'pgsql']);
})->skip(fn () => DB::connection()->getDriverName() !== 'pgsql');

it('refuses to seed sample data in production', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    expect(User::count())->toBe(0)
        ->and(Commodity::count())->toBe(0);
});
