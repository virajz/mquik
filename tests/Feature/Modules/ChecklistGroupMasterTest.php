<?php

use App\Modules\ChecklistGroupMaster\Livewire\Form;
use App\Modules\ChecklistGroupMaster\Livewire\Index;
use App\Modules\ChecklistGroupMaster\Models\ChecklistGroupMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    ChecklistGroupMaster::factory()->count(3)->create();

    $this->get(route('checklist-group-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name or code', function () {
    ChecklistGroupMaster::factory()->create(['name' => 'DOCUMENT COLLECTION GRP', 'code' => 'DOC']);
    ChecklistGroupMaster::factory()->create(['name' => 'PRE DELIVERY GRP', 'code' => 'PD']);

    Livewire::test(Index::class)->set('search', 'DOCUMENT')
        ->assertSee('DOCUMENT COLLECTION GRP')
        ->assertDontSee('PRE DELIVERY GRP');

    Livewire::test(Index::class)->set('search', 'pd')
        ->assertSee('PRE DELIVERY GRP')
        ->assertDontSee('DOCUMENT COLLECTION GRP');
});

it('filters by active status', function () {
    ChecklistGroupMaster::factory()->create(['name' => 'GROUP ENABLED']);
    ChecklistGroupMaster::factory()->inactive()->create(['name' => 'GROUP DISABLED']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('GROUP ENABLED')
        ->assertDontSee('GROUP DISABLED');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('GROUP DISABLED')
        ->assertDontSee('GROUP ENABLED');
});

it('sorts by name alphabetically by default', function () {
    ChecklistGroupMaster::factory()->create(['name' => 'ZEBRA GROUP']);
    ChecklistGroupMaster::factory()->create(['name' => 'ALPHA GROUP']);
    ChecklistGroupMaster::factory()->create(['name' => 'MIDDLE GROUP']);

    $html = Livewire::test(Index::class)->html();

    $alphaPos = strpos($html, 'ALPHA GROUP');
    $middlePos = strpos($html, 'MIDDLE GROUP');
    $zebraPos = strpos($html, 'ZEBRA GROUP');

    expect($alphaPos)->toBeLessThan($middlePos)
        ->and($middlePos)->toBeLessThan($zebraPos);
});

it('creates a group with code', function () {
    Livewire::test(Form::class)
        ->set('name', 'document collection')
        ->set('code', 'doc')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('checklist-group-master:saved');

    $record = ChecklistGroupMaster::firstOrFail();
    expect($record->name)->toBe('DOCUMENT COLLECTION')
        ->and($record->code)->toBe('DOC')
        ->and($record->is_active)->toBeTrue();
});

it('updates an existing group', function () {
    $record = ChecklistGroupMaster::factory()->create(['name' => 'OLD NAME']);

    Livewire::test(Form::class)
        ->dispatch('checklist-group-master:edit', id: $record->id)
        ->set('name', 'updated name')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED NAME');
});

it('deletes a group from the index', function () {
    $record = ChecklistGroupMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(ChecklistGroupMaster::find($record->id))->toBeNull();
});

it('validates name is required and unique', function () {
    ChecklistGroupMaster::factory()->create(['name' => 'EXISTING']);

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
    $record = ChecklistGroupMaster::factory()->create(['name' => 'DOCUMENT COLLECTION']);

    Livewire::test(Form::class)
        ->dispatch('checklist-group-master:edit', id: $record->id)
        ->set('name', 'DOCUMENT COLLECTION')
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('checklist-group-master.index'))->assertRedirect(route('login'));
});
