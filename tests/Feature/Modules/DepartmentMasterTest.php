<?php

use App\Modules\DepartmentMaster\Livewire\Form;
use App\Modules\DepartmentMaster\Livewire\Index;
use App\Modules\DepartmentMaster\Models\DepartmentMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    DepartmentMaster::factory()->count(3)->create();

    $this->get(route('department-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name or code', function () {
    DepartmentMaster::factory()->create(['name' => 'SERVICE DEPT', 'code' => 'SVC']);
    DepartmentMaster::factory()->create(['name' => 'BODYSHOP DEPT', 'code' => 'BDY']);

    Livewire::test(Index::class)->set('search', 'SERVICE')
        ->assertSee('SERVICE DEPT')
        ->assertDontSee('BODYSHOP DEPT');

    Livewire::test(Index::class)->set('search', 'bdy') // case-insensitive via whereLike
        ->assertSee('BODYSHOP DEPT')
        ->assertDontSee('SERVICE DEPT');
});

it('filters by active status', function () {
    DepartmentMaster::factory()->create(['name' => 'SERVICE ENABLED']);
    DepartmentMaster::factory()->inactive()->create(['name' => 'BODYSHOP DISABLED']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('SERVICE ENABLED')
        ->assertDontSee('BODYSHOP DISABLED');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('BODYSHOP DISABLED')
        ->assertDontSee('SERVICE ENABLED');
});

it('creates a department with capital typing', function () {
    Livewire::test(Form::class)
        ->set('name', 'service')
        ->set('code', 'svc')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('department-master:saved');

    $record = DepartmentMaster::firstOrFail();
    expect($record->name)->toBe('SERVICE')
        ->and($record->code)->toBe('SVC')
        ->and($record->is_active)->toBeTrue();
});

it('updates an existing department', function () {
    $record = DepartmentMaster::factory()->create(['name' => 'OLD NAME']);

    Livewire::test(Form::class)
        ->dispatch('department-master:edit', id: $record->id)
        ->set('name', 'updated name')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED NAME');
});

it('deletes a department from the index', function () {
    $record = DepartmentMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(DepartmentMaster::find($record->id))->toBeNull();
});

it('validates name is required and unique', function () {
    DepartmentMaster::factory()->create(['name' => 'EXISTING']);

    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);

    Livewire::test(Form::class)
        ->set('name', 'EXISTING')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('allows updating a department without triggering self-uniqueness conflict', function () {
    $record = DepartmentMaster::factory()->create(['name' => 'SERVICE']);

    Livewire::test(Form::class)
        ->dispatch('department-master:edit', id: $record->id)
        ->set('name', 'SERVICE') // same name as the record being edited
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('department-master.index'))->assertRedirect(route('login'));
});
