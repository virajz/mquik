<?php

use App\Modules\Appointment\Models\Appointment;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\JobHistory\Livewire\VehicleTimeline;
use App\Modules\JobHistory\Models\JobCardHistoryEvent;
use App\Modules\JobHistory\Support\JobCardHistoryRecorder;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the vehicle timeline page', function () {
    $vehicle = CustomerVehicleMaster::factory()->create();

    $this->get(route('job-history.vehicle-timeline', $vehicle->id))
        ->assertOk()
        ->assertSeeLivewire(VehicleTimeline::class);
});

it('stamps the vehicle on events recorded against a job card', function () {
    $vehicle = CustomerVehicleMaster::factory()->create();
    $jobCard = JobCard::factory()->create(['customer_vehicle_id' => $vehicle->id]);

    $event = JobCardHistoryRecorder::record($jobCard->id, JobCardHistoryEvent::TYPE_CREATED, 'Created');

    expect($event->customer_vehicle_id)->toBe($vehicle->id);
});

it('records an appointment on the vehicle timeline before any job card exists', function () {
    $vehicle = CustomerVehicleMaster::factory()->create();

    $appointment = Appointment::factory()->create(['customer_vehicle_id' => $vehicle->id]);

    $event = JobCardHistoryEvent::query()
        ->where('customer_vehicle_id', $vehicle->id)
        ->where('event_type', JobCardHistoryEvent::TYPE_APPOINTMENT_BOOKED)
        ->first();

    expect($event)->not->toBeNull()
        ->and($event->job_card_id)->toBeNull()
        ->and($event->summary)->toContain($appointment->appointment_no);
});

it('shows events from every job card the vehicle has had', function () {
    $vehicle = CustomerVehicleMaster::factory()->create();
    $first = JobCard::factory()->create(['customer_vehicle_id' => $vehicle->id]);
    $second = JobCard::factory()->create(['customer_vehicle_id' => $vehicle->id]);

    JobCardHistoryRecorder::record($first->id, JobCardHistoryEvent::TYPE_CREATED, 'FIRST VISIT EVENT');
    JobCardHistoryRecorder::record($second->id, JobCardHistoryEvent::TYPE_CREATED, 'SECOND VISIT EVENT');

    Livewire::test(VehicleTimeline::class, ['customerVehicle' => $vehicle])
        ->assertSee('FIRST VISIT EVENT')
        ->assertSee('SECOND VISIT EVENT');
});

it('narrows the timeline to a single visit', function () {
    $vehicle = CustomerVehicleMaster::factory()->create();
    $first = JobCard::factory()->create(['customer_vehicle_id' => $vehicle->id]);
    $second = JobCard::factory()->create(['customer_vehicle_id' => $vehicle->id]);

    JobCardHistoryRecorder::record($first->id, JobCardHistoryEvent::TYPE_CREATED, 'FIRST VISIT EVENT');
    JobCardHistoryRecorder::record($second->id, JobCardHistoryEvent::TYPE_CREATED, 'SECOND VISIT EVENT');

    Livewire::test(VehicleTimeline::class, ['customerVehicle' => $vehicle])
        ->set('jobCardFilter', $first->id)
        ->assertSee('FIRST VISIT EVENT')
        ->assertDontSee('SECOND VISIT EVENT')
        ->call('clearFilter')
        ->assertSee('SECOND VISIT EVENT');
});

it('does not show another vehicle\'s events', function () {
    $mine = CustomerVehicleMaster::factory()->create();
    $theirs = CustomerVehicleMaster::factory()->create();

    JobCardHistoryRecorder::record(
        JobCard::factory()->create(['customer_vehicle_id' => $theirs->id])->id,
        JobCardHistoryEvent::TYPE_CREATED,
        'OTHER VEHICLE EVENT'
    );

    Livewire::test(VehicleTimeline::class, ['customerVehicle' => $mine])
        ->assertDontSee('OTHER VEHICLE EVENT')
        ->assertSee('Nothing recorded yet');
});
