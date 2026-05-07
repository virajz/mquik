<?php

use App\Modules\ConsumableDepartmentMaster\Livewire\Form;
use App\Modules\ConsumableDepartmentMaster\Livewire\Index;
use App\Modules\ConsumableDepartmentMaster\Models\ConsumableDepartmentMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    ConsumableDepartmentMaster::factory()->count(3)->create();

    $this->get(route('consumable-department-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name or code', function () {
    ConsumableDepartmentMaster::factory()->create(['name' => 'SERVICEEE BAY', 'code' => 'SBY']);
    ConsumableDepartmentMaster::factory()->create(['name' => 'TYREEE BAY', 'code' => 'TYB']);

    Livewire::test(Index::class)->set('search', 'SERVICEEE')
        ->assertSee('SERVICEEE BAY')
        ->assertDontSee('TYREEE BAY');

    Livewire::test(Index::class)->set('search', 'tyb') // case-insensitive via whereLike
        ->assertSee('TYREEE BAY')
        ->assertDontSee('SERVICEEE BAY');
});

it('filters by active status', function () {
    ConsumableDepartmentMaster::factory()->create(['name' => 'BOSCH ENABLED']);
    ConsumableDepartmentMaster::factory()->inactive()->create(['name' => 'DENSO DISABLED']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('BOSCH ENABLED')
        ->assertDontSee('DENSO DISABLED');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('DENSO DISABLED')
        ->assertDontSee('BOSCH ENABLED');
});

it('sorts by name alphabetically by default', function () {
    ConsumableDepartmentMaster::factory()->create(['name' => 'ZEBRA BAY']);
    ConsumableDepartmentMaster::factory()->create(['name' => 'ALPHA BAY']);
    ConsumableDepartmentMaster::factory()->create(['name' => 'MIDDLE BAY']);

    $html = Livewire::test(Index::class)->html();

    $alphaPos = strpos($html, 'ALPHA BAY');
    $middlePos = strpos($html, 'MIDDLE BAY');
    $zebraPos = strpos($html, 'ZEBRA BAY');

    expect($alphaPos)->toBeLessThan($middlePos)
        ->and($middlePos)->toBeLessThan($zebraPos);
});

it('creates a department with code', function () {
    Livewire::test(Form::class)
        ->set('name', 'service bay')
        ->set('code', 'sby')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('consumable-department-master:saved');

    $record = ConsumableDepartmentMaster::firstOrFail();
    expect($record->name)->toBe('SERVICE BAY')
        ->and($record->code)->toBe('SBY')
        ->and($record->is_active)->toBeTrue();
});

it('updates an existing department', function () {
    $record = ConsumableDepartmentMaster::factory()->create(['name' => 'OLD NAME']);

    Livewire::test(Form::class)
        ->dispatch('consumable-department-master:edit', id: $record->id)
        ->set('name', 'updated name')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED NAME');
});

it('deletes a department from the index', function () {
    $record = ConsumableDepartmentMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(ConsumableDepartmentMaster::find($record->id))->toBeNull();
});

it('validates name is required and unique', function () {
    ConsumableDepartmentMaster::factory()->create(['name' => 'EXISTING']);

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
    $record = ConsumableDepartmentMaster::factory()->create(['name' => 'SERVICE BAY']);

    Livewire::test(Form::class)
        ->dispatch('consumable-department-master:edit', id: $record->id)
        ->set('name', 'SERVICE BAY')
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('consumable-department-master.index'))->assertRedirect(route('login'));
});
