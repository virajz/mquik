<?php

use App\Modules\ExcessStockApproval\Livewire\Edit;
use App\Modules\ExcessStockApproval\Livewire\Index;
use App\Modules\ExcessStockApproval\Models\ExcessStockApproval;
use App\Modules\SpareMaster\Models\SpareMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    ExcessStockApproval::factory()->count(3)->create();

    $this->get(route('excess-stock-approval.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('excess-stock-approval.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('raises a request with a line and stamps requested_at', function () {
    $spare = SpareMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('excess_stock_reason', 'excess_purchase')
        ->set('priority', 'high')
        ->set('items.0.spare_id', $spare->id)
        ->set('items.0.description', 'oversupplied bolt')
        ->set('items.0.quantity', 20)
        ->set('items.0.rate', 15)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('excess-stock-approval.index'));

    $e = ExcessStockApproval::with('items')->first();
    expect($e->request_no)->toBe('ESA-'.str_pad((string) $e->id, 5, '0', STR_PAD_LEFT))
        ->and($e->requested_at)->not->toBeNull()
        ->and($e->items)->toHaveCount(1)
        ->and($e->items->first()->description)->toBe('OVERSUPPLIED BOLT');
});

it('requires a vendor rejection reason when the reason is vendor return rejected', function () {
    Livewire::test(Edit::class)
        ->set('excess_stock_reason', 'vendor_return_rejected')
        ->set('items.0.description', 'part')
        ->call('save')
        ->assertHasErrors(['vendor_rejection_reason']);
});

it('requires a priority and at least one line', function () {
    Livewire::test(Edit::class)
        ->set('priority', '')
        ->set('items.0.description', '')
        ->call('save')
        ->assertHasErrors(['priority', 'items.0.description']);
});

it('stamps approved_at when created approved', function () {
    Livewire::test(Edit::class)
        ->set('status', ExcessStockApproval::STATUS_APPROVED)
        ->set('items.0.description', 'x')
        ->call('save')
        ->assertHasNoErrors();

    expect(ExcessStockApproval::first()->approved_at)->not->toBeNull();
});

it('auto-fills a line from the picked spare', function () {
    $spare = SpareMaster::factory()->create(['rate_before_tax' => 90]);

    $component = Livewire::test(Edit::class)
        ->set('items.0.spare_id', $spare->id);

    expect((float) $component->get('items.0.rate'))->toBe(90.0);
});

it('reports KPIs including excess and dead stock value', function () {
    // Live (excess) stock line.
    $excess = ExcessStockApproval::factory()->create(['excess_stock_reason' => 'excess_purchase']);
    $excess->items()->create(['description' => 'A', 'quantity' => 10, 'rate' => 100, 'sequence_no' => 1]);

    // Dead stock line (return window expired).
    $dead = ExcessStockApproval::factory()->deadStock()->approved()->create();
    $dead->items()->create(['description' => 'B', 'quantity' => 5, 'rate' => 200, 'sequence_no' => 1]);

    ExcessStockApproval::factory()->rejected()->create();

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['pending'] === 1
            && $kpis['approved'] === 1
            && $kpis['rejected'] === 1
            && (float) $kpis['excess_value'] === 2000.0
            && (float) $kpis['dead_value'] === 1000.0);
});

it('deletes a request', function () {
    $e = ExcessStockApproval::factory()->create();

    Livewire::test(Index::class)->call('delete', $e->id);

    expect(ExcessStockApproval::find($e->id))->toBeNull();
});

it('downloads the excess stock report as a CSV stream', function () {
    ExcessStockApproval::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
