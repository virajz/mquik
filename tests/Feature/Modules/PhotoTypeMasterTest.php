<?php

use App\Modules\PhotoTypeMaster\Livewire\Form;
use App\Modules\PhotoTypeMaster\Livewire\Index;
use App\Modules\PhotoTypeMaster\Models\PhotoTypeMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    PhotoTypeMaster::factory()->count(3)->create();

    $this->get(route('photo-type-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name or code', function () {
    PhotoTypeMaster::factory()->create(['name' => 'ENGINEEE NOISE', 'code' => 'ENG']);
    PhotoTypeMaster::factory()->create(['name' => 'BRAKEEE PROBLEM', 'code' => 'BRK']);

    Livewire::test(Index::class)->set('search', 'ENGINEEE')
        ->assertSee('ENGINEEE NOISE')
        ->assertDontSee('BRAKEEE PROBLEM');

    Livewire::test(Index::class)->set('search', 'brk') // case-insensitive via whereLike
        ->assertSee('BRAKEEE PROBLEM')
        ->assertDontSee('ENGINEEE NOISE');
});

it('filters by active status', function () {
    PhotoTypeMaster::factory()->create(['name' => 'ENGINE ENABLED']);
    PhotoTypeMaster::factory()->inactive()->create(['name' => 'BRAKE DISABLED']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('ENGINE ENABLED')
        ->assertDontSee('BRAKE DISABLED');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('BRAKE DISABLED')
        ->assertDontSee('ENGINE ENABLED');
});

it('sorts by name alphabetically by default', function () {
    PhotoTypeMaster::factory()->create(['name' => 'ZEBRA TYPE']);
    PhotoTypeMaster::factory()->create(['name' => 'ALPHA TYPE']);
    PhotoTypeMaster::factory()->create(['name' => 'MIDDLE TYPE']);

    $html = Livewire::test(Index::class)->html();

    $alphaPos = strpos($html, 'ALPHA TYPE');
    $middlePos = strpos($html, 'MIDDLE TYPE');
    $zebraPos = strpos($html, 'ZEBRA TYPE');

    expect($alphaPos)->toBeLessThan($middlePos)
        ->and($middlePos)->toBeLessThan($zebraPos);
});

it('creates a type with code', function () {
    Livewire::test(Form::class)
        ->set('name', 'engine noise')
        ->set('code', 'eng')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('photo-type-master:saved');

    $record = PhotoTypeMaster::firstOrFail();
    expect($record->name)->toBe('ENGINE NOISE')
        ->and($record->code)->toBe('ENG')
        ->and($record->is_active)->toBeTrue();
});

it('updates an existing type', function () {
    $record = PhotoTypeMaster::factory()->create(['name' => 'OLD NAME']);

    Livewire::test(Form::class)
        ->dispatch('photo-type-master:edit', id: $record->id)
        ->set('name', 'updated name')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED NAME');
});

it('stores the tab group (uppercased) and sort order', function () {
    Livewire::test(Form::class)
        ->set('name', 'front bumper')
        ->set('group', 'exterior')
        ->set('sort_order', 5)
        ->call('save')
        ->assertHasNoErrors();

    $record = PhotoTypeMaster::firstOrFail();
    expect($record->group)->toBe('EXTERIOR')
        ->and($record->sort_order)->toBe(5);
});

it('deletes a type from the index', function () {
    $record = PhotoTypeMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(PhotoTypeMaster::find($record->id))->toBeNull();
});

it('validates name is required and unique', function () {
    PhotoTypeMaster::factory()->create(['name' => 'EXISTING']);

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
    $record = PhotoTypeMaster::factory()->create(['name' => 'ENGINE NOISE']);

    Livewire::test(Form::class)
        ->dispatch('photo-type-master:edit', id: $record->id)
        ->set('name', 'ENGINE NOISE') // same name as the record being edited
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('photo-type-master.index'))->assertRedirect(route('login'));
});
