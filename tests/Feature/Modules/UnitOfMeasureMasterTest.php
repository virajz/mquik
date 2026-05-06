<?php

use App\Models\User;
use App\Modules\UnitOfMeasureMaster\Livewire\Form;
use App\Modules\UnitOfMeasureMaster\Livewire\Index;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders the index page', function () {
    UnitOfMeasureMaster::factory()->count(3)->create();

    $this->get(route('unit-of-measure-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name or code', function () {
    UnitOfMeasureMaster::factory()->create(['name' => 'BARRELS', 'code' => 'BRL']);
    UnitOfMeasureMaster::factory()->create(['name' => 'TONNES', 'code' => 'TON']);

    Livewire::test(Index::class)->set('search', 'BARRELS')
        ->assertSee('BARRELS')
        ->assertDontSee('TONNES');

    Livewire::test(Index::class)->set('search', 'ton') // case-insensitive via whereLike
        ->assertSee('TONNES')
        ->assertDontSee('BARRELS');
});

it('filters by active status', function () {
    UnitOfMeasureMaster::factory()->create(['name' => 'UNIT ENABLED']);
    UnitOfMeasureMaster::factory()->inactive()->create(['name' => 'UNIT DISABLED']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('UNIT ENABLED')
        ->assertDontSee('UNIT DISABLED');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('UNIT DISABLED')
        ->assertDontSee('UNIT ENABLED');
});

it('creates a unit of measure with capital typing', function () {
    Livewire::test(Form::class)
        ->set('name', 'barrels')
        ->set('code', 'brl')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('unit-of-measure-master:saved');

    $record = UnitOfMeasureMaster::firstOrFail();
    expect($record->name)->toBe('BARRELS')
        ->and($record->code)->toBe('BRL')
        ->and($record->is_active)->toBeTrue();
});

it('updates an existing unit of measure', function () {
    $record = UnitOfMeasureMaster::factory()->create(['name' => 'OLD NAME']);

    Livewire::test(Form::class)
        ->dispatch('unit-of-measure-master:edit', id: $record->id)
        ->set('name', 'updated name')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED NAME');
});

it('deletes a unit of measure from the index', function () {
    $record = UnitOfMeasureMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(UnitOfMeasureMaster::find($record->id))->toBeNull();
});

it('validates name is required and unique', function () {
    UnitOfMeasureMaster::factory()->create(['name' => 'EXISTING']);

    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);

    Livewire::test(Form::class)
        ->set('name', 'EXISTING')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('allows updating a unit of measure without triggering self-uniqueness conflict', function () {
    $record = UnitOfMeasureMaster::factory()->create(['name' => 'BARRELS']);

    Livewire::test(Form::class)
        ->dispatch('unit-of-measure-master:edit', id: $record->id)
        ->set('name', 'BARRELS') // same name as the record being edited
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('unit-of-measure-master.index'))->assertRedirect(route('login'));
});
