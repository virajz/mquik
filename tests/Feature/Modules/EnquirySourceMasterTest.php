<?php

use App\Models\User;
use App\Modules\EnquirySourceMaster\Livewire\Form;
use App\Modules\EnquirySourceMaster\Livewire\Index;
use App\Modules\EnquirySourceMaster\Models\EnquirySourceMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders the index page', function () {
    EnquirySourceMaster::factory()->count(3)->create();

    $this->get(route('enquiry-source-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name or code', function () {
    EnquirySourceMaster::factory()->create(['name' => 'WALKIN SOURCE', 'code' => 'WLK']);
    EnquirySourceMaster::factory()->create(['name' => 'PHONEEE SOURCE', 'code' => 'PHN']);

    Livewire::test(Index::class)->set('search', 'WALKIN')
        ->assertSee('WALKIN SOURCE')
        ->assertDontSee('PHONEEE SOURCE');

    Livewire::test(Index::class)->set('search', 'phn') // case-insensitive via whereLike
        ->assertSee('PHONEEE SOURCE')
        ->assertDontSee('WALKIN SOURCE');
});

it('filters by active status', function () {
    EnquirySourceMaster::factory()->create(['name' => 'WALKIN ENABLED']);
    EnquirySourceMaster::factory()->inactive()->create(['name' => 'PHONEEE DISABLED']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('WALKIN ENABLED')
        ->assertDontSee('PHONEEE DISABLED');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('PHONEEE DISABLED')
        ->assertDontSee('WALKIN ENABLED');
});

it('sorts by name alphabetically by default', function () {
    EnquirySourceMaster::factory()->create(['name' => 'ZEBRA SOURCE']);
    EnquirySourceMaster::factory()->create(['name' => 'ALPHA SOURCE']);
    EnquirySourceMaster::factory()->create(['name' => 'MIDDLE SOURCE']);

    $html = Livewire::test(Index::class)->html();

    $alphaPos = strpos($html, 'ALPHA SOURCE');
    $middlePos = strpos($html, 'MIDDLE SOURCE');
    $zebraPos = strpos($html, 'ZEBRA SOURCE');

    expect($alphaPos)->toBeLessThan($middlePos)
        ->and($middlePos)->toBeLessThan($zebraPos);
});

it('creates a source with code', function () {
    Livewire::test(Form::class)
        ->set('name', 'walk-in')
        ->set('code', 'wlk')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('enquiry-source-master:saved');

    $record = EnquirySourceMaster::firstOrFail();
    expect($record->name)->toBe('WALK-IN')
        ->and($record->code)->toBe('WLK')
        ->and($record->is_active)->toBeTrue();
});

it('updates an existing source', function () {
    $record = EnquirySourceMaster::factory()->create(['name' => 'OLD NAME']);

    Livewire::test(Form::class)
        ->dispatch('enquiry-source-master:edit', id: $record->id)
        ->set('name', 'updated name')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED NAME');
});

it('deletes a source from the index', function () {
    $record = EnquirySourceMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(EnquirySourceMaster::find($record->id))->toBeNull();
});

it('validates name is required and unique', function () {
    EnquirySourceMaster::factory()->create(['name' => 'EXISTING']);

    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);

    Livewire::test(Form::class)
        ->set('name', 'EXISTING')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('allows updating a source without triggering self-uniqueness conflict', function () {
    $record = EnquirySourceMaster::factory()->create(['name' => 'WALK-IN']);

    Livewire::test(Form::class)
        ->dispatch('enquiry-source-master:edit', id: $record->id)
        ->set('name', 'WALK-IN') // same name as the record being edited
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('enquiry-source-master.index'))->assertRedirect(route('login'));
});
