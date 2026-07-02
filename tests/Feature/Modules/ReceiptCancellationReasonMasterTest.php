<?php

use App\Modules\ReceiptCancellationReasonMaster\Livewire\Form;
use App\Modules\ReceiptCancellationReasonMaster\Livewire\Index;
use App\Modules\ReceiptCancellationReasonMaster\Models\ReceiptCancellationReasonMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    ReceiptCancellationReasonMaster::factory()->count(3)->create();

    $this->get(route('receipt-cancellation-reason-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('receipt-cancellation-reason-master.index'))->assertRedirect(route('login'));
});

it('searches by name or code (case-insensitive)', function () {
    ReceiptCancellationReasonMaster::factory()->create(['name' => 'VOID REQUEST', 'code' => 'VRQ']);
    ReceiptCancellationReasonMaster::factory()->create(['name' => 'KEYED TWICE', 'code' => 'KTW']);

    Livewire::test(Index::class)->set('search', 'void')
        ->assertSee('VOID REQUEST')
        ->assertDontSee('KEYED TWICE');

    Livewire::test(Index::class)->set('search', 'ktw')
        ->assertSee('KEYED TWICE')
        ->assertDontSee('VOID REQUEST');
});

it('creates a reason, uppercasing name', function () {
    Livewire::test(Form::class)
        ->set('name', 'wrong amount')
        ->set('code', 'wamt')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('receipt-cancellation-reason-master:saved');

    expect(ReceiptCancellationReasonMaster::firstOrFail()->name)->toBe('WRONG AMOUNT');
});

it('validates name is required and unique', function () {
    ReceiptCancellationReasonMaster::factory()->create(['name' => 'EXISTING']);

    Livewire::test(Form::class)->set('name', '')->call('save')->assertHasErrors(['name' => 'required']);
    Livewire::test(Form::class)->set('name', 'EXISTING')->call('save')->assertHasErrors(['name' => 'unique']);
});

it('deletes a reason from the index', function () {
    $record = ReceiptCancellationReasonMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(ReceiptCancellationReasonMaster::find($record->id))->toBeNull();
});
