<?php

use App\Modules\PaymentCancellationReasonMaster\Livewire\Form;
use App\Modules\PaymentCancellationReasonMaster\Livewire\Index;
use App\Modules\PaymentCancellationReasonMaster\Models\PaymentCancellationReasonMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    PaymentCancellationReasonMaster::factory()->count(3)->create();

    $this->get(route('payment-cancellation-reason-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('payment-cancellation-reason-master.index'))->assertRedirect(route('login'));
});

it('searches by name or code (case-insensitive)', function () {
    PaymentCancellationReasonMaster::factory()->create(['name' => 'VENDOR CLOSED', 'code' => 'VCL']);
    PaymentCancellationReasonMaster::factory()->create(['name' => 'STOP PAYMENT', 'code' => 'STP']);

    Livewire::test(Index::class)->set('search', 'vendor')
        ->assertSee('VENDOR CLOSED')
        ->assertDontSee('STOP PAYMENT');

    Livewire::test(Index::class)->set('search', 'stp')
        ->assertSee('STOP PAYMENT')
        ->assertDontSee('VENDOR CLOSED');
});

it('creates a reason, uppercasing name', function () {
    Livewire::test(Form::class)
        ->set('name', 'wrong vendor')
        ->set('code', 'wvnd')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('payment-cancellation-reason-master:saved');

    expect(PaymentCancellationReasonMaster::firstOrFail()->name)->toBe('WRONG VENDOR');
});

it('validates name is required and unique', function () {
    PaymentCancellationReasonMaster::factory()->create(['name' => 'EXISTING']);

    Livewire::test(Form::class)->set('name', '')->call('save')->assertHasErrors(['name' => 'required']);
    Livewire::test(Form::class)->set('name', 'EXISTING')->call('save')->assertHasErrors(['name' => 'unique']);
});

it('deletes a reason from the index', function () {
    $record = PaymentCancellationReasonMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(PaymentCancellationReasonMaster::find($record->id))->toBeNull();
});
