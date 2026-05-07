<?php

use App\Modules\BankMaster\Livewire\Form;
use App\Modules\BankMaster\Livewire\Index;
use App\Modules\BankMaster\Models\BankMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    BankMaster::factory()->count(3)->create();

    $this->get(route('bank-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name or code', function () {
    BankMaster::factory()->create(['name' => 'HDFC BANK LTD', 'code' => 'HDFC']);
    BankMaster::factory()->create(['name' => 'ICICI BANK LTD', 'code' => 'ICICI']);

    Livewire::test(Index::class)->set('search', 'HDFC')
        ->assertSee('HDFC BANK LTD')
        ->assertDontSee('ICICI BANK LTD');

    Livewire::test(Index::class)->set('search', 'icici')
        ->assertSee('ICICI BANK LTD')
        ->assertDontSee('HDFC BANK LTD');
});

it('filters by active status', function () {
    BankMaster::factory()->create(['name' => 'BANK ENABLED']);
    BankMaster::factory()->inactive()->create(['name' => 'BANK DISABLED']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('BANK ENABLED')
        ->assertDontSee('BANK DISABLED');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('BANK DISABLED')
        ->assertDontSee('BANK ENABLED');
});

it('creates a bank with code (capital typing)', function () {
    Livewire::test(Form::class)
        ->set('name', 'hdfc bank')
        ->set('code', 'hdfc')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('bank-master:saved');

    $record = BankMaster::firstOrFail();
    expect($record->name)->toBe('HDFC BANK')
        ->and($record->code)->toBe('HDFC')
        ->and($record->is_active)->toBeTrue();
});

it('updates an existing bank', function () {
    $record = BankMaster::factory()->create(['name' => 'OLD BANK']);

    Livewire::test(Form::class)
        ->dispatch('bank-master:edit', id: $record->id)
        ->set('name', 'updated bank')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED BANK');
});

it('deletes a bank from the index', function () {
    $record = BankMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(BankMaster::find($record->id))->toBeNull();
});

it('validates name is required and unique', function () {
    BankMaster::factory()->create(['name' => 'EXISTING BANK']);

    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);

    Livewire::test(Form::class)
        ->set('name', 'EXISTING BANK')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('allows updating a bank without triggering self-uniqueness conflict', function () {
    $record = BankMaster::factory()->create(['name' => 'HDFC BANK']);

    Livewire::test(Form::class)
        ->dispatch('bank-master:edit', id: $record->id)
        ->set('name', 'HDFC BANK')
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('bank-master.index'))->assertRedirect(route('login'));
});
