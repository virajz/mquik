<?php

use App\Models\User;
use App\Modules\WorkshopDepartmentMaster\Livewire\Form;
use App\Modules\WorkshopDepartmentMaster\Livewire\Index;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders the index page', function () {
    WorkshopDepartmentMaster::factory()->count(3)->create();

    $this->get(route('workshop-department-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name or code', function () {
    WorkshopDepartmentMaster::factory()->create(['name' => 'BODYSHOP BAY', 'code' => 'BDY']);
    WorkshopDepartmentMaster::factory()->create(['name' => 'TYRE BAY', 'code' => 'TYR']);

    Livewire::test(Index::class)->set('search', 'BODYSHOP')
        ->assertSee('BODYSHOP BAY')
        ->assertDontSee('TYRE BAY');

    Livewire::test(Index::class)->set('search', 'tyr') // case-insensitive via whereLike
        ->assertSee('TYRE BAY')
        ->assertDontSee('BODYSHOP BAY');
});

it('filters by active status', function () {
    WorkshopDepartmentMaster::factory()->create(['name' => 'BODYSHOP ENABLED']);
    WorkshopDepartmentMaster::factory()->inactive()->create(['name' => 'TYRE DISABLED']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('BODYSHOP ENABLED')
        ->assertDontSee('TYRE DISABLED');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('TYRE DISABLED')
        ->assertDontSee('BODYSHOP ENABLED');
});

it('creates a workshop department with capital typing', function () {
    Livewire::test(Form::class)
        ->set('name', 'bodyshop')
        ->set('code', 'bdy')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('workshop-department-master:saved');

    $record = WorkshopDepartmentMaster::firstOrFail();
    expect($record->name)->toBe('BODYSHOP')
        ->and($record->code)->toBe('BDY')
        ->and($record->is_active)->toBeTrue();
});

it('updates an existing workshop department', function () {
    $record = WorkshopDepartmentMaster::factory()->create(['name' => 'OLD NAME']);

    Livewire::test(Form::class)
        ->dispatch('workshop-department-master:edit', id: $record->id)
        ->set('name', 'updated name')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED NAME');
});

it('deletes a workshop department from the index', function () {
    $record = WorkshopDepartmentMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(WorkshopDepartmentMaster::find($record->id))->toBeNull();
});

it('validates name is required and unique', function () {
    WorkshopDepartmentMaster::factory()->create(['name' => 'EXISTING']);

    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);

    Livewire::test(Form::class)
        ->set('name', 'EXISTING')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('allows updating a workshop department without triggering self-uniqueness conflict', function () {
    $record = WorkshopDepartmentMaster::factory()->create(['name' => 'BODYSHOP']);

    Livewire::test(Form::class)
        ->dispatch('workshop-department-master:edit', id: $record->id)
        ->set('name', 'BODYSHOP') // same name as the record being edited
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('workshop-department-master.index'))->assertRedirect(route('login'));
});
