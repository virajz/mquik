<?php

use App\Modules\DesignationMaster\Livewire\Form;
use App\Modules\DesignationMaster\Livewire\Index;
use App\Modules\DesignationMaster\Models\DesignationMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    DesignationMaster::factory()->count(3)->create();

    $this->get(route('designation-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name or code', function () {
    DesignationMaster::factory()->create(['name' => 'TECHNICIAN ROLE', 'code' => 'TCH']);
    DesignationMaster::factory()->create(['name' => 'CASHIER ROLE', 'code' => 'CSH']);

    Livewire::test(Index::class)->set('search', 'TECHNICIAN')
        ->assertSee('TECHNICIAN ROLE')
        ->assertDontSee('CASHIER ROLE');

    Livewire::test(Index::class)->set('search', 'csh') // case-insensitive via whereLike
        ->assertSee('CASHIER ROLE')
        ->assertDontSee('TECHNICIAN ROLE');
});

it('filters by active status', function () {
    DesignationMaster::factory()->create(['name' => 'TECHNICIAN ENABLED']);
    DesignationMaster::factory()->inactive()->create(['name' => 'CASHIER DISABLED']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('TECHNICIAN ENABLED')
        ->assertDontSee('CASHIER DISABLED');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('CASHIER DISABLED')
        ->assertDontSee('TECHNICIAN ENABLED');
});

it('creates a designation with capital typing', function () {
    Livewire::test(Form::class)
        ->set('name', 'technician')
        ->set('code', 'tch')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('designation-master:saved');

    $record = DesignationMaster::firstOrFail();
    expect($record->name)->toBe('TECHNICIAN')
        ->and($record->code)->toBe('TCH')
        ->and($record->is_active)->toBeTrue();
});

it('updates an existing designation', function () {
    $record = DesignationMaster::factory()->create(['name' => 'OLD NAME']);

    Livewire::test(Form::class)
        ->dispatch('designation-master:edit', id: $record->id)
        ->set('name', 'updated name')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED NAME');
});

it('deletes a designation from the index', function () {
    $record = DesignationMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(DesignationMaster::find($record->id))->toBeNull();
});

it('validates name is required and unique', function () {
    DesignationMaster::factory()->create(['name' => 'EXISTING']);

    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);

    Livewire::test(Form::class)
        ->set('name', 'EXISTING')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('allows updating a designation without triggering self-uniqueness conflict', function () {
    $record = DesignationMaster::factory()->create(['name' => 'TECHNICIAN']);

    Livewire::test(Form::class)
        ->dispatch('designation-master:edit', id: $record->id)
        ->set('name', 'TECHNICIAN') // same name as the record being edited
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('designation-master.index'))->assertRedirect(route('login'));
});
