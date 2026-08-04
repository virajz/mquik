<?php

use App\Modules\Inventory\Models\StockEntry;
use App\Modules\Inventory\Services\StockLedger;
use App\Modules\SpareMaster\Models\SpareMaster;
use Illuminate\Support\Facades\File;

/**
 * `import:opening-stock` — the way on-hand balances get onto the 29,681
 * imported spares, from a physical count or a legacy stock export.
 */
function openingCsv(string $body): string
{
    $dir = storage_path('framework/testing/opening-'.uniqid());
    File::ensureDirectoryExists($dir);
    $path = $dir.'/opening.csv';
    File::put($path, "part_no,qty,rate,batch_no,expiry_date,location\n".$body);

    return $path;
}

it('creates one opening layer per row', function () {
    $a = SpareMaster::factory()->create(['spare_code' => 'BP-001']);
    $b = SpareMaster::factory()->create(['spare_code' => 'OF-002']);

    $this->artisan('import:opening-stock', ['file' => openingCsv(
        "BP-001,10,250,,,\nOF-002,4,900,,,RACK-A\n"
    )])->assertSuccessful();

    expect(StockLedger::currentQty($a->id))->toBe(10.0)
        ->and(StockLedger::currentQty($b->id))->toBe(4.0)
        ->and(StockEntry::where('spare_id', $b->id)->value('location'))->toBe('RACK-A')
        ->and(StockEntry::where('spare_id', $a->id)->value('entry_type'))->toBe(StockEntry::TYPE_OPENING);
});

it('carries batch and expiry onto the layer', function () {
    $spare = SpareMaster::factory()->create(['spare_code' => 'OIL-5W30']);

    $this->artisan('import:opening-stock', ['file' => openingCsv("OIL-5W30,12,480,lot-77,31/12/26,\n")])
        ->assertSuccessful();

    $layer = StockEntry::where('spare_id', $spare->id)->sole();
    expect($layer->batch_no)->toBe('LOT-77')
        ->and($layer->expiry_date->format('Y-m-d'))->toBe('2026-12-31');
});

it('matches part numbers case-insensitively and skips ones it does not know', function () {
    $spare = SpareMaster::factory()->create(['spare_code' => 'BP-001']);

    $this->artisan('import:opening-stock', ['file' => openingCsv("bp-001,5,100,,,\nNOSUCHPART,9,100,,,\n")])
        ->expectsOutputToContain('1 unknown part no')
        ->assertSuccessful();

    expect(StockLedger::currentQty($spare->id))->toBe(5.0)
        ->and(StockEntry::count())->toBe(1);
});

it('skips a spare that already has an opening balance', function () {
    $spare = SpareMaster::factory()->create(['spare_code' => 'BP-001']);
    $csv = openingCsv("BP-001,10,250,,,\n");

    $this->artisan('import:opening-stock', ['file' => $csv])->assertSuccessful();
    $this->artisan('import:opening-stock', ['file' => $csv])
        ->expectsOutputToContain('1 already opened')
        ->assertSuccessful();

    expect(StockLedger::currentQty($spare->id))->toBe(10.0)
        ->and(StockEntry::count())->toBe(1);
});

it('recounts when --replace is passed', function () {
    $spare = SpareMaster::factory()->create(['spare_code' => 'BP-001']);

    $this->artisan('import:opening-stock', ['file' => openingCsv("BP-001,10,250,,,\n")])->assertSuccessful();
    $this->artisan('import:opening-stock', ['file' => openingCsv("BP-001,3,250,,,\n"), '--replace' => true])->assertSuccessful();

    expect(StockLedger::currentQty($spare->id))->toBe(3.0)
        ->and(StockEntry::count())->toBe(1);
});

it('writes nothing on a dry run', function () {
    $spare = SpareMaster::factory()->create(['spare_code' => 'BP-001']);

    $this->artisan('import:opening-stock', ['file' => openingCsv("BP-001,10,250,,,\n"), '--dry-run' => true])
        ->expectsOutputToContain('[dry run] would import 1')
        ->assertSuccessful();

    expect(StockEntry::count())->toBe(0);
});

it('ignores zero and blank quantities', function () {
    SpareMaster::factory()->create(['spare_code' => 'BP-001']);

    $this->artisan('import:opening-stock', ['file' => openingCsv("BP-001,0,250,,,\n")])
        ->expectsOutputToContain('1 zero qty')
        ->assertSuccessful();

    expect(StockEntry::count())->toBe(0);
});

it('fails loudly on a missing file', function () {
    $this->artisan('import:opening-stock', ['file' => '/tmp/definitely-not-here.csv'])->assertFailed();
});
