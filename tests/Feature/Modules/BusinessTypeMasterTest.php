<?php

use App\Modules\BusinessTypeMaster\Livewire\Form;
use App\Modules\BusinessTypeMaster\Livewire\Index;
use App\Modules\BusinessTypeMaster\Models\BusinessTypeMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    BusinessTypeMaster::factory()->count(3)->create();

    $this->get(route('business-type-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name or code', function () {
    BusinessTypeMaster::factory()->create(['name' => 'CORPORATE TYPE', 'code' => 'CORP']);
    BusinessTypeMaster::factory()->create(['name' => 'WALKING TYPE', 'code' => 'WLK']);

    Livewire::test(Index::class)->set('search', 'CORPORATE')
        ->assertSee('CORPORATE TYPE')
        ->assertDontSee('WALKING TYPE');

    Livewire::test(Index::class)->set('search', 'wlk')
        ->assertSee('WALKING TYPE')
        ->assertDontSee('CORPORATE TYPE');
});

it('filters by active status', function () {
    BusinessTypeMaster::factory()->create(['name' => 'TYPE ENABLED']);
    BusinessTypeMaster::factory()->inactive()->create(['name' => 'TYPE DISABLED']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('TYPE ENABLED')
        ->assertDontSee('TYPE DISABLED');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('TYPE DISABLED')
        ->assertDontSee('TYPE ENABLED');
});

it('creates a business type with code (capital typing)', function () {
    Livewire::test(Form::class)
        ->set('name', 'corporate')
        ->set('code', 'corp')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('business-type-master:saved');

    $record = BusinessTypeMaster::firstOrFail();
    expect($record->name)->toBe('CORPORATE')
        ->and($record->code)->toBe('CORP')
        ->and($record->is_active)->toBeTrue();
});

it('updates an existing business type', function () {
    $record = BusinessTypeMaster::factory()->create(['name' => 'OLD TYPE']);

    Livewire::test(Form::class)
        ->dispatch('business-type-master:edit', id: $record->id)
        ->set('name', 'updated type')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED TYPE');
});

it('deletes a business type from the index', function () {
    $record = BusinessTypeMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(BusinessTypeMaster::find($record->id))->toBeNull();
});

it('validates name is required and unique', function () {
    BusinessTypeMaster::factory()->create(['name' => 'EXISTING TYPE']);

    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);

    Livewire::test(Form::class)
        ->set('name', 'EXISTING TYPE')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('allows updating a business type without triggering self-uniqueness conflict', function () {
    $record = BusinessTypeMaster::factory()->create(['name' => 'CORPORATE']);

    Livewire::test(Form::class)
        ->dispatch('business-type-master:edit', id: $record->id)
        ->set('name', 'CORPORATE')
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('business-type-master.index'))->assertRedirect(route('login'));
});
