<?php

use App\Models\User;
use App\Modules\Appointment\Models\Appointment;
use App\Modules\CancelReasonMaster\Models\CancelReasonMaster;
use App\Modules\ChecklistTemplateMaster\Models\ChecklistTemplateMaster;
use App\Modules\CourierCompanyMaster\Models\CourierCompanyMaster;
use App\Modules\CustomerMaster\Models\CustomerAddress;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DesignationMaster\Models\DesignationMaster;
use App\Modules\DistanceSlabMaster\Models\DistanceSlabMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\GateInOut\Models\GateInOut;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PhotoTypeMaster\Models\PhotoTypeMaster;
use App\Modules\PickupDrop\Livewire\Edit;
use App\Modules\PickupDrop\Livewire\Index;
use App\Modules\PickupDrop\Models\PickupDrop;
use App\Modules\PickupDropOptionMaster\Models\PickupDropOptionMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\TimeSlotMaster\Models\TimeSlotMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

/** The form now derives its leg from a required Pickup/Drop Type. */
function pickupType(): PickupDropOptionMaster
{
    return PickupDropOptionMaster::firstOrCreate(
        ['name' => 'WORKSHOP PICKUP ONLY'],
        ['involves_pickup' => true, 'involves_drop' => false, 'is_active' => true],
    );
}

it('renders the index page', function () {
    PickupDrop::factory()->count(3)->create();

    $this->get(route('pickup-drop.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('auto-stamps PD-00001 style pickup_drop_no on create', function () {
    $row = PickupDrop::factory()->create();

    expect($row->fresh()->pickup_drop_no)->toBe('PD-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT));
});

it('filters by status, direction, and driver', function () {
    $driver = EmployeeMaster::factory()->create(['name' => 'TARGET DRIVER']);
    PickupDrop::factory()->create();
    PickupDrop::factory()->drop()->create(['delivered_at' => now()]);
    PickupDrop::factory()->drop()->create();
    PickupDrop::factory()->create(['driver_employee_id' => $driver->id]);

    Livewire::test(Index::class)
        ->set('statusFilter', PickupDrop::STATUS_VEHICLE_DELIVERED)
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1)
        ->set('statusFilter', 'all')
        ->set('directionFilter', 'drop')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 2)
        ->set('directionFilter', 'all')
        ->set('driverFilter', (string) $driver->id)
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1);
});

it('creates a pickup with driver assignment', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $driver = EmployeeMaster::factory()->create();

    validPickupDrop(Livewire::test(Edit::class))
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('pickup_address', '12 maple st')
        ->set('driver_employee_id', $driver->id)
        ->call('save')
        ->assertHasNoErrors();

    $row = PickupDrop::first();
    expect($row->pickup_address)->toBe('12 MAPLE ST')
        ->and($row->driver_employee_id)->toBe($driver->id)
        ->and($row->vendor_courier_id)->toBeNull()
        ->and($row->pickup_drop_no)->toStartWith('PD-');
});

it('rejects create when neither driver nor vendor is assigned', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);

    validPickupDrop(Livewire::test(Edit::class))
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('pickup_address', 'somewhere')
        ->set('driver_employee_id', null)
        ->call('save')
        ->assertHasErrors(['driver_employee_id']);

    expect(PickupDrop::count())->toBe(0);
});

it('rejects create when both driver AND vendor are assigned', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $driver = EmployeeMaster::factory()->create();
    $courier = CourierCompanyMaster::factory()->create();

    validPickupDrop(Livewire::test(Edit::class))
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('pickup_address', 'somewhere')
        ->set('driver_employee_id', $driver->id)
        ->set('vendor_courier_id', $courier->id)
        ->call('save')
        ->assertHasErrors(['vendor_courier_id']);

    expect(PickupDrop::count())->toBe(0);
});

it('derives the customer from the picked vehicle (vehicle-first)', function () {
    $owner = CustomerMaster::factory()->create();
    $other = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $owner->id]);

    // Picking a vehicle sets the customer to its owner, overriding any stale pick.
    Livewire::test(Edit::class)
        ->set('customer_id', $other->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->assertSet('customer_id', $owner->id);
});

it('prefills from an appointment when from-appointment query param is set', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $appointment = Appointment::factory()->pickup()->create([
        'customer_id' => $customer->id,
        'customer_vehicle_id' => $vehicle->id,
        'pickup_address' => '99 OAK STREET',
        'pickup_contact_phone' => '9999999999',
    ]);

    Livewire::test(Edit::class, ['fromAppointment' => $appointment->id])
        ->assertSet('appointment_id', $appointment->id)
        ->assertSet('customer_id', $customer->id)
        ->assertSet('customer_vehicle_id', $vehicle->id)
        ->assertSet('pickup_address', '99 OAK STREET')
        ->assertSet('contact_phone', '9999999999');
});

it('combines scheduled_date + scheduled_time into a single datetime on save', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $driver = EmployeeMaster::factory()->create();

    validPickupDrop(Livewire::test(Edit::class))
        ->set('scheduled_date', '2026-09-10')
        ->set('scheduled_time', '08:15')
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('pickup_address', 'somewhere')
        ->set('driver_employee_id', $driver->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(PickupDrop::first()->scheduled_at->format('Y-m-d H:i'))->toBe('2026-09-10 08:15');
});

it('Edit::save blocks a user without create permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('pickup_drop.view');
    $this->actingAs($user);

    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $driver = EmployeeMaster::factory()->create();

    validPickupDrop(Livewire::test(Edit::class))
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('pickup_address', 'somewhere')
        ->set('driver_employee_id', $driver->id)
        ->call('save')
        ->assertStatus(403);
});

it('deletes a pickup-drop from the index', function () {
    $row = PickupDrop::factory()->create();

    Livewire::test(Index::class)->call('delete', $row->id);

    expect(PickupDrop::find($row->id))->toBeNull();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('pickup-drop.index'))->assertRedirect(route('login'));
});

/** Minimum valid selections for the Edit form. */
function validPickupDrop(Testable $c): Testable
{
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);

    return $c
        ->set('pickup_drop_option_id', pickupType()->id)
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('pickup_address', 'somewhere')
        ->set('time_slot_id', TimeSlotMaster::firstOrCreate(
            ['name' => '09:00-10:00'],
            ['slot_start_time' => '09:00:00', 'slot_end_time' => '10:00:00', 'is_active' => true],
        )->id)
        ->set('workshop_department_id', ($dept = WorkshopDepartmentMaster::factory()->create())->id)
        ->set('service_type_id', ServiceTypeMaster::factory()->create(['workshop_department_id' => $dept->id])->id)
        ->set('advisor_employee_id', EmployeeMaster::factory()->create()->id)
        ->call('addComplaint')
        ->set('complaints.0.description', 'GENERAL CHECK')
        ->set('driver_employee_id', EmployeeMaster::factory()->create()->id);
}

it('picks the distance slab and charge from the entered kilometres', function () {
    DistanceSlabMaster::factory()->create([
        'name' => '0-5 KM', 'min_km' => 0, 'max_km' => 5, 'charge_amount' => 200,
    ]);
    $mid = DistanceSlabMaster::factory()->create([
        'name' => '6-15 KM', 'min_km' => 6, 'max_km' => 15, 'charge_amount' => 300,
    ]);

    Livewire::test(Edit::class)
        ->set('distance_km', '12')
        ->assertSet('distance_slab_id', $mid->id)
        ->assertSet('distance_charge', '300.00');
});

it('snapshots the chosen checklist template into document lines', function () {
    $template = ChecklistTemplateMaster::factory()->create([
        'is_active' => true,
        'items' => [
            ['label' => 'RC COPY', 'is_required' => true],
            ['label' => 'DL COPY', 'is_required' => false],
        ],
    ]);

    $component = Livewire::test(Edit::class)->set('checklist_template_id', $template->id);

    expect($component->get('documents'))->toHaveCount(2)
        ->and($component->get('documents')[0]['label'])->toBe('RC COPY')
        ->and($component->get('documents')[0]['is_required'])->toBeTrue();
});

it('saves complaints and document lines, and re-syncs on update', function () {
    validPickupDrop(Livewire::test(Edit::class))
        ->set('complaints.0.description', 'ac not cooling')
        ->call('addDocument')
        ->set('documents.0.label', 'rc copy')
        ->set('documents.0.is_collected', true)
        ->call('save')
        ->assertHasNoErrors();

    $row = PickupDrop::first();
    expect($row->complaints)->toHaveCount(1)
        ->and($row->complaints->first()->description)->toBe('AC NOT COOLING')
        ->and($row->documents)->toHaveCount(1)
        ->and($row->documents->first()->label)->toBe('RC COPY')
        ->and($row->documents->first()->is_collected)->toBeTrue();

    // Complaints are mandatory now — swapping the line re-syncs, emptying it is an error.
    Livewire::test(Edit::class, ['pickupDrop' => $row])
        ->set('pickup_drop_option_id', pickupType()->id)
        ->set('complaints.0.description', 'brake noise')
        ->call('save')
        ->assertHasNoErrors();

    expect($row->fresh()->complaints)->toHaveCount(1)
        ->and($row->fresh()->complaints->first()->description)->toBe('BRAKE NOISE');

    Livewire::test(Edit::class, ['pickupDrop' => $row->fresh()])
        ->set('pickup_drop_option_id', pickupType()->id)
        ->call('removeComplaint', 0)
        ->call('save')
        ->assertHasErrors(['complaints']);
});

it('requires a reason to cancel, and derives the cancelled status from it', function () {
    $row = PickupDrop::factory()->create();

    Livewire::test(Edit::class, ['pickupDrop' => $row])
        ->call('cancelPickupDrop')
        ->assertHasErrors(['cancel_reason_id']);

    expect($row->fresh()->status)->not->toBe(PickupDrop::STATUS_CANCELLED);

    Livewire::test(Edit::class, ['pickupDrop' => $row])
        ->set('cancel_reason_id', CancelReasonMaster::factory()->create()->id)
        ->call('cancelPickupDrop');

    expect($row->fresh()->status)->toBe(PickupDrop::STATUS_CANCELLED)
        ->and($row->fresh()->cancelled_at)->not->toBeNull();

    Livewire::test(Edit::class, ['pickupDrop' => $row->fresh()])->call('restorePickupDrop');

    expect($row->fresh()->cancelled_at)->toBeNull()
        ->and($row->fresh()->status)->not->toBe(PickupDrop::STATUS_CANCELLED);
});

it('stores condition photos against a photo type and leg', function () {
    Storage::fake('public');
    $type = PhotoTypeMaster::factory()->create(['name' => 'ODOMETER']);

    validPickupDrop(Livewire::test(Edit::class))
        ->call('addPhoto')
        ->set('photos.0.photo_type_id', $type->id)
        ->set('photos.0.leg', PickupDrop::LEG_PICKUP)
        ->set('photoFiles.0', UploadedFile::fake()->image('odo.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $photo = PickupDrop::first()->photos->first();
    expect($photo)->not->toBeNull()
        ->and($photo->photo_type_id)->toBe($type->id)
        ->and($photo->leg)->toBe(PickupDrop::LEG_PICKUP);
    Storage::disk('public')->assertExists($photo->path);
});

it('stamps rescheduled_from_at when the job is moved', function () {
    $row = PickupDrop::factory()->create(['scheduled_at' => '2026-09-01 09:00:00']);

    validPickupDrop(Livewire::test(Edit::class, ['pickupDrop' => $row]))
        ->set('scheduled_date', '2026-09-03')
        ->set('scheduled_time', '11:00')
        ->call('save')
        ->assertHasNoErrors();

    expect($row->fresh()->rescheduled_from_at?->format('Y-m-d H:i'))->toBe('2026-09-01 09:00');
});

it('surfaces the driver stage on the linked appointment', function () {
    $appointment = Appointment::factory()->create(['appointment_at' => now()->addDay()]);
    $job = PickupDrop::factory()->create([
        'appointment_id' => $appointment->id,
        'status' => PickupDrop::STATUS_DRIVER_ASSIGNED,
    ]);

    expect($appointment->fresh()->effectiveStatusLabel())->toBe('Driver Assigned');

    // Once the driver has the car, the appointment's own derived ladder takes over.
    $job->update(['status' => PickupDrop::STATUS_COMPLETED]);
    expect($appointment->fresh()->effectiveStatusLabel())->toBe('Vehicle Collected');
});

it('prefills and links from a job card (?from-job-card handoff)', function () {
    $jobCard = JobCard::factory()->create();

    $component = Livewire::test(Edit::class, ['fromJobCard' => $jobCard->id]);

    expect($component->get('job_card_id'))->toBe($jobCard->id)
        ->and($component->get('customer_id'))->toBe($jobCard->customer_id)
        ->and($component->get('customer_vehicle_id'))->toBe($jobCard->customer_vehicle_id);
});

it('persists the job_card link when saved', function () {
    $jobCard = JobCard::factory()->create();

    validPickupDrop(Livewire::test(Edit::class, ['fromJobCard' => $jobCard->id]))
        ->call('save')
        ->assertHasNoErrors();

    expect(PickupDrop::latest('id')->first()->job_card_id)->toBe($jobCard->id);
});

it('resolves the drop address live from a linked saved address (edits propagate)', function () {
    $customer = CustomerMaster::factory()->create();
    $addr = CustomerAddress::factory()->create(['customer_id' => $customer->id, 'address_line' => '7 PARK LANE', 'region_id' => null]);
    $pd = PickupDrop::factory()->create([
        'customer_id' => $customer->id,
        'drop_address_id' => $addr->id,
        'drop_address' => null,
    ]);

    expect($pd->resolvedDropAddress())->toContain('7 PARK LANE');

    $addr->update(['address_line' => '8 QUEEN ST']);
    expect($pd->fresh()->resolvedDropAddress())->toContain('8 QUEEN ST');
});

/*
|--------------------------------------------------------------------------
| Department drives service type and advisor
|--------------------------------------------------------------------------
*/

it('offers only the department\'s advisors, and only ADVISOR-designated staff', function () {
    // The workshop department mirrors an HR department by name on save.
    $dept = WorkshopDepartmentMaster::factory()->create();
    $hrId = $dept->fresh()->department_id;
    $designation = DesignationMaster::factory()->create(['name' => 'SERVICE ADVISOR']);

    $advisor = EmployeeMaster::factory()->create(['department_id' => $hrId, 'designation_id' => $designation->id]);
    EmployeeMaster::factory()->create(['department_id' => $hrId, 'designation_id' => DesignationMaster::factory()->create(['name' => 'DRIVER'])->id]); // not an advisor — not offered
    EmployeeMaster::factory()->create(['designation_id' => $designation->id]); // other department — not offered

    $offered = Livewire::test(Edit::class)
        ->set('workshop_department_id', $dept->id)
        ->instance()->advisors;

    expect($offered->pluck('id')->all())->toBe([$advisor->id]);
});

it('clears service type and advisor when the department changes', function () {
    $dept = WorkshopDepartmentMaster::factory()->create();
    $other = WorkshopDepartmentMaster::factory()->create();
    $st = ServiceTypeMaster::factory()->create(['workshop_department_id' => $dept->id]);

    $component = Livewire::test(Edit::class)
        ->set('workshop_department_id', $dept->id)
        ->set('service_type_id', $st->id)
        ->set('workshop_department_id', $other->id);

    expect($component->get('service_type_id'))->toBeNull()
        ->and($component->get('advisor_employee_id'))->toBeNull();
});

it('rejects a service type from another department on save', function () {
    $theirs = ServiceTypeMaster::factory()->create(['workshop_department_id' => WorkshopDepartmentMaster::factory()->create()->id]);

    validPickupDrop(Livewire::test(Edit::class))
        ->set('service_type_id', $theirs->id)
        ->call('save')
        ->assertHasErrors(['service_type_id']);
});

/*
|--------------------------------------------------------------------------
| Derived status & assignment history
|--------------------------------------------------------------------------
*/

it('derives the ladder from recorded facts', function () {
    $job = PickupDrop::factory()->create(['driver_employee_id' => null, 'vendor_courier_id' => null]);
    expect($job->fresh()->status)->toBe(PickupDrop::STATUS_PENDING);

    $job->fresh()->update(['driver_employee_id' => EmployeeMaster::factory()->create()->id]);
    $job = $job->fresh();
    expect($job->status)->toBe(PickupDrop::STATUS_DRIVER_ASSIGNED)
        ->and($job->assigned_at)->not->toBeNull()
        ->and($job->assigned_by_user_id)->not->toBeNull();

    $job->forceFill(['departed_at' => now()])->save();
    expect($job->fresh()->status)->toBe(PickupDrop::STATUS_DRIVER_ON_THE_WAY);

    $job->fresh()->forceFill(['collected_at' => now()])->save();
    expect($job->fresh()->status)->toBe(PickupDrop::STATUS_VEHICLE_COLLECTED);
});

it('completes a pickup when the vehicle\'s inward is recorded', function () {
    $job = PickupDrop::factory()->create([
        'driver_employee_id' => EmployeeMaster::factory()->create()->id,
        'vendor_courier_id' => null,
        'collected_at' => now(),
    ]);
    $job->save();

    GateInOut::factory()->create([
        'customer_vehicle_id' => $job->customer_vehicle_id,
        'entered_at' => now(),
    ]);

    expect($job->fresh()->status)->toBe(PickupDrop::STATUS_COMPLETED);
});

it('marks a drop delivered when its OTP is verified', function () {
    $job = PickupDrop::factory()->drop()->create([
        'driver_employee_id' => EmployeeMaster::factory()->create()->id,
        'vendor_courier_id' => null,
        'departed_at' => now(),
    ]);
    $job->fresh()->forceFill(['delivery_otp_verified_at' => now()])->save();

    expect($job->fresh()->status)->toBe(PickupDrop::STATUS_VEHICLE_DELIVERED);
});

it('does not offer a status field on the form', function () {
    $html = Livewire::test(Edit::class)->html();

    expect($html)->not->toContain('wire:model.live="status"');
});

it('groups the driver board by assigned, collected, and awaiting', function () {
    $driver = EmployeeMaster::factory()->create(['name' => 'BOARD DRIVER']);
    PickupDrop::factory()->create(['driver_employee_id' => $driver->id, 'vendor_courier_id' => null]);
    PickupDrop::factory()->create(['driver_employee_id' => $driver->id, 'vendor_courier_id' => null, 'collected_at' => now()]);

    $board = Livewire::test(Index::class)->instance()->driverBoard;
    $row = collect($board)->firstWhere('name', 'BOARD DRIVER');

    expect($row['assigned'])->toBe(2)
        ->and($row['collected'])->toBe(1)
        ->and($row['awaiting'])->toBe(1);
});
