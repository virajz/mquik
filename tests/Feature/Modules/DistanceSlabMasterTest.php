<?php

use App\Modules\DistanceSlabMaster\Livewire\Form;
use App\Modules\DistanceSlabMaster\Livewire\Index;
use App\Modules\DistanceSlabMaster\Models\DistanceSlabMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    DistanceSlabMaster::factory()->count(3)->create();

    $this->get(route('distance-slab-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name or code', function () {
    DistanceSlabMaster::factory()->create(['name' => 'ENGINEEE NOISE', 'code' => 'ENG']);
    DistanceSlabMaster::factory()->create(['name' => 'BRAKEEE PROBLEM', 'code' => 'BRK']);

    Livewire::test(Index::class)->set('search', 'ENGINEEE')
        ->assertSee('ENGINEEE NOISE')
        ->assertDontSee('BRAKEEE PROBLEM');

    Livewire::test(Index::class)->set('search', 'brk') // case-insensitive via whereLike
        ->assertSee('BRAKEEE PROBLEM')
        ->assertDontSee('ENGINEEE NOISE');
});

it('filters by active status', function () {
    DistanceSlabMaster::factory()->create(['name' => 'ENGINE ENABLED']);
    DistanceSlabMaster::factory()->inactive()->create(['name' => 'BRAKE DISABLED']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('ENGINE ENABLED')
        ->assertDontSee('BRAKE DISABLED');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('BRAKE DISABLED')
        ->assertDontSee('ENGINE ENABLED');
});

it('lists bands in distance order, not alphabetically', function () {
    DistanceSlabMaster::factory()->create(['name' => 'ALPHA BAND', 'min_km' => 16, 'max_km' => 25]);
    DistanceSlabMaster::factory()->create(['name' => 'ZULU BAND', 'min_km' => 0, 'max_km' => 5]);

    $html = Livewire::test(Index::class)->html();

    expect(strpos($html, 'ZULU BAND'))->toBeLessThan(strpos($html, 'ALPHA BAND'));
});

it('renders the band label and resolves a distance to its slab', function () {
    $mid = DistanceSlabMaster::factory()->create(['name' => '6-15 KM', 'min_km' => 6, 'max_km' => 15, 'charge_amount' => 300]);
    $open = DistanceSlabMaster::factory()->create(['name' => '26 KM +', 'min_km' => 26, 'max_km' => null, 'charge_amount' => 500]);

    expect($mid->band())->toBe('6–15 KM')
        ->and($open->band())->toBe('26 KM & above')
        ->and(DistanceSlabMaster::forDistance(12)->id)->toBe($mid->id)
        // Open-ended top slab catches anything above its floor.
        ->and(DistanceSlabMaster::forDistance(900)->id)->toBe($open->id)
        // A gap between bands resolves to nothing rather than guessing.
        ->and(DistanceSlabMaster::forDistance(20))->toBeNull();
});

it('rejects a max below the min', function () {
    Livewire::test(Form::class)
        ->set('name', 'BACKWARDS')
        ->set('min_km', 20)
        ->set('max_km', 5)
        ->call('save')
        ->assertHasErrors(['max_km']);
});

it('creates a slab with band and charge', function () {
    Livewire::test(Form::class)
        ->set('name', '0-5 km')
        ->set('code', 'd1')
        ->set('min_km', 0)
        ->set('max_km', 5)
        ->set('charge_amount', '200')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('distance-slab-master:saved');

    $record = DistanceSlabMaster::firstOrFail();
    expect($record->name)->toBe('0-5 KM')
        ->and($record->code)->toBe('D1')
        ->and($record->min_km)->toBe(0)
        ->and($record->max_km)->toBe(5)
        ->and((float) $record->charge_amount)->toBe(200.0)
        ->and($record->is_active)->toBeTrue();
});

it('updates an existing type', function () {
    $record = DistanceSlabMaster::factory()->create(['name' => 'OLD NAME']);

    Livewire::test(Form::class)
        ->dispatch('distance-slab-master:edit', id: $record->id)
        ->set('name', 'updated name')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED NAME');
});

it('deletes a type from the index', function () {
    $record = DistanceSlabMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(DistanceSlabMaster::find($record->id))->toBeNull();
});

it('validates name is required and unique', function () {
    DistanceSlabMaster::factory()->create(['name' => 'EXISTING']);

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
    $record = DistanceSlabMaster::factory()->create(['name' => 'ENGINE NOISE']);

    Livewire::test(Form::class)
        ->dispatch('distance-slab-master:edit', id: $record->id)
        ->set('name', 'ENGINE NOISE') // same name as the record being edited
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('distance-slab-master.index'))->assertRedirect(route('login'));
});
