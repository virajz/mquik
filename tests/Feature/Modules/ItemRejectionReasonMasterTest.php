<?php

use App\Modules\ItemRejectionReasonMaster\Livewire\Form;
use App\Modules\ItemRejectionReasonMaster\Livewire\Index;
use App\Modules\ItemRejectionReasonMaster\Models\ItemRejectionReasonMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    ItemRejectionReasonMaster::factory()->count(3)->create();

    $this->get(route('item-rejection-reason-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name or code', function () {
    ItemRejectionReasonMaster::factory()->create(['name' => 'ENGINEEE NOISE', 'code' => 'ENG']);
    ItemRejectionReasonMaster::factory()->create(['name' => 'BRAKEEE PROBLEM', 'code' => 'BRK']);

    Livewire::test(Index::class)->set('search', 'ENGINEEE')
        ->assertSee('ENGINEEE NOISE')
        ->assertDontSee('BRAKEEE PROBLEM');

    Livewire::test(Index::class)->set('search', 'brk') // case-insensitive via whereLike
        ->assertSee('BRAKEEE PROBLEM')
        ->assertDontSee('ENGINEEE NOISE');
});

it('filters by active status', function () {
    ItemRejectionReasonMaster::factory()->create(['name' => 'ENGINE ENABLED']);
    ItemRejectionReasonMaster::factory()->inactive()->create(['name' => 'BRAKE DISABLED']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('ENGINE ENABLED')
        ->assertDontSee('BRAKE DISABLED');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('BRAKE DISABLED')
        ->assertDontSee('ENGINE ENABLED');
});

it('sorts by name alphabetically by default', function () {
    ItemRejectionReasonMaster::factory()->create(['name' => 'ZEBRA TYPE']);
    ItemRejectionReasonMaster::factory()->create(['name' => 'ALPHA TYPE']);
    ItemRejectionReasonMaster::factory()->create(['name' => 'MIDDLE TYPE']);

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
        ->assertDispatched('item-rejection-reason-master:saved');

    $record = ItemRejectionReasonMaster::firstOrFail();
    expect($record->name)->toBe('ENGINE NOISE')
        ->and($record->code)->toBe('ENG')
        ->and($record->is_active)->toBeTrue();
});

it('updates an existing type', function () {
    $record = ItemRejectionReasonMaster::factory()->create(['name' => 'OLD NAME']);

    Livewire::test(Form::class)
        ->dispatch('item-rejection-reason-master:edit', id: $record->id)
        ->set('name', 'updated name')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED NAME');
});

it('deletes a type from the index', function () {
    $record = ItemRejectionReasonMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(ItemRejectionReasonMaster::find($record->id))->toBeNull();
});

it('validates name is required and unique', function () {
    ItemRejectionReasonMaster::factory()->create(['name' => 'EXISTING']);

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
    $record = ItemRejectionReasonMaster::factory()->create(['name' => 'ENGINE NOISE']);

    Livewire::test(Form::class)
        ->dispatch('item-rejection-reason-master:edit', id: $record->id)
        ->set('name', 'ENGINE NOISE') // same name as the record being edited
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('item-rejection-reason-master.index'))->assertRedirect(route('login'));
});
