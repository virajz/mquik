<?php

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\SalaryComponentMaster\Models\SalaryComponentMaster;
use App\Modules\SalaryStructure\Livewire\Edit;
use App\Modules\SalaryStructure\Livewire\Index;
use App\Modules\SalaryStructure\Models\SalaryStructure;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    SalaryStructure::factory()->count(2)->create();

    $this->get(route('salary-structure.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('salary-structure.index'))->assertRedirect(route('login'));
});

it('creates a structure and computes gross, deductions and net', function () {
    $employee = EmployeeMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('employee_id', $employee->id)
        ->set('effective_from', '2026-06-01')
        ->set('basic_salary', 20000)
        ->set('lines', [
            ['id' => null, 'salary_component_id' => null, 'name' => 'HRA', 'component_type' => 'earning', 'calc_method' => 'percent_of_basic', 'value' => 40, 'sequence_no' => 1],
            ['id' => null, 'salary_component_id' => null, 'name' => 'CONVEYANCE', 'component_type' => 'earning', 'calc_method' => 'fixed', 'value' => 1600, 'sequence_no' => 2],
            ['id' => null, 'salary_component_id' => null, 'name' => 'PF', 'component_type' => 'deduction', 'calc_method' => 'percent_of_basic', 'value' => 12, 'sequence_no' => 3],
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $s = SalaryStructure::firstOrFail();
    // gross = 20000 + (8000 HRA + 1600 conveyance) = 29600; deductions = 2400 PF; net = 27200
    expect((float) $s->gross_earnings)->toBe(29600.0)
        ->and((float) $s->total_deductions)->toBe(2400.0)
        ->and((float) $s->net_salary)->toBe(27200.0)
        ->and($s->lines()->count())->toBe(3);
});

it('snapshots type, calc method and default value when adding a component', function () {
    $employee = EmployeeMaster::factory()->create();
    $hra = SalaryComponentMaster::factory()->create([
        'name' => 'HRA', 'component_type' => 'earning', 'calc_method' => 'percent_of_basic', 'default_value' => 40,
    ]);

    $component = Livewire::test(Edit::class)
        ->set('employee_id', $employee->id)
        ->call('addComponent', $hra->id);

    $lines = $component->get('lines');
    expect($lines)->toHaveCount(1)
        ->and($lines[0]['component_type'])->toBe('earning')
        ->and($lines[0]['calc_method'])->toBe('percent_of_basic')
        ->and((float) $lines[0]['value'])->toBe(40.0);
});

it('adds all active components except the basic one', function () {
    SalaryComponentMaster::factory()->create(['name' => 'BASIC SALARY', 'code' => 'BASIC', 'component_type' => 'earning']);
    SalaryComponentMaster::factory()->create(['name' => 'HRA', 'code' => 'HRA', 'component_type' => 'earning']);
    SalaryComponentMaster::factory()->create(['name' => 'PF', 'code' => 'PF', 'component_type' => 'deduction']);

    $component = Livewire::test(Edit::class)->call('addAllComponents');

    // BASIC excluded → 2 lines
    expect($component->get('lines'))->toHaveCount(2);
});

it('does not add the same component twice', function () {
    $hra = SalaryComponentMaster::factory()->create(['name' => 'HRA', 'code' => 'HRA', 'component_type' => 'earning']);

    $component = Livewire::test(Edit::class)
        ->call('addComponent', $hra->id)
        ->call('addComponent', $hra->id);

    expect($component->get('lines'))->toHaveCount(1);
});

it('prefills the employee from a from-employee handoff', function () {
    $employee = EmployeeMaster::factory()->create();

    $component = Livewire::withQueryParams(['from-employee' => $employee->id])->test(Edit::class);

    expect($component->get('employee_id'))->toBe($employee->id);
});

it('deletes a structure from the index', function () {
    $s = SalaryStructure::factory()->create();

    Livewire::test(Index::class)->call('delete', $s->id);

    expect(SalaryStructure::find($s->id))->toBeNull();
});
