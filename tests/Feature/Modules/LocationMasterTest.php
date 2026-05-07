<?php

use App\Modules\LocationMaster\Livewire\Form;
use App\Modules\LocationMaster\Livewire\Index;
use App\Modules\LocationMaster\Models\LocationMaster;
use App\Modules\RegionMaster\Models\RegionMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
    $this->state = RegionMaster::firstOrCreate(
        ['kind' => 'state', 'parent_id' => null, 'name' => 'GUJARAT'],
        ['code' => 'GJ', 'is_active' => true],
    );
    $this->city = RegionMaster::firstOrCreate(
        ['kind' => 'city', 'parent_id' => $this->state->id, 'name' => 'AHMEDABAD'],
        ['code' => 'AMD', 'is_active' => true],
    );
});

it('renders the index page', function () {
    LocationMaster::factory()->count(3)->create();

    $this->get(route('location-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name, code, or GSTIN', function () {
    LocationMaster::factory()->create(['name' => 'SATELLITE BRANCHHH', 'code' => 'SATX', 'gstin' => '24ABCDE1234F1Z5']);
    LocationMaster::factory()->create(['name' => 'SG HIGHWAY BRANCHHH', 'code' => 'SGHX', 'gstin' => '27FGHIJ5678K1Z9']);

    Livewire::test(Index::class)->set('search', 'SATELLITE BRANCHHH')
        ->assertSee('SATELLITE BRANCHHH')
        ->assertDontSee('SG HIGHWAY BRANCHHH');

    Livewire::test(Index::class)->set('search', 'sghx') // case-insensitive
        ->assertSee('SG HIGHWAY BRANCHHH')
        ->assertDontSee('SATELLITE BRANCHHH');

    Livewire::test(Index::class)->set('search', '27FGHIJ') // gstin search
        ->assertSee('SG HIGHWAY BRANCHHH')
        ->assertDontSee('SATELLITE BRANCHHH');
});

it('filters by active status', function () {
    LocationMaster::factory()->create(['name' => 'LOC ENABLED', 'code' => 'LOCABC1']);
    LocationMaster::factory()->inactive()->create(['name' => 'LOC DISABLED', 'code' => 'LOCABC2']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('LOC ENABLED')
        ->assertDontSee('LOC DISABLED');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('LOC DISABLED')
        ->assertDontSee('LOC ENABLED');
});

it('creates a location with all fields, capital typing, and FK resolution', function () {
    Livewire::test(Form::class)
        ->set('code', 'sat')
        ->set('name', 'satellite branch')
        ->set('is_head_office', true)
        ->set('address', 'block a, iscon cross road')
        ->set('city_id', $this->city->id)
        ->set('state_id', $this->state->id)
        ->set('pincode', '380015')
        ->set('phone', '9876543210')
        ->set('email', 'sat@mquik.in')
        ->set('gstin', '24ABCDE1234F1Z5')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('location-master:saved');

    $record = LocationMaster::firstOrFail();
    expect($record->code)->toBe('SAT')
        ->and($record->name)->toBe('SATELLITE BRANCH')
        ->and($record->is_head_office)->toBeTrue()
        ->and($record->address)->toBe('BLOCK A, ISCON CROSS ROAD')
        ->and($record->city_id)->toBe($this->city->id)
        ->and($record->state_id)->toBe($this->state->id)
        ->and($record->pincode)->toBe('380015') // not uppercased
        ->and($record->phone)->toBe('9876543210') // not uppercased
        ->and($record->email)->toBe('sat@mquik.in') // not uppercased
        ->and($record->gstin)->toBe('24ABCDE1234F1Z5') // uppercased (already was)
        ->and($record->city->name)->toBe('AHMEDABAD')
        ->and($record->state->name)->toBe('GUJARAT');
});

it('updates an existing location', function () {
    $record = LocationMaster::factory()->create(['name' => 'OLD NAME', 'code' => 'OLDC']);

    Livewire::test(Form::class)
        ->dispatch('location-master:edit', id: $record->id)
        ->set('name', 'updated name')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED NAME');
});

it('deletes a location from the index', function () {
    $record = LocationMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(LocationMaster::find($record->id))->toBeNull();
});

it('requires name and code', function () {
    Livewire::test(Form::class)
        ->set('name', '')
        ->set('code', '')
        ->call('save')
        ->assertHasErrors(['name', 'code']);
});

it('blocks duplicate code', function () {
    LocationMaster::factory()->create(['code' => 'DUPLOC']);

    Livewire::test(Form::class)
        ->set('code', 'DUPLOC')
        ->set('name', 'NEW BRANCH')
        ->call('save')
        ->assertHasErrors(['code' => 'unique']);
});

it('blocks duplicate gstin', function () {
    LocationMaster::factory()->create(['code' => 'LOC1', 'gstin' => '24ABCDE1234F1Z5']);

    Livewire::test(Form::class)
        ->set('code', 'LOC2')
        ->set('name', 'ANOTHER BRANCH')
        ->set('gstin', '24ABCDE1234F1Z5')
        ->call('save')
        ->assertHasErrors(['gstin' => 'unique']);
});

it('rejects malformed GSTIN', function () {
    Livewire::test(Form::class)
        ->set('code', 'BADGST')
        ->set('name', 'BAD GSTIN BRANCH')
        ->set('gstin', 'NOT-A-VALID-GSTIN')
        ->call('save')
        ->assertHasErrors(['gstin']);
});

it('allows updating a location without triggering self-uniqueness conflict', function () {
    $record = LocationMaster::factory()->create(['code' => 'SELF', 'gstin' => '24ABCDE1234F1Z5']);

    Livewire::test(Form::class)
        ->dispatch('location-master:edit', id: $record->id)
        ->set('code', 'SELF')
        ->set('gstin', '24ABCDE1234F1Z5')
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('location-master.index'))->assertRedirect(route('login'));
});
