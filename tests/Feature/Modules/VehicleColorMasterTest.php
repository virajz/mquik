<?php

use App\Modules\VehicleColorMaster\Livewire\Form;
use App\Modules\VehicleColorMaster\Livewire\Index;
use App\Modules\VehicleColorMaster\Models\VehicleColorMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    VehicleColorMaster::factory()->count(3)->create();
    $this->get(route('vehicle-color-master.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('creates a color', function () {
    Livewire::test(Form::class)
        ->set('name', 'pearl white')
        ->set('hex_code', '#f8f8f8')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('vehicle-color-master:saved');

    $r = VehicleColorMaster::firstOrFail();
    expect($r->name)->toBe('PEARL WHITE')->and($r->hex_code)->toBe('#F8F8F8');
});

it('validates hex format', function () {
    Livewire::test(Form::class)
        ->set('name', 'TEST')
        ->set('hex_code', 'not-a-hex')
        ->call('save')
        ->assertHasErrors(['hex_code']);
});

it('updates a color', function () {
    $r = VehicleColorMaster::factory()->create(['name' => 'OLD']);
    Livewire::test(Form::class)
        ->dispatch('vehicle-color-master:edit', id: $r->id)
        ->set('name', 'updated')
        ->call('save')
        ->assertHasNoErrors();
    expect($r->fresh()->name)->toBe('UPDATED');
});

it('deletes a color', function () {
    $r = VehicleColorMaster::factory()->create();
    Livewire::test(Index::class)->call('delete', $r->id);
    expect(VehicleColorMaster::find($r->id))->toBeNull();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('vehicle-color-master.index'))->assertRedirect(route('login'));
});
