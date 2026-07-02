<?php

use App\Models\User;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\LeaveManagement\Livewire\Form;
use App\Modules\LeaveManagement\Livewire\Index;
use App\Modules\LeaveManagement\Models\LeaveManagement;
use App\Modules\LeaveTypeMaster\Models\LeaveTypeMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    LeaveManagement::factory()->count(3)->create();

    $this->get(route('leave-management.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('creates a leave request via the form', function () {
    $employee = EmployeeMaster::factory()->create();
    LeaveTypeMaster::factory()->create(['name' => 'SICK LEAVE', 'code' => 'SL']);

    Livewire::test(Form::class)
        ->dispatch('leave-management:edit', id: null)
        ->set('employee_id', $employee->id)
        ->set('leave_type', 'SL')
        ->set('from_date', '2026-06-01')
        ->set('to_date', '2026-06-03')
        ->set('days_count', 3.0)
        ->set('reason', 'flu')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('leave-management:saved');

    $row = LeaveManagement::first();
    expect($row->employee_id)->toBe($employee->id)
        ->and($row->leave_type)->toBe('SL')
        ->and($row->reason)->toBe('FLU')
        ->and($row->status)->toBe(LeaveManagement::STATUS_PENDING);
});

it('stamps approved_at when status moves to approved', function () {
    $row = LeaveManagement::factory()->create();

    Livewire::test(Form::class)
        ->dispatch('leave-management:edit', id: $row->id)
        ->set('status', LeaveManagement::STATUS_APPROVED)
        ->call('save')
        ->assertHasNoErrors();

    expect($row->fresh()->approved_at)->not->toBeNull()
        ->and($row->fresh()->status)->toBe(LeaveManagement::STATUS_APPROVED);
});

it('rejects to_date before from_date', function () {
    $employee = EmployeeMaster::factory()->create();

    Livewire::test(Form::class)
        ->dispatch('leave-management:edit', id: null)
        ->set('employee_id', $employee->id)
        ->set('leave_type', 'CL')
        ->set('from_date', '2026-06-10')
        ->set('to_date', '2026-06-05')
        ->set('days_count', 1)
        ->call('save')
        ->assertHasErrors(['to_date']);
});

it('filters by employee, status, and leave type', function () {
    $a = EmployeeMaster::factory()->create();
    LeaveManagement::factory()->create(['employee_id' => $a->id, 'leave_type' => 'CL', 'status' => LeaveManagement::STATUS_PENDING]);
    LeaveManagement::factory()->approved()->create(['leave_type' => 'SL']);
    LeaveManagement::factory()->create(['leave_type' => 'PL']);

    Livewire::test(Index::class)
        ->set('employeeFilter', (string) $a->id)
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1)
        ->set('employeeFilter', 'all')
        ->set('statusFilter', LeaveManagement::STATUS_APPROVED)
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1)
        ->set('statusFilter', 'all')
        ->set('typeFilter', 'PL')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1);
});

it('deletes a request from the index', function () {
    $row = LeaveManagement::factory()->create();

    Livewire::test(Index::class)->call('delete', $row->id);

    expect(LeaveManagement::find($row->id))->toBeNull();
});

it('blocks Form::save for a user without create permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('leave_management.view');
    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('openCreate')
        ->assertStatus(403);
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('leave-management.index'))->assertRedirect(route('login'));
});
