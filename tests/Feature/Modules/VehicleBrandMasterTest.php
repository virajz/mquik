<?php

use App\Modules\VehicleBrandMaster\Livewire\Form;
use App\Modules\VehicleBrandMaster\Livewire\Index;
use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    VehicleBrandMaster::factory()->count(3)->create();
    $this->get(route('vehicle-brand-master.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('creates a vehicle brand', function () {
    Livewire::test(Form::class)
        ->set('name', 'maruti suzuki')
        ->set('code', 'mar')
        ->set('country', 'india')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('vehicle-brand-master:saved');

    $r = VehicleBrandMaster::firstOrFail();
    expect($r->name)->toBe('MARUTI SUZUKI')->and($r->code)->toBe('MAR')->and($r->country)->toBe('INDIA');
});

it('updates an existing brand', function () {
    $r = VehicleBrandMaster::factory()->create(['name' => 'OLD']);
    Livewire::test(Form::class)
        ->dispatch('vehicle-brand-master:edit', id: $r->id)
        ->set('name', 'updated')
        ->call('save')
        ->assertHasNoErrors();
    expect($r->fresh()->name)->toBe('UPDATED');
});

it('deletes a brand', function () {
    $r = VehicleBrandMaster::factory()->create();
    Livewire::test(Index::class)->call('delete', $r->id);
    expect(VehicleBrandMaster::find($r->id))->toBeNull();
});

it('validates name is required and unique', function () {
    VehicleBrandMaster::factory()->create(['name' => 'EXISTING']);
    Livewire::test(Form::class)->set('name', '')->call('save')->assertHasErrors(['name' => 'required']);
    Livewire::test(Form::class)->set('name', 'EXISTING')->call('save')->assertHasErrors(['name' => 'unique']);
});

it('searches by name code or country', function () {
    VehicleBrandMaster::factory()->create(['name' => 'MARUTI ABC', 'code' => 'MAR']);
    VehicleBrandMaster::factory()->create(['name' => 'HYUNDAI XYZ', 'country' => 'KOREA']);

    Livewire::test(Index::class)->set('search', 'maruti')->assertSee('MARUTI ABC')->assertDontSee('HYUNDAI XYZ');
    Livewire::test(Index::class)->set('search', 'KOREA')->assertSee('HYUNDAI XYZ')->assertDontSee('MARUTI ABC');
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('vehicle-brand-master.index'))->assertRedirect(route('login'));
});
