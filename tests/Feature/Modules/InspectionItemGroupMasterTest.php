<?php

use App\Modules\InspectionItemGroupMaster\Livewire\Form;
use App\Modules\InspectionItemGroupMaster\Livewire\Index;
use App\Modules\InspectionItemGroupMaster\Models\InspectionItemGroupMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    InspectionItemGroupMaster::factory()->count(3)->create();

    $this->get(route('inspection-item-group-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name or code', function () {
    InspectionItemGroupMaster::factory()->create(['name' => 'ENGINE GROUP', 'code' => 'ENG']);
    InspectionItemGroupMaster::factory()->create(['name' => 'BRAKE GROUP', 'code' => 'BRK']);

    Livewire::test(Index::class)->set('search', 'ENGINE')
        ->assertSee('ENGINE GROUP')
        ->assertDontSee('BRAKE GROUP');

    Livewire::test(Index::class)->set('search', 'brk') // case-insensitive via whereLike
        ->assertSee('BRAKE GROUP')
        ->assertDontSee('ENGINE GROUP');
});

it('filters by active status', function () {
    InspectionItemGroupMaster::factory()->create(['name' => 'ENGINE ENABLED']);
    InspectionItemGroupMaster::factory()->inactive()->create(['name' => 'BRAKE DISABLED']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('ENGINE ENABLED')
        ->assertDontSee('BRAKE DISABLED');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('BRAKE DISABLED')
        ->assertDontSee('ENGINE ENABLED');
});

it('creates a group with capital typing', function () {
    Livewire::test(Form::class)
        ->set('name', 'engine')
        ->set('code', 'eng')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('inspection-item-group-master:saved');

    $record = InspectionItemGroupMaster::firstOrFail();
    expect($record->name)->toBe('ENGINE')
        ->and($record->code)->toBe('ENG')
        ->and($record->is_active)->toBeTrue();
});

it('updates an existing group', function () {
    $record = InspectionItemGroupMaster::factory()->create(['name' => 'OLD NAME']);

    Livewire::test(Form::class)
        ->dispatch('inspection-item-group-master:edit', id: $record->id)
        ->set('name', 'updated name')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED NAME');
});

it('deletes a group from the index', function () {
    $record = InspectionItemGroupMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(InspectionItemGroupMaster::find($record->id))->toBeNull();
});

it('validates name is required and unique', function () {
    InspectionItemGroupMaster::factory()->create(['name' => 'EXISTING']);

    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);

    Livewire::test(Form::class)
        ->set('name', 'EXISTING')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('inspection-item-group-master.index'))->assertRedirect(route('login'));
});
