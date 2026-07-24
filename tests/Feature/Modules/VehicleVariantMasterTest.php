<?php

use App\Modules\SpareMaster\Models\SpareMaster;
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

it('copies the spare-compatibility list from a source variant onto a new one', function () {
    $source = VehicleVariantMaster::factory()->create();
    $a = SpareMaster::factory()->create(['name' => 'BRAKE PAD']);
    $b = SpareMaster::factory()->create(['name' => 'OIL FILTER']);
    $source->spares()->attach([$a->id, $b->id]);

    $model = VehicleModelMaster::factory()->create();

    Livewire::test(Form::class)
        ->set('model_id', $model->id)
        ->set('name', 'vxi')
        ->set('copy_from_variant_id', $source->id)   // triggers the load
        ->assertCount('clonedSpares', 2)
        ->call('save')
        ->assertHasNoErrors();

    $new = VehicleVariantMaster::where('name', 'VXI')->firstOrFail();
    expect($new->spares->pluck('name')->sort()->values()->all())->toBe(['BRAKE PAD', 'OIL FILTER']);
});

it('lets the user prune a copied spare before saving', function () {
    $source = VehicleVariantMaster::factory()->create();
    $keep = SpareMaster::factory()->create(['name' => 'KEEP ME']);
    $drop = SpareMaster::factory()->create(['name' => 'DROP ME']);
    $source->spares()->attach([$keep->id, $drop->id]);

    $model = VehicleModelMaster::factory()->create();

    Livewire::test(Form::class)
        ->set('model_id', $model->id)
        ->set('name', 'sx')
        ->set('copy_from_variant_id', $source->id)
        ->call('removeClonedSpare', $drop->id)
        ->assertCount('clonedSpares', 1)
        ->call('save')
        ->assertHasNoErrors();

    $new = VehicleVariantMaster::where('name', 'SX')->firstOrFail();
    expect($new->spares->pluck('name')->all())->toBe(['KEEP ME']);
});

it('clears the copied list when the source is deselected', function () {
    $source = VehicleVariantMaster::factory()->create();
    $source->spares()->attach(SpareMaster::factory()->create()->id);

    Livewire::test(Form::class)
        ->set('copy_from_variant_id', $source->id)
        ->assertCount('clonedSpares', 1)
        ->set('copy_from_variant_id', null)
        ->assertCount('clonedSpares', 0);
});

it('does not copy spares when no source is chosen', function () {
    $model = VehicleModelMaster::factory()->create();

    Livewire::test(Form::class)
        ->set('model_id', $model->id)
        ->set('name', 'base')
        ->call('save')
        ->assertHasNoErrors();

    expect(VehicleVariantMaster::where('name', 'BASE')->firstOrFail()->spares)->toHaveCount(0);
});

it('excludes the variant being edited from the copy-from source list', function () {
    $variant = VehicleVariantMaster::factory()->create(['name' => 'ITSELF']);

    $component = Livewire::test(Form::class)->call('load', $variant->id);

    // Editing has no copy-from UI, but the source list must never offer the row itself.
    expect($component->instance()->sourceVariants->pluck('id'))->not->toContain($variant->id);
});

it('inherits the model service interval when the variant has none', function () {
    $model = VehicleModelMaster::factory()->create([
        'service_interval_km' => 10000,
        'service_interval_months' => 12,
    ]);
    $variant = VehicleVariantMaster::factory()->create([
        'model_id' => $model->id,
        'service_interval_km' => null,
        'service_interval_months' => null,
    ]);

    expect($variant->effectiveServiceInterval())->toBe(['km' => 10000, 'months' => 12]);
});

it('lets a variant override the model interval, per figure', function () {
    $model = VehicleModelMaster::factory()->create([
        'service_interval_km' => 10000,
        'service_interval_months' => 12,
    ]);
    // Diesel: tighter km, same months (months left null → inherits).
    $variant = VehicleVariantMaster::factory()->create([
        'model_id' => $model->id,
        'service_interval_km' => 7500,
        'service_interval_months' => null,
    ]);

    expect($variant->effectiveServiceInterval())->toBe(['km' => 7500, 'months' => 12]);
});

it('persists the variant service-interval override from the form', function () {
    $model = VehicleModelMaster::factory()->create();

    Livewire::test(Form::class)
        ->set('model_id', $model->id)
        ->set('name', 'diesel')
        ->set('service_interval_km', 7500)
        ->set('service_interval_months', 6)
        ->call('save')
        ->assertHasNoErrors();

    $v = VehicleVariantMaster::where('name', 'DIESEL')->firstOrFail();
    expect($v->service_interval_km)->toBe(7500)
        ->and($v->service_interval_months)->toBe(6);
});
