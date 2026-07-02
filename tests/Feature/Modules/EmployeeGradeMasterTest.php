<?php

use App\Modules\EmployeeGradeMaster\Livewire\Form;
use App\Modules\EmployeeGradeMaster\Livewire\Index;
use App\Modules\EmployeeGradeMaster\Models\EmployeeGradeMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    EmployeeGradeMaster::factory()->count(3)->create();

    $this->get(route('employee-grade-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('employee-grade-master.index'))->assertRedirect(route('login'));
});

it('searches by name or code (case-insensitive)', function () {
    EmployeeGradeMaster::factory()->create(['name' => 'PLATINUM TIER', 'code' => 'PLT']);
    EmployeeGradeMaster::factory()->create(['name' => 'BRONZE TIER', 'code' => 'BRZ']);

    Livewire::test(Index::class)->set('search', 'platinum')
        ->assertSee('PLATINUM TIER')->assertDontSee('BRONZE TIER');
    Livewire::test(Index::class)->set('search', 'brz')
        ->assertSee('BRONZE TIER')->assertDontSee('PLATINUM TIER');
});

it('creates a grade, uppercasing name', function () {
    Livewire::test(Form::class)
        ->set('name', 'grade a')->set('code', 'a')
        ->call('save')->assertHasNoErrors()
        ->assertDispatched('employee-grade-master:saved');

    expect(EmployeeGradeMaster::firstOrFail()->name)->toBe('GRADE A');
});

it('validates name is required and unique', function () {
    EmployeeGradeMaster::factory()->create(['name' => 'EXISTING']);
    Livewire::test(Form::class)->set('name', '')->call('save')->assertHasErrors(['name' => 'required']);
    Livewire::test(Form::class)->set('name', 'EXISTING')->call('save')->assertHasErrors(['name' => 'unique']);
});

it('deletes a grade from the index', function () {
    $record = EmployeeGradeMaster::factory()->create();
    Livewire::test(Index::class)->call('delete', $record->id);
    expect(EmployeeGradeMaster::find($record->id))->toBeNull();
});
