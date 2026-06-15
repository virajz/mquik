<?php

use App\Modules\TransmissionTypeMaster\Models\TransmissionTypeMaster;
use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
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
    $tx = TransmissionTypeMaster::factory()->create(['name' => 'MANUAL']);

    Livewire::test(Form::class)
        ->set('model_id', $model->id)
        ->set('name', 'vxi')
        ->set('transmission_type_id', $tx->id)
        ->set('engine_cc', '1197cc')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('vehicle-variant-master:saved');

    $r = VehicleVariantMaster::firstOrFail();
    expect($r->name)->toBe('VXI')
        ->and($r->model_id)->toBe($model->id)
        ->and($r->transmission_type_id)->toBe($tx->id);
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

it('quick-add Model wizard creates a model and assigns it to model_id', function () {
    $brand = VehicleBrandMaster::firstOrCreate(['name' => 'TATA'], ['is_active' => true]);

    Livewire::test(Form::class)
        ->set('quickModel.brand_id', $brand->id)
        ->set('quickModel.name', 'punch')
        ->call('createQuickModel')
        ->assertHasNoErrors();

    $model = VehicleModelMaster::where('brand_id', $brand->id)->where('name', 'PUNCH')->firstOrFail();

    Livewire::test(Form::class)
        ->set('quickModel.brand_id', $brand->id)
        ->set('quickModel.name', 'PUNCH')
        ->call('createQuickModel')
        ->assertSet('model_id', $model->id)
        ->assertSet('quickModel.name', '');
});

it('quick-add Model wizard requires brand + name', function () {
    Livewire::test(Form::class)
        ->call('createQuickModel')
        ->assertHasErrors([
            'quickModel.brand_id',
            'quickModel.name',
        ]);
});

it('createQuickModelBrand creates a brand inline and assigns it to quickModel.brand_id', function () {
    Livewire::test(Form::class)
        ->set('quickModelBrandSearch', 'kia')
        ->call('createQuickModelBrand')
        ->assertSet('quickModelBrandSearch', '');

    $brand = VehicleBrandMaster::where('name', 'KIA')->firstOrFail();
    expect($brand)->not->toBeNull();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('vehicle-variant-master.index'))->assertRedirect(route('login'));
});
