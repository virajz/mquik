<?php

use App\Modules\ChequeBounceReasonMaster\Livewire\Form;
use App\Modules\ChequeBounceReasonMaster\Livewire\Index;
use App\Modules\ChequeBounceReasonMaster\Models\ChequeBounceReasonMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    ChequeBounceReasonMaster::factory()->count(3)->create();

    $this->get(route('cheque-bounce-reason-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('cheque-bounce-reason-master.index'))->assertRedirect(route('login'));
});

it('searches by name or code (case-insensitive)', function () {
    ChequeBounceReasonMaster::factory()->create(['name' => 'PENALTY HOLD', 'code' => 'PHD']);
    ChequeBounceReasonMaster::factory()->create(['name' => 'FROZEN LIEN', 'code' => 'FLN']);

    Livewire::test(Index::class)->set('search', 'penalty')
        ->assertSee('PENALTY HOLD')
        ->assertDontSee('FROZEN LIEN');

    Livewire::test(Index::class)->set('search', 'fln')
        ->assertSee('FROZEN LIEN')
        ->assertDontSee('PENALTY HOLD');
});

it('creates a reason, uppercasing name', function () {
    Livewire::test(Form::class)
        ->set('name', 'insufficient funds')
        ->set('code', 'insf')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('cheque-bounce-reason-master:saved');

    expect(ChequeBounceReasonMaster::firstOrFail()->name)->toBe('INSUFFICIENT FUNDS');
});

it('validates name is required and unique', function () {
    ChequeBounceReasonMaster::factory()->create(['name' => 'EXISTING']);

    Livewire::test(Form::class)->set('name', '')->call('save')->assertHasErrors(['name' => 'required']);
    Livewire::test(Form::class)->set('name', 'EXISTING')->call('save')->assertHasErrors(['name' => 'unique']);
});

it('deletes a reason from the index', function () {
    $record = ChequeBounceReasonMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(ChequeBounceReasonMaster::find($record->id))->toBeNull();
});
