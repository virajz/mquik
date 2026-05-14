<?php

use App\Models\User;
use App\Modules\SmartSalary\Livewire\Form;
use App\Modules\SmartSalary\Livewire\Index;
use App\Modules\SmartSalary\Models\SmartSalary;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    SmartSalary::factory()->count(3)->create();

    $this->get(route('smart-salary.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('registers a KPI definition', function () {
    Livewire::test(Form::class)
        ->dispatch('smart-salary:edit', id: null)
        ->set('key', 'ATTENDANCE_PCT')
        ->set('name', 'attendance %')
        ->set('category', 'Attendance')
        ->set('direction', SmartSalary::DIRECTION_HIGHER)
        ->set('unit', '%')
        ->set('weight', 15.5)
        ->set('formula', '(days_present / total_working_days) * 100')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('smart-salary:saved');

    $row = SmartSalary::first();
    expect($row->key)->toBe('ATTENDANCE_PCT')
        ->and($row->name)->toBe('ATTENDANCE %')  // capital typing on name
        ->and($row->category)->toBe('Attendance')
        ->and((float) $row->weight)->toBe(15.5)
        ->and($row->is_active)->toBeTrue();
});

it('rejects a key that is not UPPER_SNAKE_CASE', function () {
    Livewire::test(Form::class)
        ->dispatch('smart-salary:edit', id: null)
        ->set('key', 'attendance pct')   // lowercase + space
        ->set('name', 'X')
        ->set('weight', 5)
        ->call('save')
        ->assertHasErrors(['key']);
});

it('enforces unique key', function () {
    SmartSalary::factory()->create(['key' => 'BILLING_RATIO']);

    Livewire::test(Form::class)
        ->dispatch('smart-salary:edit', id: null)
        ->set('key', 'BILLING_RATIO')
        ->set('name', 'whatever')
        ->set('weight', 5)
        ->call('save')
        ->assertHasErrors(['key']);
});

it('filters by category and active flag', function () {
    SmartSalary::factory()->create(['category' => 'Attendance', 'is_active' => true]);
    SmartSalary::factory()->create(['category' => 'Sales', 'is_active' => true]);
    SmartSalary::factory()->create(['category' => 'Sales', 'is_active' => false]);

    Livewire::test(Index::class)
        ->set('categoryFilter', 'Sales')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 2)
        ->set('activeFilter', 'no')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1);
});

it('deletes a KPI from the index', function () {
    $row = SmartSalary::factory()->create();

    Livewire::test(Index::class)->call('delete', $row->id);

    expect(SmartSalary::find($row->id))->toBeNull();
});

it('blocks Form::save for a user without create permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('smart_salary.view');
    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('openCreate')
        ->assertStatus(403);
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('smart-salary.index'))->assertRedirect(route('login'));
});
