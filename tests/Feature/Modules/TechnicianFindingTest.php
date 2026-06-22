<?php

use App\Modules\JobCard\Models\JobCard;
use App\Modules\JobHistory\Models\JobCardHistoryEvent;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TechnicianFinding\Livewire\Edit;
use App\Modules\TechnicianFinding\Livewire\Index;
use App\Modules\TechnicianFinding\Models\TechnicianFinding;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    TechnicianFinding::factory()->count(2)->create();

    $this->get(route('technician-finding.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('technician-finding.index'))->assertRedirect(route('login'));
});

it('records a finding, stamps TF number, and logs a job card history event', function () {
    $jobCard = JobCard::factory()->create();

    Livewire::test(Edit::class)
        ->set('job_card_id', $jobCard->id)
        ->set('finding_type', TechnicianFinding::TYPE_SPARE)
        ->set('description', 'brake disc worn')
        ->set('estimated_amount', '1500')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $finding = TechnicianFinding::firstOrFail();
    expect($finding->finding_no)->toBe('TF-'.str_pad((string) $finding->id, 5, '0', STR_PAD_LEFT))
        ->and($finding->description)->toBe('BRAKE DISC WORN');

    $event = JobCardHistoryEvent::where('job_card_id', $jobCard->id)
        ->where('event_type', JobCardHistoryEvent::TYPE_FINDING_RECORDED)
        ->first();
    expect($event)->not->toBeNull();
});

it('nulls the labour link when type is spare', function () {
    $jobCard = JobCard::factory()->create();
    $spare = SpareMaster::factory()->create();
    $labour = LabourMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('job_card_id', $jobCard->id)
        ->set('finding_type', TechnicianFinding::TYPE_SPARE)
        ->set('spare_id', $spare->id)
        ->set('labour_id', $labour->id)
        ->set('description', 'worn part')
        ->call('save')
        ->assertHasNoErrors();

    $finding = TechnicianFinding::firstOrFail();
    expect($finding->spare_id)->toBe($spare->id)
        ->and($finding->labour_id)->toBeNull();
});

it('deletes a finding from the index', function () {
    $finding = TechnicianFinding::factory()->create();

    Livewire::test(Index::class)->call('delete', $finding->id);

    expect(TechnicianFinding::find($finding->id))->toBeNull();
});
