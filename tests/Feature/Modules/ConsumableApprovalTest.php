<?php

use App\Modules\ConsumableApproval\Livewire\Edit;
use App\Modules\ConsumableApproval\Livewire\Index;
use App\Modules\ConsumableApproval\Models\ConsumableApproval;
use App\Modules\SpareMaster\Models\SpareMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    ConsumableApproval::factory()->count(3)->create();

    $this->get(route('consumable-approval.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('consumable-approval.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('raises a request with a loss line and stamps the requested timestamp', function () {
    $spare = SpareMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('consumable_category', 'paint')
        ->set('priority', 'high')
        ->set('items.0.item_type', 'spare')
        ->set('items.0.spare_id', $spare->id)
        ->set('items.0.description', 'primer wasted')
        ->set('items.0.quantity', 2)
        ->set('items.0.rate', 300)
        ->set('items.0.loss_damage_type', 'evaporation_loss')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('consumable-approval.index'));

    $c = ConsumableApproval::with('items')->first();
    expect($c->request_no)->toBe('CA-'.str_pad((string) $c->id, 5, '0', STR_PAD_LEFT))
        ->and($c->requested_at)->not->toBeNull()
        ->and($c->items)->toHaveCount(1)
        ->and($c->items->first()->description)->toBe('PRIMER WASTED')
        ->and($c->items->first()->loss_damage_type)->toBe('evaporation_loss');
});

it('stamps the approved timestamp when created approved', function () {
    Livewire::test(Edit::class)
        ->set('status', ConsumableApproval::STATUS_APPROVED)
        ->set('items.0.description', 'x')
        ->call('save')
        ->assertHasNoErrors();

    expect(ConsumableApproval::first()->approved_at)->not->toBeNull();
});

it('stamps the approved timestamp when an existing request is approved', function () {
    $request = ConsumableApproval::factory()->create();

    Livewire::test(Edit::class, ['consumableApproval' => $request])
        ->set('items.0.description', 'x')
        ->set('status', ConsumableApproval::STATUS_APPROVED)
        ->call('save')
        ->assertHasNoErrors();

    expect($request->fresh()->approved_at)->not->toBeNull();
});

it('requires a priority and at least one line', function () {
    Livewire::test(Edit::class)
        ->set('priority', '')
        ->set('items.0.description', '')
        ->call('save')
        ->assertHasErrors(['priority', 'items.0.description']);
});

it('auto-fills a line from the picked spare', function () {
    $spare = SpareMaster::factory()->create(['rate_before_tax' => 150]);

    $component = Livewire::test(Edit::class)
        ->set('items.0.spare_id', $spare->id);

    expect((float) $component->get('items.0.rate'))->toBe(150.0);
});

it('reports KPI counts and consumption on the index', function () {
    $paint = ConsumableApproval::factory()->create(['consumable_category' => 'paint']);
    $paint->items()->create(['description' => 'A', 'quantity' => 2, 'rate' => 100, 'loss_damage_type' => 'date_expired', 'item_type' => 'spare', 'sequence_no' => 1]);

    ConsumableApproval::factory()->approved()->create();
    ConsumableApproval::factory()->rejected()->create();

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['pending'] === 1
            && $kpis['approved'] === 1
            && $kpis['rejected'] === 1
            && $kpis['expired'] === 1
            && (float) $kpis['paint'] === 200.0);
});

it('deletes a request', function () {
    $c = ConsumableApproval::factory()->create();

    Livewire::test(Index::class)->call('delete', $c->id);

    expect(ConsumableApproval::find($c->id))->toBeNull();
});

it('downloads the report as a CSV stream', function () {
    ConsumableApproval::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
