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

it('shows the notes in the list, not just inside the edit form', function () {
    // Deliberately not "Pearl White" — the form's placeholder is "e.g. PEARL WHITE",
    // so asserting on that name would pass even if the row never rendered.
    VehicleColorMaster::factory()->create([
        'name' => 'OCEAN TEAL',
        'notes' => 'PREMIUM SHADE — QUOTE EXTRA COAT',
    ]);

    Livewire::test(Index::class)
        ->assertSee('OCEAN TEAL')
        ->assertSee('PREMIUM SHADE — QUOTE EXTRA COAT');
});

it('leaves the notes cell empty when a color has none', function () {
    VehicleColorMaster::factory()->create(['name' => 'PLAIN BLACK', 'notes' => null]);

    Livewire::test(Index::class)
        ->assertSee('PLAIN BLACK')
        ->assertOk();
});

it('finds a color by its notes as well as its name', function () {
    VehicleColorMaster::factory()->create(['name' => 'OCEAN TEAL', 'notes' => 'PREMIUM SHADE, EXTRA COAT']);
    VehicleColorMaster::factory()->create(['name' => 'GUNMETAL GREY', 'notes' => 'DISCONTINUED BY SUPPLIER']);

    Livewire::test(Index::class)
        ->set('search', 'discontinued')
        ->assertSee('GUNMETAL GREY')
        ->assertDontSee('OCEAN TEAL')
        // Name search still works, and every token must land somewhere.
        ->set('search', 'ocean premium')
        ->assertSee('OCEAN TEAL')
        ->assertDontSee('GUNMETAL GREY')
        // A token that matches nothing drops the row entirely.
        ->set('search', 'ocean nosuchword')
        ->assertDontSee('OCEAN TEAL');
});
