<?php

use App\Modules\SalesReturnReasonMaster\Livewire\Form;
use App\Modules\SalesReturnReasonMaster\Livewire\Index;
use App\Modules\SalesReturnReasonMaster\Models\SalesReturnReasonMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    SalesReturnReasonMaster::factory()->count(3)->create();

    $this->get(route('sales-return-reason-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('sales-return-reason-master.index'))->assertRedirect(route('login'));
});

it('searches by name or code (case-insensitive)', function () {
    // Names deliberately don't collide with the form placeholder / empty-state hints.
    SalesReturnReasonMaster::factory()->create(['name' => 'FAULTY WELD', 'code' => 'FWD']);
    SalesReturnReasonMaster::factory()->create(['name' => 'SHIPPING DENT', 'code' => 'SDT']);

    Livewire::test(Index::class)->set('search', 'faulty')
        ->assertSee('FAULTY WELD')
        ->assertDontSee('SHIPPING DENT');

    Livewire::test(Index::class)->set('search', 'sdt')
        ->assertSee('SHIPPING DENT')
        ->assertDontSee('FAULTY WELD');
});

it('creates a reason with code, uppercasing name', function () {
    Livewire::test(Form::class)
        ->set('name', 'manufacturing defect')
        ->set('code', 'mfg')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('sales-return-reason-master:saved');

    $record = SalesReturnReasonMaster::firstOrFail();
    expect($record->name)->toBe('MANUFACTURING DEFECT')
        ->and($record->code)->toBe('MFG')
        ->and($record->is_active)->toBeTrue();
});

it('validates name is required and unique', function () {
    SalesReturnReasonMaster::factory()->create(['name' => 'EXISTING']);

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
    $record = SalesReturnReasonMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(SalesReturnReasonMaster::find($record->id))->toBeNull();
});
