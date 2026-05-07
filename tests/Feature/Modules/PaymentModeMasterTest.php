<?php

use App\Modules\PaymentModeMaster\Livewire\Form;
use App\Modules\PaymentModeMaster\Livewire\Index;
use App\Modules\PaymentModeMaster\Models\PaymentModeMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    PaymentModeMaster::factory()->count(3)->create();

    $this->get(route('payment-mode-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name or code', function () {
    PaymentModeMaster::factory()->create(['name' => 'WALLET PAY', 'code' => 'WLT']);
    PaymentModeMaster::factory()->create(['name' => 'NEFT TRANSFER', 'code' => 'NFT']);

    Livewire::test(Index::class)->set('search', 'WALLET')
        ->assertSee('WALLET PAY')
        ->assertDontSee('NEFT TRANSFER');

    Livewire::test(Index::class)->set('search', 'nft') // case-insensitive via whereLike
        ->assertSee('NEFT TRANSFER')
        ->assertDontSee('WALLET PAY');
});

it('filters by active status', function () {
    PaymentModeMaster::factory()->create(['name' => 'MODE ENABLED']);
    PaymentModeMaster::factory()->inactive()->create(['name' => 'MODE DISABLED']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('MODE ENABLED')
        ->assertDontSee('MODE DISABLED');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('MODE DISABLED')
        ->assertDontSee('MODE ENABLED');
});

it('creates a payment mode with capital typing', function () {
    Livewire::test(Form::class)
        ->set('name', 'wallet pay')
        ->set('code', 'wlt')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('payment-mode-master:saved');

    $record = PaymentModeMaster::firstOrFail();
    expect($record->name)->toBe('WALLET PAY')
        ->and($record->code)->toBe('WLT')
        ->and($record->is_active)->toBeTrue();
});

it('updates an existing payment mode', function () {
    $record = PaymentModeMaster::factory()->create(['name' => 'OLD NAME']);

    Livewire::test(Form::class)
        ->dispatch('payment-mode-master:edit', id: $record->id)
        ->set('name', 'updated name')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED NAME');
});

it('deletes a payment mode from the index', function () {
    $record = PaymentModeMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(PaymentModeMaster::find($record->id))->toBeNull();
});

it('validates name is required and unique', function () {
    PaymentModeMaster::factory()->create(['name' => 'EXISTING']);

    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);

    Livewire::test(Form::class)
        ->set('name', 'EXISTING')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('allows updating a payment mode without triggering self-uniqueness conflict', function () {
    $record = PaymentModeMaster::factory()->create(['name' => 'WALLET PAY']);

    Livewire::test(Form::class)
        ->dispatch('payment-mode-master:edit', id: $record->id)
        ->set('name', 'WALLET PAY') // same name as the record being edited
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('payment-mode-master.index'))->assertRedirect(route('login'));
});
