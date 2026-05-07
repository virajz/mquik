<?php

use App\Modules\CourierCompanyMaster\Livewire\Form;
use App\Modules\CourierCompanyMaster\Livewire\Index;
use App\Modules\CourierCompanyMaster\Models\CourierCompanyMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    CourierCompanyMaster::factory()->count(3)->create();

    $this->get(route('courier-company-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name or code', function () {
    CourierCompanyMaster::factory()->create(['name' => 'DTDC EXPRESS', 'code' => 'DTDC']);
    CourierCompanyMaster::factory()->create(['name' => 'BLUEDART INTL', 'code' => 'BD']);

    Livewire::test(Index::class)->set('search', 'DTDC')
        ->assertSee('DTDC EXPRESS')
        ->assertDontSee('BLUEDART INTL');

    Livewire::test(Index::class)->set('search', 'bd')
        ->assertSee('BLUEDART INTL')
        ->assertDontSee('DTDC EXPRESS');
});

it('filters by active status', function () {
    CourierCompanyMaster::factory()->create(['name' => 'COURIER ENABLED']);
    CourierCompanyMaster::factory()->inactive()->create(['name' => 'COURIER DISABLED']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('COURIER ENABLED')
        ->assertDontSee('COURIER DISABLED');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('COURIER DISABLED')
        ->assertDontSee('COURIER ENABLED');
});

it('creates a courier with code (capital typing)', function () {
    Livewire::test(Form::class)
        ->set('name', 'dtdc')
        ->set('code', 'dtdc')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('courier-company-master:saved');

    $record = CourierCompanyMaster::firstOrFail();
    expect($record->name)->toBe('DTDC')
        ->and($record->code)->toBe('DTDC')
        ->and($record->is_active)->toBeTrue();
});

it('updates an existing courier', function () {
    $record = CourierCompanyMaster::factory()->create(['name' => 'OLD COURIER']);

    Livewire::test(Form::class)
        ->dispatch('courier-company-master:edit', id: $record->id)
        ->set('name', 'updated courier')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED COURIER');
});

it('deletes a courier from the index', function () {
    $record = CourierCompanyMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(CourierCompanyMaster::find($record->id))->toBeNull();
});

it('validates name is required and unique', function () {
    CourierCompanyMaster::factory()->create(['name' => 'EXISTING COURIER']);

    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);

    Livewire::test(Form::class)
        ->set('name', 'EXISTING COURIER')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('allows updating a courier without triggering self-uniqueness conflict', function () {
    $record = CourierCompanyMaster::factory()->create(['name' => 'DTDC']);

    Livewire::test(Form::class)
        ->dispatch('courier-company-master:edit', id: $record->id)
        ->set('name', 'DTDC')
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('courier-company-master.index'))->assertRedirect(route('login'));
});
