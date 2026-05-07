<?php

use App\Modules\DepartmentMaster\Models\DepartmentMaster;
use App\Modules\DesignationMaster\Models\DesignationMaster;
use App\Modules\EmployeeMaster\Livewire\Form;
use App\Modules\EmployeeMaster\Livewire\Index;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
    $this->serviceDept = DepartmentMaster::firstOrCreate(['name' => 'SERVICE'], ['is_active' => true]);
    $this->mechAdvisor = DesignationMaster::firstOrCreate(['name' => 'MECHANICAL ADVISOR'], ['is_active' => true]);
    $this->technicianDesig = DesignationMaster::firstOrCreate(['name' => 'TECHNICIAN'], ['is_active' => true]);
});

it('renders the index page', function () {
    EmployeeMaster::factory()->count(3)->create();
    $this->get(route('employee-master.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('creates an employee with full details', function () {
    Livewire::test(Form::class)
        ->set('employee_code', 'EMP-00001')
        ->set('name', 'ravi sharma')
        ->set('phone', '9876543210')
        ->set('email', 'ravi@example.com')
        ->set('designation_id', $this->mechAdvisor->id)
        ->set('department_id', $this->serviceDept->id)
        ->set('joining_date', '2024-01-15')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('employee-master:saved');

    $r = EmployeeMaster::firstOrFail();
    expect($r->name)->toBe('RAVI SHARMA')
        ->and($r->employee_code)->toBe('EMP-00001')
        ->and($r->email)->toBe('ravi@example.com')
        ->and($r->designation_id)->toBe($this->mechAdvisor->id)
        ->and($r->department_id)->toBe($this->serviceDept->id);
});

it('requires employee_code, name, phone, designation, department, joining_date', function () {
    Livewire::test(Form::class)
        ->call('save')
        ->assertHasErrors(['employee_code', 'name', 'phone', 'designation_id', 'department_id', 'joining_date']);
});

it('rejects unknown designation or department FK', function () {
    Livewire::test(Form::class)
        ->set('employee_code', 'EMP-X')
        ->set('name', 'TEST')
        ->set('phone', '9999999999')
        ->set('designation_id', 99999)
        ->set('department_id', 99999)
        ->set('joining_date', '2024-01-01')
        ->call('save')
        ->assertHasErrors(['designation_id', 'department_id']);
});

it('rejects exit_date before joining_date', function () {
    Livewire::test(Form::class)
        ->set('employee_code', 'EMP-X')
        ->set('name', 'TEST')
        ->set('phone', '9999999999')
        ->set('designation_id', $this->technicianDesig->id)
        ->set('department_id', $this->serviceDept->id)
        ->set('joining_date', '2024-06-01')
        ->set('exit_date', '2024-01-01')
        ->call('save')
        ->assertHasErrors(['exit_date']);
});

it('blocks duplicate employee_code', function () {
    EmployeeMaster::factory()->create(['employee_code' => 'EMP-DUPE']);

    Livewire::test(Form::class)
        ->set('employee_code', 'EMP-DUPE')
        ->set('name', 'TEST')
        ->set('phone', '9999999999')
        ->set('designation_id', $this->technicianDesig->id)
        ->set('department_id', $this->serviceDept->id)
        ->set('joining_date', '2024-01-01')
        ->call('save')
        ->assertHasErrors(['employee_code']);
});

it('filters by department and designation', function () {
    EmployeeMaster::factory()->advisor()->create(['name' => 'ALPHA ADVISOR']);
    EmployeeMaster::factory()->technician()->create(['name' => 'BETA TECH']);

    $service = DepartmentMaster::firstOrCreate(['name' => 'SERVICE'], ['is_active' => true]);
    $tech = DesignationMaster::firstOrCreate(['name' => 'TECHNICIAN'], ['is_active' => true]);

    Livewire::test(Index::class)->set('deptFilter', (string) $service->id)
        ->assertSee('ALPHA ADVISOR')
        ->assertSee('BETA TECH');

    Livewire::test(Index::class)->set('designationFilter', (string) $tech->id)
        ->assertSee('BETA TECH')
        ->assertDontSee('ALPHA ADVISOR');
});

it('updates an employee', function () {
    $r = EmployeeMaster::factory()->create(['name' => 'OLD']);
    Livewire::test(Form::class)
        ->dispatch('employee-master:edit', id: $r->id)
        ->set('name', 'updated')
        ->call('save')
        ->assertHasNoErrors();
    expect($r->fresh()->name)->toBe('UPDATED');
});

it('deletes an employee', function () {
    $r = EmployeeMaster::factory()->create();
    Livewire::test(Index::class)->call('delete', $r->id);
    expect(EmployeeMaster::find($r->id))->toBeNull();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('employee-master.index'))->assertRedirect(route('login'));
});
