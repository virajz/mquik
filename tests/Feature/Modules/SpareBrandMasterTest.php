<?php

use App\Modules\SpareBrandMaster\Livewire\Form;
use App\Modules\SpareBrandMaster\Livewire\Index;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    SpareBrandMaster::factory()->count(3)->create();

    $this->get(route('spare-brand-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name or code', function () {
    SpareBrandMaster::factory()->create(['name' => 'BOSCHHH BRAND', 'code' => 'BSH']);
    SpareBrandMaster::factory()->create(['name' => 'DENSOOO LTD', 'code' => 'DNS']);

    Livewire::test(Index::class)->set('search', 'BOSCHHH')
        ->assertSee('BOSCHHH BRAND')
        ->assertDontSee('DENSOOO LTD');

    Livewire::test(Index::class)->set('search', 'dns') // case-insensitive via whereLike
        ->assertSee('DENSOOO LTD')
        ->assertDontSee('BOSCHHH BRAND');
});

it('filters by active status', function () {
    // Use distinct strings (not substrings of each other) — "INACTIVE BRAND" contains "ACTIVE BRAND".
    SpareBrandMaster::factory()->create(['name' => 'BOSCH ENABLED']);
    SpareBrandMaster::factory()->inactive()->create(['name' => 'DENSO DISABLED']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('BOSCH ENABLED')
        ->assertDontSee('DENSO DISABLED');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('DENSO DISABLED')
        ->assertDontSee('BOSCH ENABLED');
});

it('sorts by name alphabetically by default', function () {
    SpareBrandMaster::factory()->create(['name' => 'ZEBRA INC']);
    SpareBrandMaster::factory()->create(['name' => 'ALPHA INC']);
    SpareBrandMaster::factory()->create(['name' => 'MIDDLE INC']);

    $html = Livewire::test(Index::class)->html();

    $alphaPos = strpos($html, 'ALPHA INC');
    $middlePos = strpos($html, 'MIDDLE INC');
    $zebraPos = strpos($html, 'ZEBRA INC');

    expect($alphaPos)->toBeLessThan($middlePos)
        ->and($middlePos)->toBeLessThan($zebraPos);
});

it('creates a brand with code', function () {
    Livewire::test(Form::class)
        ->set('name', 'bosch')
        ->set('code', 'bsh')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('spare-brand-master:saved');

    $record = SpareBrandMaster::firstOrFail();
    expect($record->name)->toBe('BOSCH')
        ->and($record->code)->toBe('BSH')
        ->and($record->is_active)->toBeTrue();
});

it('updates an existing brand', function () {
    $record = SpareBrandMaster::factory()->create(['name' => 'OLD NAME']);

    Livewire::test(Form::class)
        ->dispatch('spare-brand-master:edit', id: $record->id)
        ->set('name', 'updated name')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED NAME');
});

it('deletes a brand from the index', function () {
    $record = SpareBrandMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(SpareBrandMaster::find($record->id))->toBeNull();
});

it('validates name is required and unique', function () {
    SpareBrandMaster::factory()->create(['name' => 'EXISTING']);

    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);

    Livewire::test(Form::class)
        ->set('name', 'EXISTING')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('allows updating a brand without triggering self-uniqueness conflict', function () {
    $record = SpareBrandMaster::factory()->create(['name' => 'BOSCH']);

    Livewire::test(Form::class)
        ->dispatch('spare-brand-master:edit', id: $record->id)
        ->set('name', 'BOSCH') // same name as the record being edited
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('spare-brand-master.index'))->assertRedirect(route('login'));
});
