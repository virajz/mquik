<?php

use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiry;
use App\Modules\VendorPurchaseOrder\Livewire\Edit;
use App\Modules\VendorPurchaseOrder\Livewire\Index;
use App\Modules\VendorPurchaseOrder\Models\VendorPurchaseOrder;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    VendorPurchaseOrder::factory()->count(3)->create();

    $this->get(route('vendor-purchase-order.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('vendor-purchase-order.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('creates a PO with a line and redirects', function () {
    $vendor = VendorMaster::factory()->create();
    $spare = SpareMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('po_type', 'stock_replenishment')
        ->set('vendor_id', $vendor->id)
        ->set('items.0.spare_id', $spare->id)
        ->set('items.0.description', 'brake pad')
        ->set('items.0.quantity', 10)
        ->set('items.0.rate', 250)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('vendor-purchase-order.index'));

    $po = VendorPurchaseOrder::with('items')->first();
    expect($po->po_no)->toBe('VPO-'.str_pad((string) $po->id, 5, '0', STR_PAD_LEFT))
        ->and($po->vendor_id)->toBe($vendor->id)
        ->and($po->items)->toHaveCount(1)
        ->and($po->items->first()->description)->toBe('BRAKE PAD')
        ->and((float) $po->items->first()->rate)->toBe(250.0);
});

it('requires a vendor', function () {
    Livewire::test(Edit::class)
        ->set('vendor_id', null)
        ->set('items.0.description', 'x')
        ->call('save')
        ->assertHasErrors(['vendor_id']);
});

it('requires a rejection reason when the vendor rejects', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('items.0.description', 'part')
        ->set('acknowledgement_status', VendorPurchaseOrder::ACK_REJECTED)
        ->call('save')
        ->assertHasErrors(['rejection_reason']);
});

it('requires custom delivery days when commitment is custom', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('items.0.description', 'part')
        ->set('delivery_commitment', 'custom')
        ->call('save')
        ->assertHasErrors(['delivery_custom_days']);
});

it('stores the vendor response dispatch fields (VPR)', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('items.0.description', 'part')
        ->set('acknowledgement_status', VendorPurchaseOrder::ACK_ACCEPTED)
        ->set('status', VendorPurchaseOrder::STATUS_DISPATCHED)
        ->set('courier_company', 'bluedart')
        ->set('consignment_no', 'awb12345')
        ->call('save')
        ->assertHasNoErrors();

    $po = VendorPurchaseOrder::first();
    expect($po->acknowledgement_status)->toBe(VendorPurchaseOrder::ACK_ACCEPTED)
        ->and($po->courier_company)->toBe('BLUEDART')
        ->and($po->consignment_no)->toBe('AWB12345');
});

it('auto-fills a line from the picked spare', function () {
    $vendor = VendorMaster::factory()->create();
    $spare = SpareMaster::factory()->create(['rate_before_tax' => 999]);

    $component = Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('items.0.spare_id', $spare->id);

    expect((float) $component->get('items.0.rate'))->toBe(999.0);
});

it('edits an existing PO and keeps its number', function () {
    $po = VendorPurchaseOrder::factory()->create();
    $original = $po->po_no;

    Livewire::test(Edit::class, ['vendorPurchaseOrder' => $po])
        ->set('items.0.description', 'oil filter')
        ->set('status', VendorPurchaseOrder::STATUS_DELIVERED)
        ->call('save')
        ->assertHasNoErrors();

    $po->refresh();
    expect($po->po_no)->toBe($original)
        ->and($po->status)->toBe(VendorPurchaseOrder::STATUS_DELIVERED);
});

it('reports KPI counts on the index', function () {
    VendorPurchaseOrder::factory()->count(2)->create(); // pending
    VendorPurchaseOrder::factory()->cancelled()->create();
    VendorPurchaseOrder::factory()->create([
        'status' => VendorPurchaseOrder::STATUS_ACKNOWLEDGED,
        'expected_delivery_date' => now()->subDays(3),
    ]);

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['pending'] === 3 && $kpis['cancelled'] === 1 && $kpis['delayed'] === 1);
});

it('deletes a PO', function () {
    $po = VendorPurchaseOrder::factory()->create();

    Livewire::test(Index::class)->call('delete', $po->id);

    expect(VendorPurchaseOrder::find($po->id))->toBeNull();
});

it('downloads the purchase order report as a CSV stream', function () {
    VendorPurchaseOrder::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});

it('carries forward from a VPI: links it and seeds the quoted lines', function () {
    $vendor = VendorMaster::factory()->create();
    $vpi = VendorPurchaseInquiry::factory()->create(['vendor_id' => $vendor->id, 'payment_term' => 'credit_15']);
    $spare = SpareMaster::factory()->create();
    $vpi->items()->create([
        'description' => 'BRAKE PAD', 'quantity' => 2, 'spare_id' => $spare->id,
        'quoted_rate' => 450.00, 'lead_time_days' => 3, 'sequence_no' => 1,
    ]);

    $component = Livewire::test(Edit::class, ['fromVpi' => $vpi->id]);

    expect($component->get('vendor_purchase_inquiry_id'))->toBe($vpi->id)
        ->and($component->get('vendor_id'))->toBe($vendor->id)
        ->and($component->get('payment_term'))->toBe('credit_15');

    $items = $component->get('items');
    expect($items)->toHaveCount(1)
        ->and($items[0]['description'])->toBe('BRAKE PAD')
        ->and($items[0]['spare_id'])->toBe($spare->id)
        // the vendor's quote becomes the ordered rate
        ->and((float) $items[0]['rate'])->toBe(450.00)
        ->and($items[0]['lead_time_days'])->toBe(3);
});

it('persists the VPI link when a carried-forward PO is saved', function () {
    $vendor = VendorMaster::factory()->create();
    $vpi = VendorPurchaseInquiry::factory()->create(['vendor_id' => $vendor->id]);
    $vpi->items()->create(['description' => 'BRAKE PAD', 'quantity' => 1, 'quoted_rate' => 100, 'sequence_no' => 1]);

    Livewire::test(Edit::class, ['fromVpi' => $vpi->id])
        ->set('po_type', 'stock_replenishment')
        ->set('vendor_id', $vendor->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(VendorPurchaseOrder::latest('id')->first()->vendor_purchase_inquiry_id)->toBe($vpi->id);
});
