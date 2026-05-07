<?php

use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleVariantMaster\Livewire\Form;
use App\Modules\VehicleVariantMaster\Livewire\Index;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    VehicleVariantMaster::factory()->count(3)->create();
    $this->get(route('vehicle-variant-master.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('creates a variant with model FK', function () {
    $model = VehicleModelMaster::factory()->create(['name' => 'SWIFT']);

    Livewire::test(Form::class)
        ->set('model_id', $model->id)
        ->set('name', 'vxi')
        ->set('transmission', 'manual')
        ->set('engine_cc', '1197cc')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('vehicle-variant-master:saved');

    $r = VehicleVariantMaster::firstOrFail();
    expect($r->name)->toBe('VXI')
        ->and($r->model_id)->toBe($model->id)
        ->and($r->transmission)->toBe('manual');
});

it('requires model_id', function () {
    Livewire::test(Form::class)->set('name', 'TEST')->call('save')->assertHasErrors(['model_id']);
});

it('blocks duplicate variant name within same model', function () {
    $model = VehicleModelMaster::factory()->create();
    VehicleVariantMaster::factory()->forModel($model)->create(['name' => 'VXI']);

    Livewire::test(Form::class)
        ->set('model_id', $model->id)
        ->set('name', 'VXI')
        ->call('save')
        ->assertHasErrors(['name']);
});

it('updates a variant', function () {
    $r = VehicleVariantMaster::factory()->create(['name' => 'OLD']);
    Livewire::test(Form::class)
        ->dispatch('vehicle-variant-master:edit', id: $r->id)
        ->set('name', 'updated')
        ->call('save')
        ->assertHasNoErrors();
    expect($r->fresh()->name)->toBe('UPDATED');
});

it('deletes a variant', function () {
    $r = VehicleVariantMaster::factory()->create();
    Livewire::test(Index::class)->call('delete', $r->id);
    expect(VehicleVariantMaster::find($r->id))->toBeNull();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('vehicle-variant-master.index'))->assertRedirect(route('login'));
});
