<?php

use App\Models\User;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\Payroll\Livewire\Form;
use App\Modules\Payroll\Livewire\Index;
use App\Modules\Payroll\Models\Payroll;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    Payroll::factory()->count(3)->create();

    $this->get(route('payroll.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('creates a payroll entry with computed gross + net', function () {
    $employee = EmployeeMaster::factory()->create();

    Livewire::test(Form::class)
        ->dispatch('payroll:edit', id: null)
        ->set('employee_id', $employee->id)
        ->set('period_year', 2026)
        ->set('period_month', 5)
        ->set('basic_amount', 30000)
        ->set('hra_amount', 12000)
        ->set('da_amount', 3000)
        ->set('allowances_amount', 2000)
        ->set('deductions_amount', 4500)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('payroll:saved');

    $row = Payroll::first();
    expect((float) $row->gross_amount)->toBe(47000.0)
        ->and((float) $row->net_amount)->toBe(42500.0)
        ->and($row->period_month)->toBe(5)
        ->and($row->period_year)->toBe(2026);
});

it('enforces unique (employee, year, month)', function () {
    $employee = EmployeeMaster::factory()->create();
    Payroll::factory()->create(['employee_id' => $employee->id, 'period_year' => 2026, 'period_month' => 5]);

    Livewire::test(Form::class)
        ->dispatch('payroll:edit', id: null)
        ->set('employee_id', $employee->id)
        ->set('period_year', 2026)
        ->set('period_month', 5)
        ->set('basic_amount', 30000)
        ->call('save')
        ->assertHasErrors(['employee_id']);
});

it('allows same employee in a different month', function () {
    $employee = EmployeeMaster::factory()->create();
    Payroll::factory()->create(['employee_id' => $employee->id, 'period_year' => 2026, 'period_month' => 5]);

    Livewire::test(Form::class)
        ->dispatch('payroll:edit', id: null)
        ->set('employee_id', $employee->id)
        ->set('period_year', 2026)
        ->set('period_month', 6)
        ->set('basic_amount', 30000)
        ->call('save')
        ->assertHasNoErrors();

    expect(Payroll::where('employee_id', $employee->id)->count())->toBe(2);
});

it('filters by status, year, month, and employee', function () {
    $a = EmployeeMaster::factory()->create();
    Payroll::factory()->create(['employee_id' => $a->id, 'period_year' => 2026, 'period_month' => 1]);
    Payroll::factory()->paid()->create(['period_year' => 2026, 'period_month' => 2]);
    Payroll::factory()->create(['period_year' => 2025, 'period_month' => 12]);

    Livewire::test(Index::class)
        ->set('statusFilter', Payroll::STATUS_PAID)
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1)
        ->set('statusFilter', 'all')
        ->set('yearFilter', '2026')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 2)
        ->set('monthFilter', '1')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1)
        ->set('monthFilter', 'all')
        ->set('yearFilter', '')
        ->set('employeeFilter', (string) $a->id)
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1);
});

it('deletes a payroll entry from the index', function () {
    $row = Payroll::factory()->create();

    Livewire::test(Index::class)->call('delete', $row->id);

    expect(Payroll::find($row->id))->toBeNull();
});

it('blocks Form::save for a user without create permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('payroll.view');
    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('openCreate')
        ->assertStatus(403);
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('payroll.index'))->assertRedirect(route('login'));
});
