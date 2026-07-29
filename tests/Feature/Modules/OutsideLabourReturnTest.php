<?php

use App\Modules\OutsideLabourReturn\Livewire\Edit;
use App\Modules\OutsideLabourReturn\Livewire\Index;
use App\Modules\OutsideLabourReturn\Models\OutsideLabourReturn;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    OutsideLabourReturn::factory()->count(3)->create();

    $this->get(route('outside-labour-return.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('outside-labour-return.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('creates a return with a part line and a labour line and redirects', function () {
    $vendor = VendorMaster::factory()->create();
    $spare = SpareMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('return_type', 'warranty')
        ->set('claim_type', 'outside_labour_warranty')
        ->set('vendor_id', $vendor->id)
        ->set('return_reason', 'workmanship_failure')
        ->set('items.0.item_type', 'spare')
        ->set('items.0.spare_id', $spare->id)
        ->set('items.0.description', 'radiator')
        ->set('items.0.quantity', 1)
        ->call('addItem')
        ->set('items.1.item_type', 'labour')
        ->set('items.1.description', 'refit labour')
        ->set('items.1.quantity', 1)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('outside-labour-return.index'));

    $r = OutsideLabourReturn::with('items')->first();
    expect($r->return_no)->toBe('OLRR-'.str_pad((string) $r->id, 5, '0', STR_PAD_LEFT))
        ->and($r->items)->toHaveCount(2)
        ->and($r->items->pluck('item_type')->all())->toBe(['spare', 'labour'])
        ->and($r->items->first()->description)->toBe('RADIATOR');
});

it('requires a vendor', function () {
    Livewire::test(Edit::class)
        ->set('vendor_id', null)
        ->set('items.0.description', 'x')
        ->call('save')
        ->assertHasErrors(['vendor_id']);
});

it('requires a rejection reason when rejected', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('items.0.description', 'part')
        ->set('status', OutsideLabourReturn::STATUS_REJECTED)
        ->call('save')
        ->assertHasErrors(['rejection_reason']);
});

it('requires a counter proposal when status is counter proposal', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('items.0.description', 'part')
        ->set('status', OutsideLabourReturn::STATUS_COUNTER_PROPOSAL)
        ->call('save')
        ->assertHasErrors(['counter_proposal']);
});

it('requires a custom TAT when tat option is custom', function () {
    $vendor = VendorMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('items.0.description', 'part')
        ->set('tat_option', 'custom')
        ->call('save')
        ->assertHasErrors(['tat_custom_days']);
});

it('auto-fills a part line from the picked spare', function () {
    $vendor = VendorMaster::factory()->create();
    $spare = SpareMaster::factory()->create(['rate_before_tax' => 1250]);

    $component = Livewire::test(Edit::class)
        ->set('vendor_id', $vendor->id)
        ->set('items.0.spare_id', $spare->id);

    expect((float) $component->get('items.0.rate'))->toBe(1250.0);
});

it('reports KPI counts on the index', function () {
    OutsideLabourReturn::factory()->count(2)->create(['recovery_amount' => 1000]); // open
    OutsideLabourReturn::factory()->accepted()->create(['recovery_amount' => 5000]); // closed
    OutsideLabourReturn::factory()->rejected()->create(); // closed

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['open_claims'] === 2 && (float) $kpis['recovery_pending'] === 2000.0);
});

it('deletes a return', function () {
    $r = OutsideLabourReturn::factory()->create();

    Livewire::test(Index::class)->call('delete', $r->id);

    expect(OutsideLabourReturn::find($r->id))->toBeNull();
});

it('downloads the return register as a CSV stream', function () {
    OutsideLabourReturn::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
