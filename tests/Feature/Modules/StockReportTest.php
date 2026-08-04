<?php

use App\Modules\Inventory\Models\StockEntry;
use App\Modules\Inventory\Services\StockIssuer;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\StockReport\Livewire\Index;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the report page', function () {
    $this->get(route('stock-report.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('shows on-hand quantity and value per spare', function () {
    $spare = SpareMaster::factory()->create(['name' => 'BRAKE PAD', 'rate_before_tax' => 100]);
    StockEntry::factory()->in(10)->create(['spare_id' => $spare->id]);
    StockEntry::factory()->out(3)->create(['spare_id' => $spare->id]);

    $rows = Livewire::test(Index::class)->viewData('rows');
    $row = $rows->firstWhere('spare.id', $spare->id);

    expect($row['qty'])->toBe(7.0)
        ->and($row['value'])->toBe(700.0);   // 7 × 100
});

it('totals stock value across the whole filtered set, not just the page', function () {
    $a = SpareMaster::factory()->create(['rate_before_tax' => 50]);
    $b = SpareMaster::factory()->create(['rate_before_tax' => 20]);
    StockEntry::factory()->in(4)->create(['spare_id' => $a->id]); // 200
    StockEntry::factory()->in(5)->create(['spare_id' => $b->id]); // 100

    expect(Livewire::test(Index::class)->instance()->totalValue)->toBe(300.0);
});

it('filters to below-reorder spares only', function () {
    $low = SpareMaster::factory()->create(['name' => 'LOW STOCK', 'min_qty' => 10]);
    $ok = SpareMaster::factory()->create(['name' => 'HEALTHY', 'min_qty' => 2]);
    StockEntry::factory()->in(3)->create(['spare_id' => $low->id]);  // 3 < min 10 → below_min
    StockEntry::factory()->in(20)->create(['spare_id' => $ok->id]);  // 20 ≥ min 2 → ok

    $rows = Livewire::test(Index::class)
        ->set('belowReorderOnly', true)
        ->viewData('rows');

    expect($rows->pluck('spare.name')->all())->toBe(['LOW STOCK']);
});

it('filters by brand', function () {
    $brandA = SpareBrandMaster::factory()->create();
    $withBrand = SpareMaster::factory()->create(['name' => 'BRANDED', 'spare_brand_id' => $brandA->id]);
    SpareMaster::factory()->create(['name' => 'OTHER']);

    $rows = Livewire::test(Index::class)
        ->set('brandFilter', (string) $brandA->id)
        ->viewData('rows');

    expect($rows->pluck('spare.name')->all())->toBe(['BRANDED']);
});

it('streams a CSV honouring the current filters', function () {
    $brandA = SpareBrandMaster::factory()->create();
    $inScope = SpareMaster::factory()->create(['name' => 'IN SCOPE', 'spare_brand_id' => $brandA->id, 'rate_before_tax' => 100]);
    StockEntry::factory()->in(5)->create(['spare_id' => $inScope->id]);
    SpareMaster::factory()->create(['name' => 'OUT OF SCOPE']);

    $stream = Livewire::test(Index::class)
        ->set('brandFilter', (string) $brandA->id)
        ->instance()
        ->download();

    ob_start();
    $stream->sendContent();
    $csv = ob_get_clean();

    expect($csv)->toContain('IN SCOPE')
        ->and($csv)->not->toContain('OUT OF SCOPE')
        ->and($csv)->toContain('"Part No",Spare,Brand');
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('stock-report.index'))->assertRedirect(route('login'));
});

it('warns about batches expiring inside the window', function () {
    $spare = SpareMaster::factory()->create(['name' => 'ENGINE OIL 5W30', 'tracks_batch' => true]);
    StockIssuer::receive($spare->id, 8, 500, StockEntry::TYPE_PURCHASE, null, [
        'batch_no' => 'LOT-42', 'expiry_date' => today()->addDays(20)->toDateString(),
    ]);

    Livewire::test(Index::class)
        ->assertSee('1 batch expiring within')
        ->assertSee('LOT-42')
        ->assertSee('20d left')
        ->assertSee('4,000.00');   // 8 × ₹500 at risk
});

it('calls out batches that have already lapsed', function () {
    $spare = SpareMaster::factory()->create(['tracks_batch' => true]);
    StockIssuer::receive($spare->id, 2, 100, StockEntry::TYPE_PURCHASE, null, [
        'batch_no' => 'OLD', 'expiry_date' => today()->subDays(7)->toDateString(),
    ]);

    Livewire::test(Index::class)
        ->assertSee('1 already lapsed')
        ->assertSee('7d ago');
});

it('stays quiet when nothing is near expiry', function () {
    $spare = SpareMaster::factory()->create();
    StockIssuer::receive($spare->id, 5, 100, StockEntry::TYPE_PURCHASE, null, [
        'expiry_date' => today()->addYears(3)->toDateString(),
    ]);

    Livewire::test(Index::class)->assertDontSee('expiring within');
});
