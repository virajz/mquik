<?php

use App\Modules\PerformanceSlabMaster\Livewire\Form;
use App\Modules\PerformanceSlabMaster\Livewire\Index;
use App\Modules\PerformanceSlabMaster\Models\PerformanceSlabMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    PerformanceSlabMaster::factory()->count(3)->create();

    $this->get(route('performance-slab-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('performance-slab-master.index'))->assertRedirect(route('login'));
});

it('creates a slab with percent range and incentive amount', function () {
    Livewire::test(Form::class)
        ->set('name', 'slab 2')
        ->set('code', 's2')
        ->set('min_percent', 90)
        ->set('max_percent', 99.99)
        ->set('incentive_amount', 200)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('performance-slab-master:saved');

    $s = PerformanceSlabMaster::firstOrFail();
    expect((float) $s->min_percent)->toBe(90.0)
        ->and((float) $s->max_percent)->toBe(99.99)
        ->and((float) $s->incentive_amount)->toBe(200.0);
});

it('rejects a max_percent below min_percent', function () {
    Livewire::test(Form::class)
        ->set('name', 'bad slab')
        ->set('min_percent', 90)
        ->set('max_percent', 80)
        ->call('save')
        ->assertHasErrors(['max_percent']);
});

it('deletes a slab from the index', function () {
    $record = PerformanceSlabMaster::factory()->create();
    Livewire::test(Index::class)->call('delete', $record->id);
    expect(PerformanceSlabMaster::find($record->id))->toBeNull();
});
