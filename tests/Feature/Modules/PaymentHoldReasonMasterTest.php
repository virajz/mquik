<?php

use App\Modules\PaymentHoldReasonMaster\Livewire\Form;
use App\Modules\PaymentHoldReasonMaster\Livewire\Index;
use App\Modules\PaymentHoldReasonMaster\Models\PaymentHoldReasonMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    PaymentHoldReasonMaster::factory()->count(3)->create();

    $this->get(route('payment-hold-reason-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('payment-hold-reason-master.index'))->assertRedirect(route('login'));
});

it('searches by name or code (case-insensitive)', function () {
    PaymentHoldReasonMaster::factory()->create(['name' => 'LEGAL FREEZE', 'code' => 'LGL']);
    PaymentHoldReasonMaster::factory()->create(['name' => 'BUDGET BLOCK', 'code' => 'BDG']);

    Livewire::test(Index::class)->set('search', 'legal')
        ->assertSee('LEGAL FREEZE')
        ->assertDontSee('BUDGET BLOCK');

    Livewire::test(Index::class)->set('search', 'bdg')
        ->assertSee('BUDGET BLOCK')
        ->assertDontSee('LEGAL FREEZE');
});

it('creates a reason, uppercasing name', function () {
    Livewire::test(Form::class)
        ->set('name', 'invoice dispute')
        ->set('code', 'disp')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('payment-hold-reason-master:saved');

    expect(PaymentHoldReasonMaster::firstOrFail()->name)->toBe('INVOICE DISPUTE');
});

it('validates name is required and unique', function () {
    PaymentHoldReasonMaster::factory()->create(['name' => 'EXISTING']);

    Livewire::test(Form::class)->set('name', '')->call('save')->assertHasErrors(['name' => 'required']);
    Livewire::test(Form::class)->set('name', 'EXISTING')->call('save')->assertHasErrors(['name' => 'unique']);
});

it('deletes a reason from the index', function () {
    $record = PaymentHoldReasonMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(PaymentHoldReasonMaster::find($record->id))->toBeNull();
});
