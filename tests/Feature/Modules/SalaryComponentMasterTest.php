<?php

use App\Modules\SalaryComponentMaster\Livewire\Form;
use App\Modules\SalaryComponentMaster\Livewire\Index;
use App\Modules\SalaryComponentMaster\Models\SalaryComponentMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    SalaryComponentMaster::factory()->count(3)->create();

    $this->get(route('salary-component-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('salary-component-master.index'))->assertRedirect(route('login'));
});

it('creates an earning component with a calc method', function () {
    Livewire::test(Form::class)
        ->set('name', 'hra')
        ->set('code', 'hra')
        ->set('component_type', 'earning')
        ->set('calc_method', 'percent_of_basic')
        ->set('default_value', 40)
        ->set('is_taxable', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('salary-component-master:saved');

    $c = SalaryComponentMaster::firstOrFail();
    expect($c->name)->toBe('HRA')
        ->and($c->component_type)->toBe('earning')
        ->and($c->calc_method)->toBe('percent_of_basic')
        ->and((float) $c->default_value)->toBe(40.0)
        ->and($c->is_taxable)->toBeTrue();
});

it('creates a deduction component', function () {
    Livewire::test(Form::class)
        ->set('name', 'pf')
        ->set('component_type', 'deduction')
        ->set('calc_method', 'percent_of_basic')
        ->set('default_value', 12)
        ->call('save')
        ->assertHasNoErrors();

    expect(SalaryComponentMaster::firstOrFail()->component_type)->toBe('deduction');
});

it('validates component_type against the allowed set', function () {
    Livewire::test(Form::class)
        ->set('name', 'bad')
        ->set('component_type', 'nonsense')
        ->call('save')
        ->assertHasErrors(['component_type']);
});

it('deletes a component from the index', function () {
    $record = SalaryComponentMaster::factory()->create();
    Livewire::test(Index::class)->call('delete', $record->id);
    expect(SalaryComponentMaster::find($record->id))->toBeNull();
});
