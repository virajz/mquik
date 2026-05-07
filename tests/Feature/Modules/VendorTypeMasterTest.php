<?php

use App\Modules\VendorTypeMaster\Livewire\Form;
use App\Modules\VendorTypeMaster\Livewire\Index;
use App\Modules\VendorTypeMaster\Models\VendorTypeMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    VendorTypeMaster::factory()->count(3)->create();

    $this->get(route('vendor-type-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name or code', function () {
    VendorTypeMaster::factory()->create(['name' => 'SPARE PARTS DIV', 'code' => 'SP']);
    VendorTypeMaster::factory()->create(['name' => 'OSL DIVISION', 'code' => 'OSL']);

    Livewire::test(Index::class)->set('search', 'SPARE PARTS')
        ->assertSee('SPARE PARTS DIV')
        ->assertDontSee('OSL DIVISION');

    Livewire::test(Index::class)->set('search', 'osl') // case-insensitive via whereLike
        ->assertSee('OSL DIVISION')
        ->assertDontSee('SPARE PARTS DIV');
});

it('filters by active status', function () {
    // Use distinct strings (not substrings of each other).
    VendorTypeMaster::factory()->create(['name' => 'SPARE ENABLED']);
    VendorTypeMaster::factory()->inactive()->create(['name' => 'OSL DISABLED']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('SPARE ENABLED')
        ->assertDontSee('OSL DISABLED');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('OSL DISABLED')
        ->assertDontSee('SPARE ENABLED');
});

it('sorts by name alphabetically by default', function () {
    VendorTypeMaster::factory()->create(['name' => 'ZEBRA TYPE']);
    VendorTypeMaster::factory()->create(['name' => 'ALPHA TYPE']);
    VendorTypeMaster::factory()->create(['name' => 'MIDDLE TYPE']);

    $html = Livewire::test(Index::class)->html();

    $alphaPos = strpos($html, 'ALPHA TYPE');
    $middlePos = strpos($html, 'MIDDLE TYPE');
    $zebraPos = strpos($html, 'ZEBRA TYPE');

    expect($alphaPos)->toBeLessThan($middlePos)
        ->and($middlePos)->toBeLessThan($zebraPos);
});

it('creates a type with code', function () {
    Livewire::test(Form::class)
        ->set('name', 'spare parts')
        ->set('code', 'sp')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('vendor-type-master:saved');

    $record = VendorTypeMaster::firstOrFail();
    expect($record->name)->toBe('SPARE PARTS')
        ->and($record->code)->toBe('SP')
        ->and($record->is_active)->toBeTrue();
});

it('updates an existing type', function () {
    $record = VendorTypeMaster::factory()->create(['name' => 'OLD NAME']);

    Livewire::test(Form::class)
        ->dispatch('vendor-type-master:edit', id: $record->id)
        ->set('name', 'updated name')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED NAME');
});

it('deletes a type from the index', function () {
    $record = VendorTypeMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(VendorTypeMaster::find($record->id))->toBeNull();
});

it('validates name is required and unique', function () {
    VendorTypeMaster::factory()->create(['name' => 'EXISTING']);

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
    $record = VendorTypeMaster::factory()->create(['name' => 'SPARE PARTS']);

    Livewire::test(Form::class)
        ->dispatch('vendor-type-master:edit', id: $record->id)
        ->set('name', 'SPARE PARTS') // same name as the record being edited
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('vendor-type-master.index'))->assertRedirect(route('login'));
});
