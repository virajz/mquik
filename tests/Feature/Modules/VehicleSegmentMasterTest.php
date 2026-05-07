<?php

use App\Modules\VehicleSegmentMaster\Livewire\Form;
use App\Modules\VehicleSegmentMaster\Livewire\Index;
use App\Modules\VehicleSegmentMaster\Models\VehicleSegmentMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    VehicleSegmentMaster::factory()->count(3)->create();

    $this->get(route('vehicle-segment-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name or code', function () {
    VehicleSegmentMaster::factory()->create(['name' => 'HATCHBACKKK', 'code' => 'HCH']);
    VehicleSegmentMaster::factory()->create(['name' => 'SEDANNNN', 'code' => 'SDN']);

    Livewire::test(Index::class)->set('search', 'HATCHBACKKK')
        ->assertSee('HATCHBACKKK')
        ->assertDontSee('SEDANNNN');

    Livewire::test(Index::class)->set('search', 'sdn') // case-insensitive via whereLike
        ->assertSee('SEDANNNN')
        ->assertDontSee('HATCHBACKKK');
});

it('filters by active status', function () {
    VehicleSegmentMaster::factory()->create(['name' => 'HATCH ENABLED']);
    VehicleSegmentMaster::factory()->inactive()->create(['name' => 'SEDAN DISABLED']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('HATCH ENABLED')
        ->assertDontSee('SEDAN DISABLED');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('SEDAN DISABLED')
        ->assertDontSee('HATCH ENABLED');
});

it('creates a segment with code', function () {
    Livewire::test(Form::class)
        ->set('name', 'hatchback')
        ->set('code', 'hch')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('vehicle-segment-master:saved');

    $record = VehicleSegmentMaster::firstOrFail();
    expect($record->name)->toBe('HATCHBACK')
        ->and($record->code)->toBe('HCH')
        ->and($record->is_active)->toBeTrue();
});

it('updates an existing segment', function () {
    $record = VehicleSegmentMaster::factory()->create(['name' => 'OLD NAME']);

    Livewire::test(Form::class)
        ->dispatch('vehicle-segment-master:edit', id: $record->id)
        ->set('name', 'updated name')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED NAME');
});

it('deletes a segment from the index', function () {
    $record = VehicleSegmentMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(VehicleSegmentMaster::find($record->id))->toBeNull();
});

it('validates name is required and unique', function () {
    VehicleSegmentMaster::factory()->create(['name' => 'EXISTING SEGMENT']);

    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);

    Livewire::test(Form::class)
        ->set('name', 'EXISTING SEGMENT')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('blocks duplicate code', function () {
    VehicleSegmentMaster::factory()->create(['name' => 'FIRST SEG', 'code' => 'DUP']);

    Livewire::test(Form::class)
        ->set('name', 'SECOND SEG')
        ->set('code', 'DUP')
        ->call('save')
        ->assertHasErrors(['code' => 'unique']);
});

it('allows updating a segment without triggering self-uniqueness conflict', function () {
    $record = VehicleSegmentMaster::factory()->create(['name' => 'SUV']);

    Livewire::test(Form::class)
        ->dispatch('vehicle-segment-master:edit', id: $record->id)
        ->set('name', 'SUV') // same name as the record being edited
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('vehicle-segment-master.index'))->assertRedirect(route('login'));
});
