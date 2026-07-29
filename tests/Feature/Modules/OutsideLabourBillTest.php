<?php

use App\Modules\OutsideLabourBill\Livewire\Edit;
use App\Modules\OutsideLabourBill\Livewire\Index;
use App\Modules\OutsideLabourBill\Models\OutsideLabourBill;
use App\Modules\VendorMaster\Models\VendorMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    OutsideLabourBill::factory()->count(3)->create();

    $this->get(route('outside-labour-bill.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('outside-labour-bill.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('receives a bill with a line and redirects', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('bill_document_type', 'tax_invoice')
        ->set('vendor_bill_no', 'inv-778')
        ->set('bill_amount', 8500)
        ->set('items.0.description', 'denting front door')
        ->set('items.0.quantity', 1)
        ->set('items.0.rate', 8500)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('outside-labour-bill.index'));

    $b = OutsideLabourBill::with('items')->first();
    expect($b->bill_no)->toBe('OLB-'.str_pad((string) $b->id, 5, '0', STR_PAD_LEFT))
        ->and($b->vendor_bill_no)->toBe('INV-778')
        ->and($b->items)->toHaveCount(1)
        ->and($b->items->first()->description)->toBe('DENTING FRONT DOOR');
});

it('requires a vendor', function () {
    Livewire::test(Edit::class)
        ->set('vendor_id', null)
        ->set('items.0.description', 'x')
        ->call('save')
        ->assertHasErrors(['vendor_id']);
});

it('requires a hold reason when on hold', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('items.0.description', 'work')
        ->set('status', OutsideLabourBill::STATUS_ON_HOLD)
        ->call('save')
        ->assertHasErrors(['hold_reason']);
});

it('requires a rejection reason when rejected', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('items.0.description', 'work')
        ->set('status', OutsideLabourBill::STATUS_REJECTED)
        ->call('save')
        ->assertHasErrors(['rejection_reason']);
});

it('requires custom reminder days when frequency is custom', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('items.0.description', 'work')
        ->set('reminder_frequency', 'custom')
        ->call('save')
        ->assertHasErrors(['reminder_custom_days']);
});

it('reports KPI counts on the index', function () {
    OutsideLabourBill::factory()->count(2)->create();      // requested -> pending
    OutsideLabourBill::factory()->onHold()->create();
    OutsideLabourBill::factory()->rejected()->create();

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['pending'] === 2 && $kpis['on_hold'] === 1 && $kpis['rejected'] === 1);
});

it('deletes a bill', function () {
    $b = OutsideLabourBill::factory()->create();

    Livewire::test(Index::class)->call('delete', $b->id);

    expect(OutsideLabourBill::find($b->id))->toBeNull();
});

it('downloads the status report as a CSV stream', function () {
    OutsideLabourBill::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
