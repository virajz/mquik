<?php

use App\Modules\EmployeeCategoryMaster\Livewire\Form;
use App\Modules\EmployeeCategoryMaster\Livewire\Index;
use App\Modules\EmployeeCategoryMaster\Models\EmployeeCategoryMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    EmployeeCategoryMaster::factory()->count(3)->create();

    $this->get(route('employee-category-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('employee-category-master.index'))->assertRedirect(route('login'));
});

it('searches by name or code (case-insensitive)', function () {
    EmployeeCategoryMaster::factory()->create(['name' => 'SEASONAL', 'code' => 'SEAS']);
    EmployeeCategoryMaster::factory()->create(['name' => 'RETAINER', 'code' => 'RET']);

    Livewire::test(Index::class)->set('search', 'seasonal')
        ->assertSee('SEASONAL')->assertDontSee('RETAINER');
    Livewire::test(Index::class)->set('search', 'ret')
        ->assertSee('RETAINER')->assertDontSee('SEASONAL');
});

it('creates a category, uppercasing name', function () {
    Livewire::test(Form::class)
        ->set('name', 'permanent')->set('code', 'perm')
        ->call('save')->assertHasNoErrors()
        ->assertDispatched('employee-category-master:saved');

    expect(EmployeeCategoryMaster::firstOrFail()->name)->toBe('PERMANENT');
});

it('validates name is required and unique', function () {
    EmployeeCategoryMaster::factory()->create(['name' => 'EXISTING']);
    Livewire::test(Form::class)->set('name', '')->call('save')->assertHasErrors(['name' => 'required']);
    Livewire::test(Form::class)->set('name', 'EXISTING')->call('save')->assertHasErrors(['name' => 'unique']);
});

it('deletes a category from the index', function () {
    $record = EmployeeCategoryMaster::factory()->create();
    Livewire::test(Index::class)->call('delete', $record->id);
    expect(EmployeeCategoryMaster::find($record->id))->toBeNull();
});
