<?php

use App\Modules\AccountGroupMaster\Livewire\Form;
use App\Modules\AccountGroupMaster\Livewire\Index;
use App\Modules\AccountGroupMaster\Models\AccountGroupMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    AccountGroupMaster::factory()->count(3)->create();

    $this->get(route('account-group-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name or code', function () {
    AccountGroupMaster::factory()->create(['name' => 'INCOME GROUP', 'code' => 'INC']);
    AccountGroupMaster::factory()->create(['name' => 'EXPENSE GROUP', 'code' => 'EXP']);

    Livewire::test(Index::class)->set('search', 'INCOME')
        ->assertSee('INCOME GROUP')
        ->assertDontSee('EXPENSE GROUP');

    Livewire::test(Index::class)->set('search', 'exp')
        ->assertSee('EXPENSE GROUP')
        ->assertDontSee('INCOME GROUP');
});

it('filters by active status', function () {
    AccountGroupMaster::factory()->create(['name' => 'GROUP ENABLED']);
    AccountGroupMaster::factory()->inactive()->create(['name' => 'GROUP DISABLED']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('GROUP ENABLED')
        ->assertDontSee('GROUP DISABLED');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('GROUP DISABLED')
        ->assertDontSee('GROUP ENABLED');
});

it('creates an account group with code (capital typing)', function () {
    Livewire::test(Form::class)
        ->set('name', 'income')
        ->set('code', 'inc')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('account-group-master:saved');

    $record = AccountGroupMaster::firstOrFail();
    expect($record->name)->toBe('INCOME')
        ->and($record->code)->toBe('INC')
        ->and($record->is_active)->toBeTrue();
});

it('updates an existing account group', function () {
    $record = AccountGroupMaster::factory()->create(['name' => 'OLD GROUP']);

    Livewire::test(Form::class)
        ->dispatch('account-group-master:edit', id: $record->id)
        ->set('name', 'updated group')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED GROUP');
});

it('deletes an account group from the index', function () {
    $record = AccountGroupMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(AccountGroupMaster::find($record->id))->toBeNull();
});

it('validates name is required and unique', function () {
    AccountGroupMaster::factory()->create(['name' => 'EXISTING GROUP']);

    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);

    Livewire::test(Form::class)
        ->set('name', 'EXISTING GROUP')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('allows updating an account group without triggering self-uniqueness conflict', function () {
    $record = AccountGroupMaster::factory()->create(['name' => 'INCOME']);

    Livewire::test(Form::class)
        ->dispatch('account-group-master:edit', id: $record->id)
        ->set('name', 'INCOME')
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('account-group-master.index'))->assertRedirect(route('login'));
});
