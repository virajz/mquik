<?php

use App\Modules\LoanTypeMaster\Livewire\Form;
use App\Modules\LoanTypeMaster\Livewire\Index;
use App\Modules\LoanTypeMaster\Models\LoanTypeMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    LoanTypeMaster::factory()->count(3)->create();

    $this->get(route('loan-type-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('loan-type-master.index'))->assertRedirect(route('login'));
});

it('searches by name or code (case-insensitive)', function () {
    LoanTypeMaster::factory()->create(['name' => 'VEHICLE LOAN', 'code' => 'VEH']);
    LoanTypeMaster::factory()->create(['name' => 'HOUSING LOAN', 'code' => 'HSG']);

    Livewire::test(Index::class)->set('search', 'vehicle')
        ->assertSee('VEHICLE LOAN')->assertDontSee('HOUSING LOAN');
    Livewire::test(Index::class)->set('search', 'hsg')
        ->assertSee('HOUSING LOAN')->assertDontSee('VEHICLE LOAN');
});

it('creates a loan type, uppercasing name', function () {
    Livewire::test(Form::class)
        ->set('name', 'advance salary')->set('code', 'adv')
        ->call('save')->assertHasNoErrors()
        ->assertDispatched('loan-type-master:saved');

    expect(LoanTypeMaster::firstOrFail()->name)->toBe('ADVANCE SALARY');
});

it('validates name is required and unique', function () {
    LoanTypeMaster::factory()->create(['name' => 'EXISTING']);
    Livewire::test(Form::class)->set('name', '')->call('save')->assertHasErrors(['name' => 'required']);
    Livewire::test(Form::class)->set('name', 'EXISTING')->call('save')->assertHasErrors(['name' => 'unique']);
});

it('deletes a loan type from the index', function () {
    $record = LoanTypeMaster::factory()->create();
    Livewire::test(Index::class)->call('delete', $record->id);
    expect(LoanTypeMaster::find($record->id))->toBeNull();
});
