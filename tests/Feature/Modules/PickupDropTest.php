<?php

use App\Models\User;
use App\Modules\Appointment\Models\Appointment;
use App\Modules\CancelReasonMaster\Models\CancelReasonMaster;
use App\Modules\ChecklistTemplateMaster\Models\ChecklistTemplateMaster;
use App\Modules\CourierCompanyMaster\Models\CourierCompanyMaster;
use App\Modules\CustomerMaster\Models\CustomerAddress;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DistanceSlabMaster\Models\DistanceSlabMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PhotoTypeMaster\Models\PhotoTypeMaster;
use App\Modules\PickupDrop\Livewire\Edit;
use App\Modules\PickupDrop\Livewire\Index;
use App\Modules\PickupDrop\Models\PickupDrop;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

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
    PickupDrop::factory()->delivered()->create();
    PickupDrop::factory()->drop()->create();
    PickupDrop::factory()->create(['driver_employee_id' => $driver->id]);

    Livewire::test(Index::class)
        ->set('statusFilter', PickupDrop::STATUS_VEHICLE_DELIVERED)
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1)
        ->set('statusFilter', 'all')
        ->set('directionFilter', 'drop')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1)
        ->set('directionFilter', 'all')
        ->set('driverFilter', (string) $driver->id)
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1);
});

it('creates a pickup with driver assignment', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $driver = EmployeeMaster::factory()->create();

    Livewire::test(Edit::class)
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

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('pickup_address', 'somewhere')
        ->call('save')
        ->assertHasErrors(['driver_employee_id']);

    expect(PickupDrop::count())->toBe(0);
});

it('rejects create when both driver AND vendor are assigned', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $driver = EmployeeMaster::factory()->create();
    $courier = CourierCompanyMaster::factory()->create();

    Livewire::test(Edit::class)
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

    Livewire::test(Edit::class)
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

    Livewire::test(Edit::class)
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
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('pickup_address', 'somewhere')
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
        ->call('addComplaint')
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

    Livewire::test(Edit::class, ['pickupDrop' => $row])
        ->call('removeComplaint', 0)
        ->call('save')
        ->assertHasNoErrors();

    expect($row->fresh()->complaints)->toHaveCount(0);
});

it('requires a cancel reason only when the job is cancelled', function () {
    validPickupDrop(Livewire::test(Edit::class))
        ->set('status', PickupDrop::STATUS_CANCELLED)
        ->call('save')
        ->assertHasErrors(['cancel_reason_id']);

    validPickupDrop(Livewire::test(Edit::class))
        ->set('status', PickupDrop::STATUS_CANCELLED)
        ->set('cancel_reason_id', CancelReasonMaster::factory()->create()->id)
        ->call('save')
        ->assertHasNoErrors();
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

    Livewire::test(Edit::class, ['pickupDrop' => $row])
        ->set('scheduled_date', '2026-09-03')
        ->set('scheduled_time', '11:00')
        ->call('save')
        ->assertHasNoErrors();

    expect($row->fresh()->rescheduled_from_at?->format('Y-m-d H:i'))->toBe('2026-09-01 09:00');
});

it('surfaces the driver stage on the linked appointment', function () {
    $appointment = Appointment::factory()->create(['status' => Appointment::STATUS_CONFIRMED]);
    $job = PickupDrop::factory()->create([
        'appointment_id' => $appointment->id,
        'status' => PickupDrop::STATUS_DRIVER_ASSIGNED,
    ]);

    expect($appointment->fresh()->effectiveStatusLabel())->toBe('Driver Assigned');

    // The job's own non-driver states must not override the appointment.
    $job->update(['status' => PickupDrop::STATUS_COMPLETED]);
    expect($appointment->fresh()->effectiveStatusLabel())->toBe('Confirmed');
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

    Livewire::test(Edit::class, ['fromJobCard' => $jobCard->id])
        ->set('pickup_address', 'somewhere')
        ->set('driver_employee_id', EmployeeMaster::factory()->create()->id)
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
