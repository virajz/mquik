<?php

use App\Modules\ReceiptDifferenceReasonMaster\Livewire\Form;
use App\Modules\ReceiptDifferenceReasonMaster\Livewire\Index;
use App\Modules\ReceiptDifferenceReasonMaster\Models\ReceiptDifferenceReasonMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    ReceiptDifferenceReasonMaster::factory()->count(3)->create();

    $this->get(route('receipt-difference-reason-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('receipt-difference-reason-master.index'))->assertRedirect(route('login'));
});

it('searches by name or code (case-insensitive)', function () {
    ReceiptDifferenceReasonMaster::factory()->create(['name' => 'ROUNDING GAP', 'code' => 'RGP']);
    ReceiptDifferenceReasonMaster::factory()->create(['name' => 'FOREX SPREAD', 'code' => 'FXS']);

    Livewire::test(Index::class)->set('search', 'rounding')
        ->assertSee('ROUNDING GAP')
        ->assertDontSee('FOREX SPREAD');

    Livewire::test(Index::class)->set('search', 'fxs')
        ->assertSee('FOREX SPREAD')
        ->assertDontSee('ROUNDING GAP');
});

it('creates a reason, uppercasing name', function () {
    Livewire::test(Form::class)
        ->set('name', 'short payment')
        ->set('code', 'short')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('receipt-difference-reason-master:saved');

    expect(ReceiptDifferenceReasonMaster::firstOrFail()->name)->toBe('SHORT PAYMENT');
});

it('validates name is required and unique', function () {
    ReceiptDifferenceReasonMaster::factory()->create(['name' => 'EXISTING']);

    Livewire::test(Form::class)->set('name', '')->call('save')->assertHasErrors(['name' => 'required']);
    Livewire::test(Form::class)->set('name', 'EXISTING')->call('save')->assertHasErrors(['name' => 'unique']);
});

it('deletes a reason from the index', function () {
    $record = ReceiptDifferenceReasonMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(ReceiptDifferenceReasonMaster::find($record->id))->toBeNull();
});
