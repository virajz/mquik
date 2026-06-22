<?php

use App\Modules\EstimateTemplateMaster\Livewire\Edit;
use App\Modules\EstimateTemplateMaster\Livewire\Index;
use App\Modules\EstimateTemplateMaster\Models\EstimateTemplateMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    EstimateTemplateMaster::factory()->count(2)->create();

    $this->get(route('estimate-template-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('estimate-template-master.index'))->assertRedirect(route('login'));
});

it('creates a template with spare lines', function () {
    $spare = SpareMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('name', 'pms basic')
        ->call('addItem', 'spare')
        ->set('items.0.spare_id', $spare->id)
        ->set('items.0.default_qty', 2)
        ->call('save')
        ->assertHasNoErrors();

    $template = EstimateTemplateMaster::with('items')->firstOrFail();
    expect($template->name)->toBe('PMS BASIC')
        ->and($template->items)->toHaveCount(1)
        ->and($template->items[0]->line_type)->toBe('spare')
        ->and($template->items[0]->spare_id)->toBe($spare->id);
});

it('deletes a template from the index', function () {
    $t = EstimateTemplateMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $t->id);

    expect(EstimateTemplateMaster::find($t->id))->toBeNull();
});
