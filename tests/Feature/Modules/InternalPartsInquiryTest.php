<?php

use App\Models\User;
use App\Modules\InternalPartsInquiry\Livewire\Form;
use App\Modules\InternalPartsInquiry\Livewire\Index;
use App\Modules\InternalPartsInquiry\Models\InternalPartsInquiry;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders the index page', function () {
    InternalPartsInquiry::factory()->count(3)->create();

    $this->get(route('internal-parts-inquiry.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('filters records by search', function () {
    InternalPartsInquiry::factory()->create(['name' => 'ALPHA RECORD']);
    InternalPartsInquiry::factory()->create(['name' => 'BETA RECORD']);

    Livewire::test(Index::class)
        ->set('search', 'ALPHA')
        ->assertSee('ALPHA RECORD')
        ->assertDontSee('BETA RECORD');
});

it('creates a record via the form', function () {
    Livewire::test(Form::class)
        ->set('name', 'new vendor')
        ->set('description', 'first line')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('internal-parts-inquiry:saved');

    expect(InternalPartsInquiry::count())->toBe(1);
    expect(InternalPartsInquiry::first()->name)->toBe('NEW VENDOR'); // capital typing enforced
});

it('updates an existing record via the form', function () {
    $record = InternalPartsInquiry::factory()->create(['name' => 'OLD']);

    Livewire::test(Form::class)
        ->dispatch('internal-parts-inquiry:edit', id: $record->id)
        ->set('name', 'updated')
        ->call('save')
        ->assertDispatched('internal-parts-inquiry:saved');

    expect($record->fresh()->name)->toBe('UPDATED');
});

it('deletes a record from the index', function () {
    $record = InternalPartsInquiry::factory()->create();

    Livewire::test(Index::class)
        ->call('delete', $record->id);

    expect(InternalPartsInquiry::find($record->id))->toBeNull();
});

it('validates required name on save', function () {
    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('internal-parts-inquiry.index'))->assertRedirect(route('login'));
});
