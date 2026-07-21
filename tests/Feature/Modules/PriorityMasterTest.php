<?php

use App\Modules\PriorityMaster\Livewire\Form;
use App\Modules\PriorityMaster\Livewire\Index;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    PriorityMaster::factory()->count(3)->create();

    $this->get(route('priority-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name or code', function () {
    PriorityMaster::factory()->create(['name' => 'ENGINEEE NOISE', 'code' => 'ENG']);
    PriorityMaster::factory()->create(['name' => 'BRAKEEE PROBLEM', 'code' => 'BRK']);

    Livewire::test(Index::class)->set('search', 'ENGINEEE')
        ->assertSee('ENGINEEE NOISE')
        ->assertDontSee('BRAKEEE PROBLEM');

    Livewire::test(Index::class)->set('search', 'brk') // case-insensitive via whereLike
        ->assertSee('BRAKEEE PROBLEM')
        ->assertDontSee('ENGINEEE NOISE');
});

it('filters by active status', function () {
    PriorityMaster::factory()->create(['name' => 'ENGINE ENABLED']);
    PriorityMaster::factory()->inactive()->create(['name' => 'BRAKE DISABLED']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('ENGINE ENABLED')
        ->assertDontSee('BRAKE DISABLED');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('BRAKE DISABLED')
        ->assertDontSee('ENGINE ENABLED');
});

it('sorts by severity order, not alphabetically, by default', function () {
    // Alphabetically this would read HIGH, NORMAL, URGENT — sort_order fixes that.
    PriorityMaster::factory()->create(['name' => 'URGENT', 'sort_order' => 30]);
    PriorityMaster::factory()->create(['name' => 'NORMAL', 'sort_order' => 10]);
    PriorityMaster::factory()->create(['name' => 'HIGH', 'sort_order' => 20]);

    $html = Livewire::test(Index::class)->html();

    expect(strpos($html, 'NORMAL'))->toBeLessThan(strpos($html, 'HIGH'))
        ->and(strpos($html, 'HIGH'))->toBeLessThan(strpos($html, 'URGENT'));
});

it('persists the severity order from the form', function () {
    Livewire::test(Form::class)
        ->set('name', 'urgent')
        ->set('sort_order', 30)
        ->call('save')
        ->assertHasNoErrors();

    expect(PriorityMaster::firstOrFail()->sort_order)->toBe(30);
});

it('creates a type with code', function () {
    Livewire::test(Form::class)
        ->set('name', 'engine noise')
        ->set('code', 'eng')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('priority-master:saved');

    $record = PriorityMaster::firstOrFail();
    expect($record->name)->toBe('ENGINE NOISE')
        ->and($record->code)->toBe('ENG')
        ->and($record->is_active)->toBeTrue();
});

it('updates an existing type', function () {
    $record = PriorityMaster::factory()->create(['name' => 'OLD NAME']);

    Livewire::test(Form::class)
        ->dispatch('priority-master:edit', id: $record->id)
        ->set('name', 'updated name')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED NAME');
});

it('deletes a type from the index', function () {
    $record = PriorityMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(PriorityMaster::find($record->id))->toBeNull();
});

it('validates name is required and unique', function () {
    PriorityMaster::factory()->create(['name' => 'EXISTING']);

    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);

    Livewire::test(Form::class)
        ->set('name', 'EXISTING')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('allows updating a type without triggering self-uniqueness conflict', function () {
    $record = PriorityMaster::factory()->create(['name' => 'ENGINE NOISE']);

    Livewire::test(Form::class)
        ->dispatch('priority-master:edit', id: $record->id)
        ->set('name', 'ENGINE NOISE') // same name as the record being edited
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('priority-master.index'))->assertRedirect(route('login'));
});
