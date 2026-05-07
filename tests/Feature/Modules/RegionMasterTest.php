<?php

use App\Modules\RegionMaster\Livewire\Form;
use App\Modules\RegionMaster\Livewire\Index;
use App\Modules\RegionMaster\Models\RegionMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    RegionMaster::factory()->count(3)->create();

    $this->get(route('region-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name or code', function () {
    RegionMaster::factory()->create(['name' => 'GUJJJARAT', 'code' => 'GJX']);
    RegionMaster::factory()->create(['name' => 'MAHHHARASHTRA', 'code' => 'MHX']);

    // Note: assertDontSee is unreliable here because the Form's parent dropdown
    // (rendered as a child component) lists every region. Check the table rows
    // via viewData('rows') to confirm filter behavior.
    $rows = Livewire::test(Index::class)->set('search', 'GUJJ')->viewData('rows');
    expect($rows->pluck('name')->all())->toBe(['GUJJJARAT']);

    $rows = Livewire::test(Index::class)->set('search', 'mhx')->viewData('rows');
    expect($rows->pluck('name')->all())->toBe(['MAHHHARASHTRA']);
});

it('filters by status', function () {
    RegionMaster::factory()->create(['name' => 'BOSCH ENABLED']);
    RegionMaster::factory()->inactive()->create(['name' => 'DENSO DISABLED']);

    $rows = Livewire::test(Index::class)->set('statusFilter', 'active')->viewData('rows');
    expect($rows->pluck('name')->all())->toBe(['BOSCH ENABLED']);

    $rows = Livewire::test(Index::class)->set('statusFilter', 'inactive')->viewData('rows');
    expect($rows->pluck('name')->all())->toBe(['DENSO DISABLED']);
});

it('filters by kind', function () {
    $state = RegionMaster::factory()->create(['name' => 'FILTER STATE']);
    RegionMaster::factory()->city($state->id)->create(['name' => 'FILTER CITY']);

    $rows = Livewire::test(Index::class)->set('kindFilter', 'state')->viewData('rows');
    expect($rows->pluck('name')->all())->toBe(['FILTER STATE']);

    $rows = Livewire::test(Index::class)->set('kindFilter', 'city')->viewData('rows');
    expect($rows->pluck('name')->all())->toBe(['FILTER CITY']);
});

it('creates a state with no parent', function () {
    Livewire::test(Form::class)
        ->set('kind', 'state')
        ->set('name', 'gujarat')
        ->set('code', 'gj')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('region-master:saved');

    $record = RegionMaster::firstOrFail();
    expect($record->kind)->toBe('state')
        ->and($record->name)->toBe('GUJARAT')
        ->and($record->code)->toBe('GJ')
        ->and($record->parent_id)->toBeNull();
});

it('creates a city under a state', function () {
    $state = RegionMaster::factory()->asState()->create(['name' => 'GUJARAT']);

    Livewire::test(Form::class)
        ->set('kind', 'city')
        ->set('parent_id', $state->id)
        ->set('name', 'ahmedabad')
        ->call('save')
        ->assertHasNoErrors();

    $city = RegionMaster::where('name', 'AHMEDABAD')->firstOrFail();
    expect($city->kind)->toBe('city')
        ->and($city->parent_id)->toBe($state->id)
        ->and($city->parent->kind)->toBe('state');
});

it('rejects a city without a parent', function () {
    Livewire::test(Form::class)
        ->set('kind', 'city')
        ->set('parent_id', null)
        ->set('name', 'ahmedabad')
        ->call('save')
        ->assertHasErrors('parent_id');
});

it('rejects a state with a parent', function () {
    $existing = RegionMaster::factory()->asState()->create(['name' => 'EXISTING STATE']);

    Livewire::test(Form::class)
        ->set('kind', 'state')
        ->set('parent_id', $existing->id)
        ->set('name', 'invalid state')
        ->call('save')
        ->assertHasErrors('parent_id');
});

it('rejects a city whose parent is not a state', function () {
    $state = RegionMaster::factory()->asState()->create();
    $city = RegionMaster::factory()->city($state->id)->create();

    // Try to put a city under another city — invalid.
    Livewire::test(Form::class)
        ->set('kind', 'city')
        ->set('parent_id', $city->id)
        ->set('name', 'new city')
        ->call('save')
        ->assertHasErrors('parent_id');
});

it('rejects an area whose parent is a state (must be a city)', function () {
    $state = RegionMaster::factory()->asState()->create();

    Livewire::test(Form::class)
        ->set('kind', 'area')
        ->set('parent_id', $state->id)
        ->set('name', 'satellite')
        ->call('save')
        ->assertHasErrors('parent_id');
});

it('rejects a pincode whose parent is a city (must be an area)', function () {
    $state = RegionMaster::factory()->asState()->create();
    $city = RegionMaster::factory()->city($state->id)->create();

    Livewire::test(Form::class)
        ->set('kind', 'pincode')
        ->set('parent_id', $city->id)
        ->set('name', '380015')
        ->call('save')
        ->assertHasErrors('parent_id');
});

it('rejects an invalid kind', function () {
    Livewire::test(Form::class)
        ->set('kind', 'continent')
        ->set('name', 'asia')
        ->call('save')
        ->assertHasErrors('kind');
});

it('updates an existing region', function () {
    $record = RegionMaster::factory()->create(['name' => 'OLD NAME']);

    Livewire::test(Form::class)
        ->dispatch('region-master:edit', id: $record->id)
        ->set('name', 'updated name')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED NAME');
});

it('deletes a region from the index', function () {
    $record = RegionMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(RegionMaster::find($record->id))->toBeNull();
});

it('validates name is required', function () {
    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

it('exposes the kinds list on the model', function () {
    expect(RegionMaster::kinds())->toBe(['state', 'city', 'area', 'pincode']);
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('region-master.index'))->assertRedirect(route('login'));
});
