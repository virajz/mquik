<?php

use App\Models\User;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\LateMemo\Livewire\Form;
use App\Modules\LateMemo\Livewire\Index;
use App\Modules\LateMemo\Models\LateMemo;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    LateMemo::factory()->count(3)->create();

    $this->get(route('late-memo.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('issues a late memo via the form', function () {
    $employee = EmployeeMaster::factory()->create();

    Livewire::test(Form::class)
        ->dispatch('late-memo:edit', id: null)
        ->set('employee_id', $employee->id)
        ->set('memo_date', '2026-05-14')
        ->set('late_by_minutes', 45)
        ->set('reason', 'traffic on the highway')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('late-memo:saved');

    $row = LateMemo::first();
    expect($row->employee_id)->toBe($employee->id)
        ->and($row->late_by_minutes)->toBe(45)
        ->and($row->reason)->toBe('TRAFFIC ON THE HIGHWAY')
        ->and($row->status)->toBe(LateMemo::STATUS_ISSUED)
        ->and($row->issued_at)->not->toBeNull();
});

it('filters memos by employee and status', function () {
    $a = EmployeeMaster::factory()->create();
    LateMemo::factory()->create(['employee_id' => $a->id]);
    LateMemo::factory()->acknowledged()->create();
    LateMemo::factory()->waived()->create();

    Livewire::test(Index::class)
        ->set('employeeFilter', (string) $a->id)
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1)
        ->set('employeeFilter', 'all')
        ->set('statusFilter', LateMemo::STATUS_WAIVED)
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1);
});

it('validates late_by_minutes range', function () {
    $employee = EmployeeMaster::factory()->create();

    Livewire::test(Form::class)
        ->dispatch('late-memo:edit', id: null)
        ->set('employee_id', $employee->id)
        ->set('memo_date', '2026-05-14')
        ->set('late_by_minutes', 999)
        ->call('save')
        ->assertHasErrors(['late_by_minutes']);
});

it('deletes a memo from the index', function () {
    $row = LateMemo::factory()->create();

    Livewire::test(Index::class)->call('delete', $row->id);

    expect(LateMemo::find($row->id))->toBeNull();
});

it('blocks Form::save for a user without create permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('late_memo.view');
    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('openCreate')
        ->assertStatus(403);
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('late-memo.index'))->assertRedirect(route('login'));
});
