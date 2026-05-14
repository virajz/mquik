<?php

use App\Models\User;
use App\Modules\Attendance\Livewire\Form;
use App\Modules\Attendance\Livewire\Index;
use App\Modules\Attendance\Models\Attendance;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    Attendance::factory()->count(3)->create();

    $this->get(route('attendance.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('creates an attendance punch via the form', function () {
    $employee = EmployeeMaster::factory()->create();

    Livewire::test(Form::class)
        ->dispatch('attendance:edit', id: null)
        ->set('employee_id', $employee->id)
        ->set('punched_date', '2026-05-14')
        ->set('punched_time', '09:30')
        ->set('type', Attendance::TYPE_IN)
        ->set('notes', 'on time')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('attendance:saved');

    $row = Attendance::first();
    expect($row->employee_id)->toBe($employee->id)
        ->and($row->type)->toBe(Attendance::TYPE_IN)
        ->and($row->punched_at->format('Y-m-d H:i'))->toBe('2026-05-14 09:30')
        ->and($row->notes)->toBe('ON TIME');
});

it('uploads a selfie and saves it on the public disk', function () {
    Storage::fake('public');

    $employee = EmployeeMaster::factory()->create();

    Livewire::test(Form::class)
        ->dispatch('attendance:edit', id: null)
        ->set('employee_id', $employee->id)
        ->set('punched_date', '2026-05-14')
        ->set('punched_time', '09:30')
        ->set('type', Attendance::TYPE_IN)
        ->set('selfie', UploadedFile::fake()->image('me.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $row = Attendance::first();
    expect($row->selfie_path)->not->toBeNull()
        ->and($row->selfie_path)->toStartWith("attendance/{$employee->id}/selfies/");
    Storage::disk('public')->assertExists($row->selfie_path);
});

it('replaces a saved selfie on edit', function () {
    Storage::fake('public');

    $employee = EmployeeMaster::factory()->create();
    $row = Attendance::factory()->create(['employee_id' => $employee->id]);
    $oldPath = UploadedFile::fake()->image('old.jpg')->store("attendance/{$employee->id}/selfies", 'public');
    $row->forceFill(['selfie_path' => $oldPath])->save();

    Livewire::test(Form::class)
        ->dispatch('attendance:edit', id: $row->id)
        ->set('selfie', UploadedFile::fake()->image('new.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $fresh = $row->fresh();
    expect($fresh->selfie_path)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($fresh->selfie_path);
});

it('clears an existing selfie when markClearSelfie + save', function () {
    Storage::fake('public');

    $row = Attendance::factory()->create();
    $oldPath = UploadedFile::fake()->image('old.jpg')->store("attendance/{$row->employee_id}/selfies", 'public');
    $row->forceFill(['selfie_path' => $oldPath])->save();

    Livewire::test(Form::class)
        ->dispatch('attendance:edit', id: $row->id)
        ->call('markClearSelfie')
        ->call('save')
        ->assertHasNoErrors();

    expect($row->fresh()->selfie_path)->toBeNull();
    Storage::disk('public')->assertMissing($oldPath);
});

it('filters punches by employee, type, and date range', function () {
    $a = EmployeeMaster::factory()->create();
    $b = EmployeeMaster::factory()->create();

    Attendance::factory()->punchIn()->create(['employee_id' => $a->id, 'punched_at' => '2026-05-10 09:00']);
    Attendance::factory()->punchOut()->create(['employee_id' => $a->id, 'punched_at' => '2026-05-10 18:00']);
    Attendance::factory()->punchIn()->create(['employee_id' => $b->id, 'punched_at' => '2026-05-12 10:00']);

    Livewire::test(Index::class)
        ->set('employeeFilter', (string) $a->id)
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 2)
        ->set('employeeFilter', 'all')
        ->set('typeFilter', Attendance::TYPE_OUT)
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1)
        ->set('typeFilter', 'all')
        ->set('dateFrom', '2026-05-12')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1);
});

it('validates required fields on save', function () {
    Livewire::test(Form::class)
        ->dispatch('attendance:edit', id: null)
        ->set('employee_id', null)
        ->set('punched_date', '')
        ->set('punched_time', '')
        ->call('save')
        ->assertHasErrors(['employee_id', 'punched_date', 'punched_time']);
});

it('deletes a punch from the index', function () {
    $row = Attendance::factory()->create();

    Livewire::test(Index::class)->call('delete', $row->id);

    expect(Attendance::find($row->id))->toBeNull();
});

it('blocks Form::save for a user without attendance.create permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('attendance.view');
    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('openCreate')
        ->assertStatus(403);
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('attendance.index'))->assertRedirect(route('login'));
});
