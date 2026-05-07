<?php

use App\Modules\GstTypeMaster\Livewire\Form;
use App\Modules\GstTypeMaster\Livewire\Index;
use App\Modules\GstTypeMaster\Models\GstTypeMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    GstTypeMaster::factory()->count(3)->create();

    $this->get(route('gst-type-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name or code', function () {
    GstTypeMaster::factory()->create(['name' => 'REGULAR TYPE', 'code' => 'REG']);
    GstTypeMaster::factory()->create(['name' => 'COMPOSITION TYPE', 'code' => 'COMP']);

    Livewire::test(Index::class)->set('search', 'REGULAR')
        ->assertSee('REGULAR TYPE')
        ->assertDontSee('COMPOSITION TYPE');

    Livewire::test(Index::class)->set('search', 'comp')
        ->assertSee('COMPOSITION TYPE')
        ->assertDontSee('REGULAR TYPE');
});

it('filters by active status', function () {
    GstTypeMaster::factory()->create(['name' => 'GST ENABLED']);
    GstTypeMaster::factory()->inactive()->create(['name' => 'GST DISABLED']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('GST ENABLED')
        ->assertDontSee('GST DISABLED');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('GST DISABLED')
        ->assertDontSee('GST ENABLED');
});

it('creates a GST type with code (capital typing)', function () {
    Livewire::test(Form::class)
        ->set('name', 'regular')
        ->set('code', 'reg')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('gst-type-master:saved');

    $record = GstTypeMaster::firstOrFail();
    expect($record->name)->toBe('REGULAR')
        ->and($record->code)->toBe('REG')
        ->and($record->is_active)->toBeTrue();
});

it('updates an existing GST type', function () {
    $record = GstTypeMaster::factory()->create(['name' => 'OLD GST']);

    Livewire::test(Form::class)
        ->dispatch('gst-type-master:edit', id: $record->id)
        ->set('name', 'updated gst')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED GST');
});

it('deletes a GST type from the index', function () {
    $record = GstTypeMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(GstTypeMaster::find($record->id))->toBeNull();
});

it('validates name is required and unique', function () {
    GstTypeMaster::factory()->create(['name' => 'EXISTING GST']);

    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);

    Livewire::test(Form::class)
        ->set('name', 'EXISTING GST')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('allows updating a GST type without triggering self-uniqueness conflict', function () {
    $record = GstTypeMaster::factory()->create(['name' => 'REGULAR']);

    Livewire::test(Form::class)
        ->dispatch('gst-type-master:edit', id: $record->id)
        ->set('name', 'REGULAR')
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('gst-type-master.index'))->assertRedirect(route('login'));
});
