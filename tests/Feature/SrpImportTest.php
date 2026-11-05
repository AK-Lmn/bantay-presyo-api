<?php

use App\Models\Commodity;
use App\Models\Srp;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->rice = Commodity::factory()->create(['name' => 'Well-milled rice', 'unit' => 'kg']);
    $this->pork = Commodity::factory()->create(['name' => 'Pork kasim', 'unit' => 'kg']);
});

function csvFile(string $contents): string
{
    $path = tempnam(sys_get_temp_dir(), 'srp');
    file_put_contents($path, $contents);

    return $path;
}

it('imports a bulletin and updates existing SRPs', function () {
    Srp::factory()->for($this->rice)->create(['price' => 40.00, 'effective_date' => '2026-11-01']);

    $path = csvFile("\xEF\xBB\xBFCommodity,Unit,Price,Effective_Date\nwell-milled rice,kg,45.00,2026-11-01\nPork kasim,kg,\"₱1,350.50\",2026-11-01\n");

    $this->artisan('srps:import', ['path' => $path])
        ->expectsOutputToContain('1 created, 1 updated')
        ->assertSuccessful();

    expect($this->rice->srps()->sole()->price)->toBe('45.00')
        ->and($this->pork->srps()->sole()->price)->toBe('1350.50');
});

it('uses the default effective date for rows without one', function () {
    $path = csvFile("commodity,price\nWell-milled rice,45\n");

    $this->artisan('srps:import', ['path' => $path, '--effective-date' => '2026-12-01'])->assertSuccessful();

    expect($this->rice->srps()->sole()->effective_date->toDateString())->toBe('2026-12-01');
});

it('imports nothing when any row is invalid', function () {
    $path = csvFile(implode("\n", [
        'commodity,price,effective_date',
        'Well-milled rice,45.00,2026-11-01',
        'Galunggong,200,2026-11-01',
        'Pork kasim,abc,2026-11-01',
        'Pork kasim,350,11/01/2026',
        'Well-milled rice,46.00,2026-11-01',
    ]));

    $this->artisan('srps:import', ['path' => $path])
        ->expectsOutputToContain('Row 3: unknown commodity "Galunggong".')
        ->expectsOutputToContain('Row 4: invalid price "abc".')
        ->expectsOutputToContain('Row 5: invalid effective_date "11/01/2026", expected YYYY-MM-DD.')
        ->expectsOutputToContain('Row 6: duplicate of row 2.')
        ->assertFailed();

    expect(Srp::count())->toBe(0);
});

it('rejects a file without the required columns', function () {
    $path = csvFile("name,amount\nWell-milled rice,45\n");

    $this->artisan('srps:import', ['path' => $path])
        ->expectsOutputToContain('The header row is missing: commodity, price.')
        ->assertFailed();
});

it('imports through the API for admins only', function () {
    $file = fn () => UploadedFile::fake()->createWithContent('bulletin.csv', "commodity,price\nWell-milled rice,45\nPork kasim,350\n");

    actingAsUser();
    $this->postJson('/api/v1/srps/import', ['file' => $file(), 'effective_date' => '2026-11-01'])->assertForbidden();

    actingAsAdmin();
    $this->postJson('/api/v1/srps/import', ['file' => $file(), 'effective_date' => '2026-11-01'])
        ->assertOk()
        ->assertJsonPath('data', ['created' => 2, 'updated' => 0]);

    $this->postJson('/api/v1/srps/import', [
        'file' => UploadedFile::fake()->createWithContent('bulletin.csv', "commodity,price\nTilapia,120\n"),
        'effective_date' => '2026-11-01',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('error.details.file.0', 'Row 2: unknown commodity "Tilapia".');
});
