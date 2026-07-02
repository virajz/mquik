<?php

use App\Modules\InvoiceCancellationReasonMaster\Livewire\Form;
use App\Modules\InvoiceCancellationReasonMaster\Livewire\Index;
use App\Modules\InvoiceCancellationReasonMaster\Models\InvoiceCancellationReasonMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    InvoiceCancellationReasonMaster::factory()->count(3)->create();

    $this->get(route('invoice-cancellation-reason-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('invoice-cancellation-reason-master.index'))->assertRedirect(route('login'));
});

it('searches by name or code (case-insensitive)', function () {
    // Names deliberately don't collide with the form placeholder / empty-state hints.
    InvoiceCancellationReasonMaster::factory()->create(['name' => 'REFUND MISMATCH', 'code' => 'RFD']);
    InvoiceCancellationReasonMaster::factory()->create(['name' => 'CLERICAL BLUNDER', 'code' => 'CLB']);

    Livewire::test(Index::class)->set('search', 'refund')
        ->assertSee('REFUND MISMATCH')
        ->assertDontSee('CLERICAL BLUNDER');

    Livewire::test(Index::class)->set('search', 'clb') // case-insensitive code match
        ->assertSee('CLERICAL BLUNDER')
        ->assertDontSee('REFUND MISMATCH');
});

it('creates a reason with code, uppercasing name', function () {
    Livewire::test(Form::class)
        ->set('name', 'wrong tax')
        ->set('code', 'tax')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('invoice-cancellation-reason-master:saved');

    $record = InvoiceCancellationReasonMaster::firstOrFail();
    expect($record->name)->toBe('WRONG TAX')
        ->and($record->code)->toBe('TAX')
        ->and($record->is_active)->toBeTrue();
});

it('validates name is required and unique', function () {
    InvoiceCancellationReasonMaster::factory()->create(['name' => 'EXISTING']);

    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);

    Livewire::test(Form::class)
        ->set('name', 'EXISTING')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('deletes a reason from the index', function () {
    $record = InvoiceCancellationReasonMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(InvoiceCancellationReasonMaster::find($record->id))->toBeNull();
});
