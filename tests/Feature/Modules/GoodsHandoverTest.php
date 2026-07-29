<?php

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\GoodsHandover\Livewire\Edit;
use App\Modules\GoodsHandover\Livewire\Index;
use App\Modules\GoodsHandover\Models\GoodsHandover;
use App\Modules\SpareMaster\Models\SpareMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    GoodsHandover::factory()->count(3)->create();

    $this->get(route('goods-handover.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('renders the create page', function () {
    $this->get(route('goods-handover.create'))->assertOk()->assertSeeLivewire(Edit::class);
});

it('records a handover with a part line and redirects', function () {
    $tech = EmployeeMaster::factory()->create();
    $spare = SpareMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('received_by_id', $tech->id)
        ->set('items.0.spare_id', $spare->id)
        ->set('items.0.description', 'oil filter')
        ->set('items.0.quantity', 4)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('goods-handover.index'));

    $h = GoodsHandover::with('items')->first();
    expect($h->handover_no)->toBe('GHO-'.str_pad((string) $h->id, 5, '0', STR_PAD_LEFT))
        ->and($h->received_by_id)->toBe($tech->id)
        ->and($h->items)->toHaveCount(1)
        ->and($h->items->first()->description)->toBe('OIL FILTER');
});

it('requires a receiving technician', function () {
    Livewire::test(Edit::class)
        ->set('received_by_id', null)
        ->set('items.0.description', 'x')
        ->call('save')
        ->assertHasErrors(['received_by_id']);
});

it('requires a return reason when a material return status is set', function () {
    $tech = EmployeeMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('received_by_id', $tech->id)
        ->set('items.0.description', 'part')
        ->set('material_return_status', 'partial_return')
        ->call('save')
        ->assertHasErrors(['return_reason']);
});

it('captures a technician parts return', function () {
    $tech = EmployeeMaster::factory()->create();
    $spare = SpareMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('received_by_id', $tech->id)
        ->set('material_return_status', 'partial_return')
        ->set('return_reason', 'excess_issue')
        ->set('items.0.spare_id', $spare->id)
        ->set('items.0.description', 'bolt')
        ->set('items.0.quantity', 10)
        ->set('items.0.returned_quantity', 3)
        ->call('save')
        ->assertHasNoErrors();

    $h = GoodsHandover::with('items')->first();
    expect($h->material_return_status)->toBe('partial_return')
        ->and((float) $h->items->first()->returned_quantity)->toBe(3.0);
});

it('auto-fills a line from the picked spare', function () {
    $tech = EmployeeMaster::factory()->create();
    $spare = SpareMaster::factory()->create(['name' => 'Brake Cable']);

    $component = Livewire::test(Edit::class)
        ->set('received_by_id', $tech->id)
        ->set('items.0.spare_id', $spare->id);

    expect($component->get('items.0.spare_brand_id'))->toBe($spare->spare_brand_id);
});

it('reports KPI counts and technician consumption on the index', function () {
    GoodsHandover::factory()->count(2)->create();
    GoodsHandover::factory()->withReturn()->create();

    Livewire::test(Index::class)
        ->assertViewHas('kpis', fn ($kpis) => $kpis['issued_today'] === 3 && $kpis['returns'] === 1);
});

it('deletes a handover', function () {
    $h = GoodsHandover::factory()->create();

    Livewire::test(Index::class)->call('delete', $h->id);

    expect(GoodsHandover::find($h->id))->toBeNull();
});

it('downloads the purchase report as a CSV stream', function () {
    GoodsHandover::factory()->count(2)->create();

    Livewire::test(Index::class)->call('download')->assertFileDownloaded();
});
