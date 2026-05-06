<?php

use App\Models\User;
use App\Modules\ServiceTypeMaster\Livewire\Form;
use App\Modules\ServiceTypeMaster\Livewire\Index;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->serviceDept = WorkshopDepartmentMaster::firstOrCreate(['name' => 'SERVICE'], ['is_active' => true]);
    $this->bodyshopDept = WorkshopDepartmentMaster::firstOrCreate(['name' => 'BODYSHOP'], ['is_active' => true]);
});

it('renders the index page', function () {
    ServiceTypeMaster::factory()->count(3)->create();

    $this->get(route('service-type-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('filters records by search', function () {
    ServiceTypeMaster::factory()->create(['name' => 'PERIODIC MAINTENANCE', 'code' => 'PMS']);
    ServiceTypeMaster::factory()->create(['name' => 'BODYSHOP REPAIR', 'code' => 'BSR']);

    Livewire::test(Index::class)
        ->set('search', 'PERIODIC')
        ->assertSee('PERIODIC MAINTENANCE')
        ->assertDontSee('BODYSHOP REPAIR');
});

it('filters records by department', function () {
    ServiceTypeMaster::factory()->create(['name' => 'PMS BIG', 'workshop_department_id' => $this->serviceDept->id]);
    ServiceTypeMaster::factory()->create(['name' => 'BSR BIG', 'workshop_department_id' => $this->bodyshopDept->id]);

    Livewire::test(Index::class)
        ->set('departmentFilter', (string) $this->bodyshopDept->id)
        ->assertSee('BSR BIG')
        ->assertDontSee('PMS BIG');
});

it('creates a record via the form', function () {
    Livewire::test(Form::class)
        ->set('name', 'periodic maintenance')
        ->set('code', 'pms')
        ->set('workshop_department_id', $this->serviceDept->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('service-type-master:saved');

    expect(ServiceTypeMaster::count())->toBe(1);
    expect(ServiceTypeMaster::first()->name)->toBe('PERIODIC MAINTENANCE');
    expect(ServiceTypeMaster::first()->workshop_department_id)->toBe($this->serviceDept->id);
});

it('updates an existing record via the form', function () {
    $r = ServiceTypeMaster::factory()->create(['name' => 'OLD NAME']);

    Livewire::test(Form::class)
        ->dispatch('service-type-master:edit', id: $r->id)
        ->set('name', 'new name')
        ->call('save')
        ->assertDispatched('service-type-master:saved');

    expect($r->fresh()->name)->toBe('NEW NAME');
});

it('deletes a record from the index', function () {
    $r = ServiceTypeMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $r->id);

    expect(ServiceTypeMaster::find($r->id))->toBeNull();
});

it('validates required name and department', function () {
    Livewire::test(Form::class)
        ->set('name', '')
        ->set('workshop_department_id', null)
        ->call('save')
        ->assertHasErrors(['name' => 'required', 'workshop_department_id' => 'required']);
});

it('rejects unknown workshop department FK', function () {
    Livewire::test(Form::class)
        ->set('name', 'TEST')
        ->set('workshop_department_id', 99999)
        ->call('save')
        ->assertHasErrors(['workshop_department_id']);
});

it('rejects duplicate name', function () {
    ServiceTypeMaster::factory()->create(['name' => 'PMS']);

    Livewire::test(Form::class)
        ->set('name', 'PMS')
        ->set('workshop_department_id', $this->serviceDept->id)
        ->call('save')
        ->assertHasErrors(['name']);
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('service-type-master.index'))->assertRedirect(route('login'));
});
