<?php

use App\Models\User;
use App\Modules\InventoryGroupMaster\Livewire\Form;
use App\Modules\InventoryGroupMaster\Livewire\Index;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders the index page', function () {
    InventoryGroupMaster::factory()->count(3)->create();

    $this->get(route('inventory-group-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name or code', function () {
    InventoryGroupMaster::factory()->create(['name' => 'BRAKEEE GROUP', 'code' => 'BRK']);
    InventoryGroupMaster::factory()->create(['name' => 'ENGINEEE GROUP', 'code' => 'ENG']);

    // Note: assertDontSee is unreliable here because the Form's parent dropdown
    // (rendered as a child component) lists every group. Check the table rows
    // via viewData('rows') to confirm filter behavior.
    $rows = Livewire::test(Index::class)->set('search', 'BRAKEEE')->viewData('rows');
    expect($rows->pluck('name')->all())->toBe(['BRAKEEE GROUP']);

    $rows = Livewire::test(Index::class)->set('search', 'eng')->viewData('rows');
    expect($rows->pluck('name')->all())->toBe(['ENGINEEE GROUP']);
});

it('filters by active status', function () {
    InventoryGroupMaster::factory()->create(['name' => 'BOSCH ENABLED']);
    InventoryGroupMaster::factory()->inactive()->create(['name' => 'DENSO DISABLED']);

    $rows = Livewire::test(Index::class)->set('statusFilter', 'active')->viewData('rows');
    expect($rows->pluck('name')->all())->toBe(['BOSCH ENABLED']);

    $rows = Livewire::test(Index::class)->set('statusFilter', 'inactive')->viewData('rows');
    expect($rows->pluck('name')->all())->toBe(['DENSO DISABLED']);
});

it('sorts by name alphabetically by default', function () {
    InventoryGroupMaster::factory()->create(['name' => 'ZEBRA GROUP']);
    InventoryGroupMaster::factory()->create(['name' => 'ALPHA GROUP']);
    InventoryGroupMaster::factory()->create(['name' => 'MIDDLE GROUP']);

    $html = Livewire::test(Index::class)->html();

    expect(strpos($html, 'ALPHA GROUP'))->toBeLessThan(strpos($html, 'MIDDLE GROUP'))
        ->and(strpos($html, 'MIDDLE GROUP'))->toBeLessThan(strpos($html, 'ZEBRA GROUP'));
});

it('creates a top-level group with code', function () {
    Livewire::test(Form::class)
        ->set('name', 'brake')
        ->set('code', 'brk')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('inventory-group-master:saved');

    $record = InventoryGroupMaster::firstOrFail();
    expect($record->name)->toBe('BRAKE')
        ->and($record->code)->toBe('BRK')
        ->and($record->parent_id)->toBeNull()
        ->and($record->is_active)->toBeTrue();
});

it('creates a sub-group under a parent', function () {
    $parent = InventoryGroupMaster::factory()->create(['name' => 'BRAKE']);

    Livewire::test(Form::class)
        ->set('name', 'brake pads')
        ->set('parent_id', $parent->id)
        ->call('save')
        ->assertHasNoErrors();

    $child = InventoryGroupMaster::where('name', 'BRAKE PADS')->firstOrFail();
    expect($child->parent_id)->toBe($parent->id);
    expect($child->parent->name)->toBe('BRAKE');
});

it('updates an existing group', function () {
    $record = InventoryGroupMaster::factory()->create(['name' => 'OLD NAME']);

    Livewire::test(Form::class)
        ->dispatch('inventory-group-master:edit', id: $record->id)
        ->set('name', 'updated name')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED NAME');
});

it('prevents picking yourself as parent', function () {
    $record = InventoryGroupMaster::factory()->create(['name' => 'BRAKE']);

    Livewire::test(Form::class)
        ->dispatch('inventory-group-master:edit', id: $record->id)
        ->set('parent_id', $record->id)
        ->call('save')
        ->assertHasErrors('parent_id');
});

it('deletes a group from the index', function () {
    $record = InventoryGroupMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(InventoryGroupMaster::find($record->id))->toBeNull();
});

it('validates name is required and unique', function () {
    InventoryGroupMaster::factory()->create(['name' => 'EXISTING']);

    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);

    Livewire::test(Form::class)
        ->set('name', 'EXISTING')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('allows updating a group without triggering self-uniqueness conflict', function () {
    $record = InventoryGroupMaster::factory()->create(['name' => 'BRAKE']);

    Livewire::test(Form::class)
        ->dispatch('inventory-group-master:edit', id: $record->id)
        ->set('name', 'BRAKE')
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('inventory-group-master.index'))->assertRedirect(route('login'));
});
