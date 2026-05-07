<?php

use App\Modules\JobDescriptionMaster\Livewire\Form;
use App\Modules\JobDescriptionMaster\Livewire\Index;
use App\Modules\JobDescriptionMaster\Models\JobDescriptionMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
    $dept = WorkshopDepartmentMaster::firstOrCreate(['name' => 'SERVICE'], ['is_active' => true]);
    $this->serviceType = ServiceTypeMaster::factory()->create([
        'name' => 'PMS-FOR-TEST',
        'workshop_department_id' => $dept->id,
        'is_active' => true,
    ]);
});

it('renders the index page', function () {
    JobDescriptionMaster::factory()->count(3)->create();
    $this->get(route('job-description-master.index'))->assertOk()->assertSeeLivewire(Index::class);
});

it('filters records by search', function () {
    JobDescriptionMaster::factory()->create(['name' => 'ENGINE OIL CHANGE']);
    JobDescriptionMaster::factory()->create(['name' => 'BRAKE PAD REPLACEMENT']);

    Livewire::test(Index::class)
        ->set('search', 'ENGINE')
        ->assertSee('ENGINE OIL CHANGE')
        ->assertDontSee('BRAKE PAD REPLACEMENT');
});

it('filters by category', function () {
    JobDescriptionMaster::factory()->frequent()->create(['name' => 'FREQ JOB']);
    JobDescriptionMaster::factory()->create(['name' => 'GEN JOB', 'category' => 'general']);

    Livewire::test(Index::class)
        ->set('categoryFilter', 'frequent')
        ->assertSee('FREQ JOB')
        ->assertDontSee('GEN JOB');
});

it('filters by service type', function () {
    $other = ServiceTypeMaster::factory()->create(['name' => 'OTHER-ST']);
    JobDescriptionMaster::factory()->create(['name' => 'JOB ON PMS', 'service_type_id' => $this->serviceType->id]);
    JobDescriptionMaster::factory()->create(['name' => 'JOB ON OTHER', 'service_type_id' => $other->id]);

    Livewire::test(Index::class)
        ->set('serviceTypeFilter', (string) $this->serviceType->id)
        ->assertSee('JOB ON PMS')
        ->assertDontSee('JOB ON OTHER');
});

it('creates with capital typing and persists fk + category + hours', function () {
    Livewire::test(Form::class)
        ->set('name', 'engine oil change')
        ->set('code', 'eng-oil')
        ->set('category', 'frequent')
        ->set('service_type_id', $this->serviceType->id)
        ->set('standard_hours', '0.50')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('job-description-master:saved');

    $r = JobDescriptionMaster::firstOrFail();
    expect($r->name)->toBe('ENGINE OIL CHANGE')
        ->and($r->code)->toBe('ENG-OIL')
        ->and($r->category)->toBe('frequent')
        ->and($r->service_type_id)->toBe($this->serviceType->id)
        ->and((float) $r->standard_hours)->toBe(0.5);
});

it('rejects unknown category', function () {
    Livewire::test(Form::class)
        ->set('name', 'TEST')
        ->set('category', 'invalid')
        ->call('save')
        ->assertHasErrors(['category']);
});

it('rejects unknown service type id', function () {
    Livewire::test(Form::class)
        ->set('name', 'TEST')
        ->set('category', 'general')
        ->set('service_type_id', 99999)
        ->call('save')
        ->assertHasErrors(['service_type_id']);
});

it('blocks duplicate name within same service type', function () {
    JobDescriptionMaster::factory()->create([
        'name' => 'SAME NAME',
        'service_type_id' => $this->serviceType->id,
    ]);

    Livewire::test(Form::class)
        ->set('name', 'SAME NAME')
        ->set('category', 'general')
        ->set('service_type_id', $this->serviceType->id)
        ->call('save')
        ->assertHasErrors(['name']);
});

it('allows duplicate name across different service types', function () {
    $other = ServiceTypeMaster::factory()->create(['name' => 'OTHER-ST-2']);
    JobDescriptionMaster::factory()->create([
        'name' => 'SHARED NAME',
        'service_type_id' => $this->serviceType->id,
    ]);

    Livewire::test(Form::class)
        ->set('name', 'SHARED NAME')
        ->set('category', 'general')
        ->set('service_type_id', $other->id)
        ->call('save')
        ->assertHasNoErrors();
});

it('updates an existing record', function () {
    $r = JobDescriptionMaster::factory()->create(['name' => 'OLD NAME']);
    Livewire::test(Form::class)
        ->dispatch('job-description-master:edit', id: $r->id)
        ->set('name', 'updated')
        ->call('save')
        ->assertHasNoErrors();
    expect($r->fresh()->name)->toBe('UPDATED');
});

it('deletes a record', function () {
    $r = JobDescriptionMaster::factory()->create();
    Livewire::test(Index::class)->call('delete', $r->id);
    expect(JobDescriptionMaster::find($r->id))->toBeNull();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('job-description-master.index'))->assertRedirect(route('login'));
});
