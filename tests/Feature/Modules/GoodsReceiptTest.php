<?php

use App\Modules\GoodsReceipt\Livewire\Edit;
use App\Modules\GoodsReceipt\Livewire\Index;
use App\Modules\GoodsReceipt\Models\GoodsReceipt;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorPurchaseOrder\Models\VendorPurchaseOrder;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    GoodsReceipt::factory()->count(3)->create();

    $this->get(route('goods-receipt.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('goods-receipt.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('receives goods with a per-line approval and redirects', function () {
    $vendor = VendorMaster::factory()->create();
    $po = VendorPurchaseOrder::factory()->create();
    $spare = SpareMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('goods_receipt_type', 'against_po')
        ->set('vendor_id', $vendor->id)
        ->set('vendor_purchase_order_id', $po->id)
        ->set('items.0.spare_id', $spare->id)
        ->set('items.0.description', 'brake disc')
        ->set('items.0.quantity', 6)
        ->set('items.0.quantity_approved', 6)
        ->set('items.0.rate_approved', 900)
        ->set('items.0.part_approved', true)
        ->set('items.0.storage_allocation', 'a1')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('goods-receipt.index'));

    $g = GoodsReceipt::with('items')->first();
    expect($g->grn_no)->toBe('GRN-'.str_pad((string) $g->id, 5, '0', STR_PAD_LEFT))
        ->and($g->items)->toHaveCount(1);
    $item = $g->items->first();
    expect($item->description)->toBe('BRAKE DISC')
        ->and($item->part_approved)->toBeTrue()
        ->and((float) $item->rate_approved)->toBe(900.0)
        ->and($item->storage_allocation)->toBe('A1');
});

it('requires a vendor', function () {
    Livewire::test(Edit::class)
        ->set('vendor_id', null)
        ->set('items.0.description', 'x')
        ->call('save')
        ->assertHasErrors(['vendor_id']);
});

it('requires a purchase order for an against-PO receipt', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('goods_receipt_type', 'against_po')
        ->set('vendor_id', $vendor->id)
        ->set('vendor_purchase_order_id', null)
        ->set('items.0.description', 'part')
        ->call('save')
        ->assertHasErrors(['vendor_purchase_order_id']);
});

it('allows a direct receipt without a purchase order', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('goods_receipt_type', 'direct_receipt')
        ->set('vendor_id', $vendor->id)
        ->set('vendor_purchase_order_id', null)
        ->set('items.0.description', 'part')
        ->set('items.0.quantity', 1)
        ->call('save')
        ->assertHasNoErrors();

    expect(GoodsReceipt::count())->toBe(1);
});

it('auto-fills a line from the picked spare', function () {
    $vendor = VendorMaster::factory()->create();
    $spare = SpareMaster::factory()->create(['rate_before_tax' => 720]);

    $component = Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('items.0.spare_id', $spare->id);

    expect((float) $component->get('items.0.rate'))->toBe(720.0)
        ->and((float) $component->get('items.0.last_purchase_price'))->toBe(720.0);
});

it('counts rejected material lines and pending receipts in KPIs', function () {
    $vendor = VendorMaster::factory()->create();
    $spare = SpareMaster::factory()->create();

    // A receipt with a damaged line.
    Livewire::test(Edit::class)
        ->set('goods_receipt_type', 'direct_receipt')
        ->set('vendor_id', $vendor->id)
        ->set('items.0.spare_id', $spare->id)
        ->set('items.0.description', 'cracked part')
        ->set('items.0.quantity', 1)
        ->set('items.0.physical_verification', 'physical_damage')
        ->call('save')
        ->assertHasNoErrors();

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['today'] === 1 && $kpis['pending'] === 1 && $kpis['rejected_materials'] === 1);
});

it('deletes a GRN', function () {
    $g = GoodsReceipt::factory()->create();

    Livewire::test(Index::class)->call('delete', $g->id);

    expect(GoodsReceipt::find($g->id))->toBeNull();
});

it('downloads the purchase report as a CSV stream', function () {
    GoodsReceipt::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
