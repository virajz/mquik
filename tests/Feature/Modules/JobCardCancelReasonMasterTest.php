<?php

use App\Models\User;
use App\Modules\JobCardCancelReasonMaster\Livewire\Form;
use App\Modules\JobCardCancelReasonMaster\Livewire\Index;
use App\Modules\JobCardCancelReasonMaster\Models\JobCardCancelReasonMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders the index page', function () {
    JobCardCancelReasonMaster::factory()->count(3)->create();

    $this->get(route('job-card-cancel-reason-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name or code', function () {
    JobCardCancelReasonMaster::factory()->create(['name' => 'WALK-IN ABANDONED', 'code' => 'WIA']);
    JobCardCancelReasonMaster::factory()->create(['name' => 'WARRANTY DECLINED', 'code' => 'WDC']);

    Livewire::test(Index::class)->set('search', 'WALK-IN')
        ->assertSee('WALK-IN ABANDONED')
        ->assertDontSee('WARRANTY DECLINED');

    Livewire::test(Index::class)->set('search', 'wdc') // case-insensitive via whereLike
        ->assertSee('WARRANTY DECLINED')
        ->assertDontSee('WALK-IN ABANDONED');
});

it('filters by active status', function () {
    JobCardCancelReasonMaster::factory()->create(['name' => 'REASON ENABLED']);
    JobCardCancelReasonMaster::factory()->inactive()->create(['name' => 'REASON DISABLED']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('REASON ENABLED')
        ->assertDontSee('REASON DISABLED');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('REASON DISABLED')
        ->assertDontSee('REASON ENABLED');
});

it('creates a cancel reason with capital typing', function () {
    Livewire::test(Form::class)
        ->set('name', 'walk-in abandoned')
        ->set('code', 'wia')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('job-card-cancel-reason-master:saved');

    $record = JobCardCancelReasonMaster::firstOrFail();
    expect($record->name)->toBe('WALK-IN ABANDONED')
        ->and($record->code)->toBe('WIA')
        ->and($record->is_active)->toBeTrue();
});

it('updates an existing cancel reason', function () {
    $record = JobCardCancelReasonMaster::factory()->create(['name' => 'OLD NAME']);

    Livewire::test(Form::class)
        ->dispatch('job-card-cancel-reason-master:edit', id: $record->id)
        ->set('name', 'updated name')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED NAME');
});

it('deletes a cancel reason from the index', function () {
    $record = JobCardCancelReasonMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(JobCardCancelReasonMaster::find($record->id))->toBeNull();
});

it('validates name is required and unique', function () {
    JobCardCancelReasonMaster::factory()->create(['name' => 'EXISTING']);

    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);

    Livewire::test(Form::class)
        ->set('name', 'EXISTING')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('allows updating a cancel reason without triggering self-uniqueness conflict', function () {
    $record = JobCardCancelReasonMaster::factory()->create(['name' => 'WALK-IN ABANDONED']);

    Livewire::test(Form::class)
        ->dispatch('job-card-cancel-reason-master:edit', id: $record->id)
        ->set('name', 'WALK-IN ABANDONED') // same name as the record being edited
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('job-card-cancel-reason-master.index'))->assertRedirect(route('login'));
});
