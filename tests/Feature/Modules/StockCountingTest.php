<?php

use App\Modules\Inventory\Models\StockEntry;
use App\Modules\Inventory\Services\StockIssuer;
use App\Modules\Inventory\Services\StockLedger;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\StockCounting\Livewire\Edit;
use App\Modules\StockCounting\Livewire\Index;
use App\Modules\StockCounting\Models\StockCount;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    StockCount::factory()->count(3)->create();

    $this->get(route('stock-counting.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('stock-counting.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('creates a stock count with an SC- number and a counted item with computed variance', function () {
    Livewire::test(Edit::class)
        ->set('counting_method', 'manual')
        ->set('verification_status', 'in_progress')
        ->set('items.0.description', 'brake pad')
        ->set('items.0.system_stock', 10)
        ->set('items.0.physical_stock', 7)
        ->set('items.0.quantity', 7)
        ->set('items.0.mismatch_reason', 'lost_item')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('stock-counting.index'));

    $c = StockCount::with('items')->first();
    expect($c->count_no)->toBe('SC-'.str_pad((string) $c->id, 5, '0', STR_PAD_LEFT))
        ->and($c->items)->toHaveCount(1)
        ->and($c->items->first()->description)->toBe('BRAKE PAD')
        ->and((float) $c->items->first()->diff_qty)->toBe(-3.0);
});

it('rejects duplicate spare rows', function () {
    $spare = SpareMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('items', [
            ['id' => null, 'spare_id' => $spare->id, 'uom_id' => null, 'hsn_id' => null, 'tax_id' => null, 'barcode' => null, 'description' => 'ROW A', 'quantity' => 1, 'system_stock' => 1, 'physical_stock' => 1, 'mismatch_reason' => null, 'spares_condition' => null, 'purchase_invoice_no' => null, 'vendor_name' => null, 'remark' => null, 'spareSearch' => ''],
            ['id' => null, 'spare_id' => $spare->id, 'uom_id' => null, 'hsn_id' => null, 'tax_id' => null, 'barcode' => null, 'description' => 'ROW B', 'quantity' => 1, 'system_stock' => 1, 'physical_stock' => 1, 'mismatch_reason' => null, 'spares_condition' => null, 'purchase_invoice_no' => null, 'vendor_name' => null, 'remark' => null, 'spareSearch' => ''],
        ])
        ->call('save')
        ->assertHasErrors('items.1.spare_id');

    expect(StockCount::count())->toBe(0);
});

it('requires a verification status', function () {
    Livewire::test(Edit::class)
        ->set('verification_status', '')
        ->call('save')
        ->assertHasErrors(['verification_status']);
});

it('rejects an end date before the start date', function () {
    Livewire::test(Edit::class)
        ->set('count_start_date', '2026-07-24')
        ->set('count_end_date', '2026-07-01')
        ->call('save')
        ->assertHasErrors(['count_end_date']);
});

it('reports in-progress / completed-today / mismatch / pending KPIs', function () {
    StockCount::factory()->inProgress()->create();
    StockCount::factory()->completed()->create();
    StockCount::factory()->count(2)->create(); // pending

    $count = StockCount::factory()->create();
    $count->items()->create(['description' => 'X', 'system_stock' => 10, 'physical_stock' => 6, 'diff_qty' => -4, 'sequence_no' => 1]);

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['in_progress'] === 1
            && $kpis['completed_today'] === 1
            && $kpis['pending'] === 3
            && (float) $kpis['total_mismatch_qty'] === 4.0);
});

it('deletes a stock count', function () {
    $c = StockCount::factory()->create();

    Livewire::test(Index::class)->call('delete', $c->id);

    expect(StockCount::find($c->id))->toBeNull();
});

it('downloads the stock counting report as a CSV stream', function () {
    StockCount::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});

it('posts the counted variance to the ledger once the count is completed', function () {
    $spare = SpareMaster::factory()->create();
    StockIssuer::receive($spare->id, 10, 500, StockEntry::TYPE_OPENING);

    $component = Livewire::test(Edit::class)
        ->set('verification_status', StockCount::STATUS_IN_PROGRESS)
        ->set('items', [countLine($spare->id, system: 10, physical: 7)])
        ->call('save');

    // Nothing posts while the count is still being done.
    expect(StockLedger::currentQty($spare->id))->toBe(10.0);

    $count = StockCount::firstOrFail();
    Livewire::test(Edit::class, ['stockCount' => $count])
        ->set('verification_status', StockCount::STATUS_COMPLETED)
        ->call('save')
        ->assertHasNoErrors();

    expect(StockLedger::currentQty($spare->id))->toBe(7.0)
        ->and(StockEntry::where('entry_type', StockEntry::TYPE_ADJUSTMENT)->sum('qty'))->toEqual(-3);
});

it('tops stock up when the shelf holds more than the system', function () {
    $spare = SpareMaster::factory()->create();
    StockIssuer::receive($spare->id, 4, 200, StockEntry::TYPE_OPENING);

    Livewire::test(Edit::class)
        ->set('verification_status', StockCount::STATUS_COMPLETED)
        ->set('items', [countLine($spare->id, system: 4, physical: 9)])
        ->call('save')
        ->assertHasNoErrors();

    expect(StockLedger::currentQty($spare->id))->toBe(9.0);
});

it('lets a count write stock below zero — the shelf is the authority', function () {
    $spare = SpareMaster::factory()->create();
    StockIssuer::receive($spare->id, 2, 100, StockEntry::TYPE_OPENING);

    Livewire::test(Edit::class)
        ->set('verification_status', StockCount::STATUS_COMPLETED)
        ->set('items', [countLine($spare->id, system: 8, physical: 0)])
        ->call('save')
        ->assertHasNoErrors();

    expect(StockLedger::currentQty($spare->id))->toBe(-6.0);
});

it('reverses its adjustment when a completed count is cancelled', function () {
    $spare = SpareMaster::factory()->create();
    StockIssuer::receive($spare->id, 10, 100, StockEntry::TYPE_OPENING);

    Livewire::test(Edit::class)
        ->set('verification_status', StockCount::STATUS_COMPLETED)
        ->set('items', [countLine($spare->id, system: 10, physical: 6)])
        ->call('save');
    expect(StockLedger::currentQty($spare->id))->toBe(6.0);

    $count = StockCount::firstOrFail();
    Livewire::test(Edit::class, ['stockCount' => $count])
        ->set('verification_status', StockCount::STATUS_CANCELLED)
        ->call('save')
        ->assertHasNoErrors();

    expect(StockLedger::currentQty($spare->id))->toBe(10.0);
});

/** One count line in the component's array shape. */
function countLine(int $spareId, float $system, float $physical): array
{
    return [
        'id' => null, 'spare_id' => $spareId, 'uom_id' => null, 'hsn_id' => null, 'tax_id' => null,
        'barcode' => null, 'description' => 'COUNTED ROW', 'quantity' => $physical,
        'system_stock' => $system, 'physical_stock' => $physical, 'mismatch_reason' => null,
        'spares_condition' => null, 'purchase_invoice_no' => null, 'vendor_name' => null,
        'remark' => null, 'spareSearch' => '',
    ];
}
