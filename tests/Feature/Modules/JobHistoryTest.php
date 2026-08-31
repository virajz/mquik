<?php

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DigitalInspection\Models\DigitalInspection;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\GateInOut\Models\GateInOut;
use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use App\Modules\JobCard\Livewire\Edit as JobCardEdit;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\JobCard\Models\JobCardComplaint;
use App\Modules\JobHistory\Livewire\Show;
use App\Modules\JobHistory\Models\JobCardHistoryEvent;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('records a created event when a JobCard is created', function () {
    $jc = JobCard::factory()->create();

    $events = JobCardHistoryEvent::where('job_card_id', $jc->id)->get();
    expect($events->pluck('event_type'))->toContain(JobCardHistoryEvent::TYPE_CREATED);
});

it('records a status_changed event when status moves', function () {
    $jc = JobCard::factory()->create();
    JobCardHistoryEvent::where('job_card_id', $jc->id)->delete();   // clear `created`

    $jc->update(['status' => JobCard::STATUS_IN_PROGRESS]);

    $event = JobCardHistoryEvent::where('job_card_id', $jc->id)
        ->where('event_type', JobCardHistoryEvent::TYPE_STATUS_CHANGED)
        ->first();

    expect($event)->not->toBeNull()
        ->and($event->payload['from'])->toBe(JobCard::STATUS_OPEN)
        ->and($event->payload['to'])->toBe(JobCard::STATUS_IN_PROGRESS);
});

it('records a cancelled event when status moves to cancelled', function () {
    $jc = JobCard::factory()->create();

    $jc->update(['status' => JobCard::STATUS_CANCELLED]);

    expect(JobCardHistoryEvent::where('job_card_id', $jc->id)
        ->where('event_type', JobCardHistoryEvent::TYPE_CANCELLED)
        ->exists())->toBeTrue();
});

it('records an advisor_changed event when advisor is reassigned', function () {
    $jc = JobCard::factory()->create();
    $oldAdvisor = $jc->assigned_advisor_id;
    JobCardHistoryEvent::where('job_card_id', $jc->id)->delete();

    $jc->update(['assigned_advisor_id' => EmployeeMaster::factory()->create()->id]);

    $event = JobCardHistoryEvent::where('job_card_id', $jc->id)
        ->where('event_type', JobCardHistoryEvent::TYPE_ADVISOR_CHANGED)
        ->first();

    expect($event)->not->toBeNull()
        ->and($event->payload['from'])->toBe($oldAdvisor);
});

it('records a complaint_added event when a complaint is created', function () {
    $jc = JobCard::factory()->create();

    JobCardComplaint::create([
        'job_card_id' => $jc->id,
        'description' => 'BRAKE NOISE',
        'severity' => 'high',
        'sequence_no' => 1,
    ]);

    $event = JobCardHistoryEvent::where('job_card_id', $jc->id)
        ->where('event_type', JobCardHistoryEvent::TYPE_COMPLAINT_ADDED)
        ->first();

    expect($event)->not->toBeNull()
        ->and($event->summary)->toContain('BRAKE NOISE')
        ->and($event->payload['severity'])->toBe('high');
});

it('records a complaint_resolved event when a complaint is marked resolved', function () {
    $jc = JobCard::factory()->create();
    $complaint = JobCardComplaint::create([
        'job_card_id' => $jc->id,
        'description' => 'BRAKE NOISE',
        'severity' => 'high',
        'sequence_no' => 1,
    ]);

    $complaint->update(['is_resolved' => true]);

    expect(JobCardHistoryEvent::where('job_card_id', $jc->id)
        ->where('event_type', JobCardHistoryEvent::TYPE_COMPLAINT_RESOLVED)
        ->exists())->toBeTrue();
});

it('records an inspection_started event when a DI is created in WIP', function () {
    $template = InspectionTemplateMaster::factory()->create();
    $di = DigitalInspection::factory()->create([
        'inspection_template_id' => $template->id,
        'status' => DigitalInspection::STATUS_WIP,
    ]);

    expect(JobCardHistoryEvent::where('job_card_id', $di->job_card_id)
        ->where('event_type', JobCardHistoryEvent::TYPE_INSPECTION_STARTED)
        ->exists())->toBeTrue();
});

it('records an inspection_completed event when a DI moves to completed', function () {
    $template = InspectionTemplateMaster::factory()->create();
    $di = DigitalInspection::factory()->create([
        'inspection_template_id' => $template->id,
        'status' => DigitalInspection::STATUS_PENDING,
    ]);

    $di->update(['status' => DigitalInspection::STATUS_COMPLETED]);

    expect(JobCardHistoryEvent::where('job_card_id', $di->job_card_id)
        ->where('event_type', JobCardHistoryEvent::TYPE_INSPECTION_COMPLETED)
        ->exists())->toBeTrue();
});

it('renders the history page with events newest-first', function () {
    $jc = JobCard::factory()->create();
    // forge an older event manually
    JobCardHistoryEvent::create([
        'job_card_id' => $jc->id,
        'event_type' => JobCardHistoryEvent::TYPE_STATUS_CHANGED,
        'actor_user_id' => null,
        'summary' => 'SOONER',
        'payload' => null,
        'occurred_at' => now()->subDays(2),
    ]);
    JobCardHistoryEvent::create([
        'job_card_id' => $jc->id,
        'event_type' => JobCardHistoryEvent::TYPE_STATUS_CHANGED,
        'actor_user_id' => null,
        'summary' => 'LATER',
        'payload' => null,
        'occurred_at' => now()->subDay(),
    ]);

    $response = $this->get(route('job-history.show', $jc));
    $response->assertOk()->assertSeeLivewire(Show::class);

    $html = $response->getContent();
    expect(strpos($html, 'LATER'))->toBeLessThan(strpos($html, 'SOONER'));
});

it('captures the actor user on each event', function () {
    $user = adminUser();
    $this->actingAs($user);

    $jc = JobCard::factory()->create();

    $event = JobCardHistoryEvent::where('job_card_id', $jc->id)->first();
    expect($event->actor_user_id)->toBe($user->id);
});

it('requires authentication for history page', function () {
    $jc = JobCard::factory()->create();
    auth()->logout();

    $this->get(route('job-history.show', $jc))->assertRedirect(route('login'));
});

it('does NOT record a status_changed event for the JobCard creation row itself', function () {
    $jc = JobCard::factory()->create();

    $statusEvents = JobCardHistoryEvent::where('job_card_id', $jc->id)
        ->where('event_type', JobCardHistoryEvent::TYPE_STATUS_CHANGED)
        ->count();
    expect($statusEvents)->toBe(0);
});

it('Edit JobCard form save in the wild produces the right event chain', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $dept = WorkshopDepartmentMaster::factory()->create();
    $advisor = EmployeeMaster::factory()->create();

    Livewire::test(JobCardEdit::class)
        ->set('gate_event_id', GateInOut::factory()->create([
            'customer_id' => $customer->id,
            'customer_vehicle_id' => $vehicle->id,
        ])->id)
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->set('fuel_level', 'half')
        ->set('terms_accepted_by', 'customer')
        // After the department: picking one clears the service type and the people.
        ->set('service_type_id', ServiceTypeMaster::factory()->create(['is_active' => true])->id)
        ->set('assigned_technician_id', EmployeeMaster::factory()->create(['is_active' => true])->id)
        ->call('save')
        ->assertHasNoErrors();

    $jc = JobCard::first();
    $events = JobCardHistoryEvent::where('job_card_id', $jc->id)->pluck('event_type');
    expect($events)->toContain(JobCardHistoryEvent::TYPE_CREATED);
});
