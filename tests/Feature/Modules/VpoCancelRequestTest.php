<?php

use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VpoCancelRequest\Livewire\Edit;
use App\Modules\VpoCancelRequest\Livewire\Index;
use App\Modules\VpoCancelRequest\Models\VpoCancelRequest;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    VpoCancelRequest::factory()->count(3)->create();

    $this->get(route('vpo-cancel-request.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('vpo-cancel-request.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('creates a cancel request with a line and redirects', function () {
    $vendor = VendorMaster::factory()->create();
    $spare = SpareMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('cancellation_request_type', 'partial_item')
        ->set('cancellation_reason', 'wrong_part')
        ->set('vendor_id', $vendor->id)
        ->set('items.0.spare_id', $spare->id)
        ->set('items.0.description', 'brake pad')
        ->set('items.0.quantity', 10)
        ->set('items.0.quantity_to_cancel', 4)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('vpo-cancel-request.index'));

    $r = VpoCancelRequest::with('items')->first();
    expect($r->request_no)->toBe('VCR-'.str_pad((string) $r->id, 5, '0', STR_PAD_LEFT))
        ->and($r->vendor_id)->toBe($vendor->id)
        ->and($r->items)->toHaveCount(1)
        ->and($r->items->first()->description)->toBe('BRAKE PAD')
        ->and((float) $r->items->first()->quantity_to_cancel)->toBe(4.0);
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
        ->set('status', VpoCancelRequest::STATUS_REJECTED)
        ->call('save')
        ->assertHasErrors(['rejection_reason']);
});

it('requires a charge amount for a fixed / percentage cancellation term', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('items.0.description', 'part')
        ->set('cancellation_term', 'fixed_charge')
        ->call('save')
        ->assertHasErrors(['cancellation_charge']);
});

it('clears the charge when the term is no-charge', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('items.0.description', 'part')
        ->set('cancellation_term', 'no_charge')
        ->set('cancellation_charge', 500)
        ->call('save')
        ->assertHasNoErrors();

    expect(VpoCancelRequest::first()->cancellation_charge)->toBeNull();
});

it('auto-fills a line from the picked spare', function () {
    $vendor = VendorMaster::factory()->create();
    $spare = SpareMaster::factory()->create(['rate_before_tax' => 480]);

    $component = Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('items.0.spare_id', $spare->id);

    expect((float) $component->get('items.0.rate'))->toBe(480.0);
});

it('reports KPI counts on the index', function () {
    VpoCancelRequest::factory()->count(2)->create();        // requested -> open + pending
    VpoCancelRequest::factory()->refundPending()->create(); // refund pending + open + pending
    VpoCancelRequest::factory()->accepted()->create();      // not open

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['open'] === 3 && $kpis['pending_response'] === 3 && $kpis['refund_pending'] === 1);
});

it('deletes a cancel request', function () {
    $r = VpoCancelRequest::factory()->create();

    Livewire::test(Index::class)->call('delete', $r->id);

    expect(VpoCancelRequest::find($r->id))->toBeNull();
});

it('downloads the purchase report as a CSV stream', function () {
    VpoCancelRequest::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
