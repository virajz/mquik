<?php

use App\Modules\JobCardPendingReasonMaster\Livewire\Form;
use App\Modules\JobCardPendingReasonMaster\Livewire\Index;
use App\Modules\JobCardPendingReasonMaster\Models\JobCardPendingReasonMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    JobCardPendingReasonMaster::factory()->count(3)->create();

    $this->get(route('job-card-pending-reason-master.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('searches by name or code', function () {
    JobCardPendingReasonMaster::factory()->create(['name' => 'SPARE AWAITED REASON', 'code' => 'SPR']);
    JobCardPendingReasonMaster::factory()->create(['name' => 'ESTIMATE PENDING REASON', 'code' => 'EST']);

    Livewire::test(Index::class)->set('search', 'SPARE AWAITED')
        ->assertSee('SPARE AWAITED REASON')
        ->assertDontSee('ESTIMATE PENDING REASON');

    Livewire::test(Index::class)->set('search', 'est') // case-insensitive via whereLike
        ->assertSee('ESTIMATE PENDING REASON')
        ->assertDontSee('SPARE AWAITED REASON');
});

it('filters by active status', function () {
    JobCardPendingReasonMaster::factory()->create(['name' => 'REASON ENABLED']);
    JobCardPendingReasonMaster::factory()->inactive()->create(['name' => 'REASON DISABLED']);

    Livewire::test(Index::class)->set('statusFilter', 'active')
        ->assertSee('REASON ENABLED')
        ->assertDontSee('REASON DISABLED');

    Livewire::test(Index::class)->set('statusFilter', 'inactive')
        ->assertSee('REASON DISABLED')
        ->assertDontSee('REASON ENABLED');
});

it('creates a reason with code (capital typing)', function () {
    Livewire::test(Form::class)
        ->set('name', 'spare awaited')
        ->set('code', 'spr')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('job-card-pending-reason-master:saved');

    $record = JobCardPendingReasonMaster::firstOrFail();
    expect($record->name)->toBe('SPARE AWAITED')
        ->and($record->code)->toBe('SPR')
        ->and($record->is_active)->toBeTrue();
});

it('updates an existing reason', function () {
    $record = JobCardPendingReasonMaster::factory()->create(['name' => 'OLD REASON']);

    Livewire::test(Form::class)
        ->dispatch('job-card-pending-reason-master:edit', id: $record->id)
        ->set('name', 'updated reason')
        ->call('save')
        ->assertHasNoErrors();

    expect($record->fresh()->name)->toBe('UPDATED REASON');
});

it('deletes a reason from the index', function () {
    $record = JobCardPendingReasonMaster::factory()->create();

    Livewire::test(Index::class)->call('delete', $record->id);

    expect(JobCardPendingReasonMaster::find($record->id))->toBeNull();
});

it('validates name is required and unique', function () {
    JobCardPendingReasonMaster::factory()->create(['name' => 'EXISTING REASON']);

    Livewire::test(Form::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);

    Livewire::test(Form::class)
        ->set('name', 'EXISTING REASON')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('allows updating a reason without triggering self-uniqueness conflict', function () {
    $record = JobCardPendingReasonMaster::factory()->create(['name' => 'SPARE AWAITED']);

    Livewire::test(Form::class)
        ->dispatch('job-card-pending-reason-master:edit', id: $record->id)
        ->set('name', 'SPARE AWAITED')
        ->call('save')
        ->assertHasNoErrors();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('job-card-pending-reason-master.index'))->assertRedirect(route('login'));
});
