<?php

use App\Modules\VehicleInventoryItemMaster\Livewire\Form;
use App\Modules\VehicleInventoryItemMaster\Livewire\Index;
use App\Modules\VehicleInventoryItemMaster\Models\VehicleInventoryItemMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    VehicleInventoryItemMaster::factory()->count(3)->create();

    $this->get(route('vehicle-inventory-item-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name or code', function () {
    VehicleInventoryItemMaster::factory()->create(['name' => 'SPARE WHEEL ITEM', 'code' => 'SW']);
    VehicleInventoryItemMaster::factory()->create(['name' => 'TOOL KIT ITEM', 'code' => 'TK']);

    Livewire::test(Index::class)->set('search', 'SPARE WHEEL')
        ->assertSee('SPARE WHEEL ITEM')
        ->assertDontSee('TOOL KIT ITEM');

    Livewire::test(Index::class)->set('search', 'tk')
        ->assertSee('TOOL KIT ITEM')
        ->assertDontSee('SPARE WHEEL ITEM');
});

it('filters by active status', function () {
    VehicleInventoryItemMaster::factory()->create(['name' => 'ENABLED ITEM']);
    VehicleInventoryItemMaster::factory()->inactive()->create(['name' => 'DISABLED ITEM']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('ENABLED ITEM')
        ->assertDontSee('DISABLED ITEM');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('DISABLED ITEM')
        ->assertDontSee('ENABLED ITEM');
});

it('sorts by name alphabetically by default', function () {
    VehicleInventoryItemMaster::factory()->create(['name' => 'ZEBRA ITEM']);
    VehicleInventoryItemMaster::factory()->create(['name' => 'ALPHA ITEM']);
    VehicleInventoryItemMaster::factory()->create(['name' => 'MIDDLE ITEM']);

    $html = Livewire::test(Index::class)->html();

    $alphaPos = strpos($html, 'ALPHA ITEM');
    $middlePos = strpos($html, 'MIDDLE ITEM');
    $zebraPos = strpos($html, 'ZEBRA ITEM');

    expect($alphaPos)->toBeLessThan($middlePos)
        ->and($middlePos)->toBeLessThan($zebraPos);
});

it('creates an item with code', function () {
    Livewire::test(Form::class)
        ->set('name', 'spare wheel')
        ->set('code', 'sw')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('vehicle-inventory-item-master:saved');

    $record = VehicleInventoryItemMaster::firstOrFail();
    expect($record->name)->toBe('SPARE WHEEL')
        ->and($record->code)->toBe('SW')
        ->and($record->is_active)->toBeTrue();
});

it('updates an existing item', function () {
    $record = VehicleInventoryItemMaster::factory()->create(['name' => 'OLD NAME']);

    Livewire::test(Form::class)
        ->dispatch('vehicle-inventory-item-master:edit', id: $record->id)
        ->set('name', 'updated name')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED NAME');
});

it('deletes an item from the index', function () {
    $record = VehicleInventoryItemMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(VehicleInventoryItemMaster::find($record->id))->toBeNull();
});

it('validates name is required and unique', function () {
    VehicleInventoryItemMaster::factory()->create(['name' => 'EXISTING']);

    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);

    Livewire::test(Form::class)
        ->set('name', 'EXISTING')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('allows updating an item without triggering self-uniqueness conflict', function () {
    $record = VehicleInventoryItemMaster::factory()->create(['name' => 'SPARE WHEEL']);

    Livewire::test(Form::class)
        ->dispatch('vehicle-inventory-item-master:edit', id: $record->id)
        ->set('name', 'SPARE WHEEL')
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('vehicle-inventory-item-master.index'))->assertRedirect(route('login'));
});
