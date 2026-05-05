<?php

use App\Models\User;
use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use App\Modules\VehicleModelMaster\Livewire\Form;
use App\Modules\VehicleModelMaster\Livewire\Index;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders the index page', function () {
    VehicleModelMaster::factory()->count(3)->create();
    $this->get(route('vehicle-model-master.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('creates a model with brand FK', function () {
    $brand = VehicleBrandMaster::factory()->create(['name' => 'MARUTI']);

    Livewire::test(Form::class)
        ->set('brand_id', $brand->id)
        ->set('name', 'swift')
        ->set('segment', 'hatchback')
        ->set('fuel_type', 'petrol')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('vehicle-model-master:saved');

    $r = VehicleModelMaster::firstOrFail();
    expect($r->name)->toBe('SWIFT')
        ->and($r->brand_id)->toBe($brand->id)
        ->and($r->segment)->toBe('hatchback')
        ->and($r->fuel_type)->toBe('petrol');
});

it('requires brand_id', function () {
    Livewire::test(Form::class)
        ->set('name', 'TEST')
        ->call('save')
        ->assertHasErrors(['brand_id']);
});

it('allows same model name across different brands', function () {
    $maruti = VehicleBrandMaster::factory()->create(['name' => 'MARUTI']);
    $nissan = VehicleBrandMaster::factory()->create(['name' => 'NISSAN']);

    VehicleModelMaster::factory()->forBrand($maruti)->create(['name' => 'SWIFT']);

    Livewire::test(Form::class)
        ->set('brand_id', $nissan->id)
        ->set('name', 'SWIFT')
        ->call('save')
        ->assertHasNoErrors();

    expect(VehicleModelMaster::where('name', 'SWIFT')->count())->toBe(2);
});

it('blocks duplicate model name within same brand', function () {
    $brand = VehicleBrandMaster::factory()->create();
    VehicleModelMaster::factory()->forBrand($brand)->create(['name' => 'SWIFT']);

    Livewire::test(Form::class)
        ->set('brand_id', $brand->id)
        ->set('name', 'SWIFT')
        ->call('save')
        ->assertHasErrors(['name']);
});

it('filters by brand', function () {
    $maruti = VehicleBrandMaster::factory()->create(['name' => 'MARUTI']);
    $hyundai = VehicleBrandMaster::factory()->create(['name' => 'HYUNDAI']);

    VehicleModelMaster::factory()->forBrand($maruti)->create(['name' => 'SWIFT MARUTI']);
    VehicleModelMaster::factory()->forBrand($hyundai)->create(['name' => 'CRETA HYUNDAI']);

    Livewire::test(Index::class)
        ->set('brandFilter', (string) $maruti->id)
        ->assertSee('SWIFT MARUTI')
        ->assertDontSee('CRETA HYUNDAI');
});

it('updates a model', function () {
    $r = VehicleModelMaster::factory()->create(['name' => 'OLD']);
    Livewire::test(Form::class)
        ->dispatch('vehicle-model-master:edit', id: $r->id)
        ->set('name', 'updated')
        ->call('save')
        ->assertHasNoErrors();
    expect($r->fresh()->name)->toBe('UPDATED');
});

it('deletes a model', function () {
    $r = VehicleModelMaster::factory()->create();
    Livewire::test(Index::class)->call('delete', $r->id);
    expect(VehicleModelMaster::find($r->id))->toBeNull();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('vehicle-model-master.index'))->assertRedirect(route('login'));
});
