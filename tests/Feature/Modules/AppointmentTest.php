<?php

use App\Modules\Appointment\Livewire\Edit;
use App\Modules\Appointment\Livewire\Index;
use App\Modules\Appointment\Models\Appointment;
use App\Modules\BookingChannelMaster\Models\BookingChannelMaster;
use App\Modules\CancelReasonMaster\Models\CancelReasonMaster;
use App\Modules\ComplaintTypeMaster\Models\ComplaintTypeMaster;
use App\Modules\CustomerMaster\Models\CustomerAddress;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\HolidayMaster\Models\HolidayMaster;
use App\Modules\PickupDrop\Models\PickupDrop;
use App\Modules\PickupDropOptionMaster\Models\PickupDropOptionMaster;
use App\Modules\TimeSlotMaster\Models\TimeSlotMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

/** A booking channel, created on demand so tests don't depend on the seeder. */
function channel(string $name = 'PHONE CALL'): BookingChannelMaster
{
    return BookingChannelMaster::firstOrCreate(['name' => $name], ['is_active' => true]);
}

/** A pickup/drop option; $pickup decides whether the workshop collects the vehicle. */
function pickupOption(bool $pickup = false, ?string $name = null): PickupDropOptionMaster
{
    $name ??= $pickup ? 'WORKSHOP PICKUP ONLY' : 'CUSTOMER SELF DROP';

    return PickupDropOptionMaster::firstOrCreate(
        ['name' => $name],
        ['involves_pickup' => $pickup, 'involves_drop' => false, 'is_active' => true],
    );
}

/** The minimum valid set of selections the Edit form needs to save. */
function fillValidAppointment(Testable $component): Testable
{
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);

    return $component
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', WorkshopDepartmentMaster::factory()->create()->id)
        ->set('assigned_advisor_id', EmployeeMaster::factory()->create()->id)
        ->set('booking_channel_id', channel()->id)
        ->set('pickup_drop_option_id', pickupOption()->id);
}

it('renders the index page', function () {
    Appointment::factory()->count(3)->create();

    $this->get(route('appointment.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('auto-stamps APT-00001 style appointment_no on create', function () {
    $a = Appointment::factory()->create();

    expect($a->fresh()->appointment_no)->toBe('APT-'.str_pad((string) $a->id, 5, '0', STR_PAD_LEFT));
});

it('filters by status', function () {
    Appointment::factory()->create(['status' => Appointment::STATUS_PENDING]);
    Appointment::factory()->confirmed()->create();
    Appointment::factory()->cancelled()->create();

    Livewire::test(Index::class)
        ->set('statusFilter', 'confirmed')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1 && $rows->first()->status === 'confirmed');
});

it('filters by channel and advisor', function () {
    $email = channel('EMAIL');
    $phone = channel('PHONE CALL');
    $a1 = EmployeeMaster::factory()->create(['name' => 'PRIYANKA']);
    $a2 = EmployeeMaster::factory()->create(['name' => 'VINOD']);
    Appointment::factory()->create(['booking_channel_id' => $email->id, 'assigned_advisor_id' => $a1->id]);
    Appointment::factory()->create(['booking_channel_id' => $phone->id, 'assigned_advisor_id' => $a2->id]);

    Livewire::test(Index::class)
        ->set('channelFilter', (string) $email->id)
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1)
        ->set('channelFilter', 'all')
        ->set('advisorFilter', (string) $a2->id)
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1 && $rows->first()->assigned_advisor_id === $a2->id);
});

it('searches by appointment_no, customer name, and reg no', function () {
    $customer = CustomerMaster::factory()->create(['first_name' => 'AAARDVARK']);
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id, 'registration_no' => 'GJ05XYZ7777']);
    Appointment::factory()->create([
        'customer_id' => $customer->id,
        'customer_vehicle_id' => $vehicle->id,
    ]);
    Appointment::factory()->count(2)->create();

    Livewire::test(Index::class)
        ->set('search', 'AAARDVARK')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1)
        ->set('search', 'GJ05XYZ7777')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1);
});

it('creates an appointment with capital typing on free-text fields', function () {
    fillValidAppointment(Livewire::test(Edit::class))
        ->set('notes', 'customer requested early delivery')
        ->call('save')
        ->assertHasNoErrors();

    $a = Appointment::first();
    expect($a)->not->toBeNull()
        ->and($a->notes)->toBe('CUSTOMER REQUESTED EARLY DELIVERY')
        ->and($a->status)->toBe(Appointment::STATUS_PENDING)
        ->and($a->appointment_no)->toStartWith('APT-');
});

it('requires a booking channel and a pickup/drop option', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', WorkshopDepartmentMaster::factory()->create()->id)
        ->set('assigned_advisor_id', EmployeeMaster::factory()->create()->id)
        ->call('save')
        ->assertHasErrors(['booking_channel_id', 'pickup_drop_option_id']);
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

it('clears customer_vehicle_id when the customer changes', function () {
    $customerA = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customerA->id]);
    $customerB = CustomerMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('customer_id', $customerA->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('customer_id', $customerB->id)
        ->assertSet('customer_vehicle_id', null);
});

it('requires a pickup address when the option means the workshop collects the vehicle', function () {
    fillValidAppointment(Livewire::test(Edit::class))
        ->set('pickup_drop_option_id', pickupOption(true)->id)
        ->set('pickup_address', null)
        ->call('save')
        ->assertHasErrors(['pickup_address']);
});

it('does not require a pickup address for a self-drop option', function () {
    fillValidAppointment(Livewire::test(Edit::class))
        ->set('pickup_drop_option_id', pickupOption(false)->id)
        ->call('save')
        ->assertHasNoErrors();
});

it('combines appointment_date + appointment_time into a single datetime on save', function () {
    fillValidAppointment(Livewire::test(Edit::class))
        ->set('appointment_date', '2026-08-15')
        ->set('appointment_time', '14:30')
        ->call('save')
        ->assertHasNoErrors();

    expect(Appointment::first()->appointment_at->format('Y-m-d H:i'))->toBe('2026-08-15 14:30');
});

it('selecting a saved customer address fills the pickup_address textarea', function () {
    $customer = CustomerMaster::factory()->create();
    $address = $customer->addresses()->create([
        'label' => 'Home',
        'address_line' => '12 Maple Street',
        'is_primary' => true,
    ]);

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('pickup_drop_option_id', pickupOption(true)->id)
        ->assertSet('pickup_address_choice', (string) $address->id)
        ->assertSet('pickup_address', '12 Maple Street');
});

it('switching to custom address choice keeps whatever text is in the textarea', function () {
    $customer = CustomerMaster::factory()->create();
    $customer->addresses()->create(['label' => 'Home', 'address_line' => '12 Maple Street', 'is_primary' => true]);

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('pickup_drop_option_id', pickupOption(true)->id)
        ->set('pickup_address', 'TYPED OVER ADDRESS')
        ->set('pickup_address_choice', 'custom')
        ->assertSet('pickup_address', 'TYPED OVER ADDRESS');
});

it('clears pickup fields when the option switches back to self-drop', function () {
    Livewire::test(Edit::class)
        ->set('pickup_drop_option_id', pickupOption(true)->id)
        ->set('pickup_address', 'SOME ADDRESS')
        ->set('pickup_contact_phone', '9999999999')
        ->set('pickup_drop_option_id', pickupOption(false)->id)
        ->assertSet('pickup_address', null)
        ->assertSet('pickup_contact_phone', null);
});

it('updates an existing appointment without changing appointment_no', function () {
    $a = Appointment::factory()->create();
    $original = $a->fresh()->appointment_no;

    Livewire::test(Edit::class, ['appointment' => $a])
        ->set('notes', 'updated note')
        ->set('status', Appointment::STATUS_CONFIRMED)
        ->call('save')
        ->assertHasNoErrors();

    expect($a->fresh()->notes)->toBe('UPDATED NOTE')
        ->and($a->fresh()->status)->toBe(Appointment::STATUS_CONFIRMED)
        ->and($a->fresh()->appointment_no)->toBe($original);
});

it('requires a cancel reason only when the status is cancelled', function () {
    fillValidAppointment(Livewire::test(Edit::class))
        ->set('status', Appointment::STATUS_CANCELLED)
        ->call('save')
        ->assertHasErrors(['cancel_reason_id']);

    fillValidAppointment(Livewire::test(Edit::class))
        ->set('status', Appointment::STATUS_CANCELLED)
        ->set('cancel_reason_id', CancelReasonMaster::factory()->create()->id)
        ->call('save')
        ->assertHasNoErrors();
});

it('saves customer complaints as line items and re-syncs on update', function () {
    $type = ComplaintTypeMaster::factory()->create();

    fillValidAppointment(Livewire::test(Edit::class))
        ->call('addComplaint')
        ->set('complaints.0.complaint_type_id', $type->id)
        ->set('complaints.0.description', 'suspension noise')
        ->call('addComplaint')
        ->set('complaints.1.description', 'ac not working')
        ->call('save')
        ->assertHasNoErrors();

    $a = Appointment::first();
    expect($a->complaints)->toHaveCount(2)
        ->and($a->complaints->first()->description)->toBe('SUSPENSION NOISE')
        ->and($a->complaints->first()->complaint_type_id)->toBe($type->id);

    // Reopening and removing a line deletes it rather than orphaning it.
    Livewire::test(Edit::class, ['appointment' => $a])
        ->call('removeComplaint', 1)
        ->call('save')
        ->assertHasNoErrors();

    expect($a->fresh()->complaints)->toHaveCount(1);
});

it('warns but still allows booking into a full time slot', function () {
    $slot = TimeSlotMaster::factory()->window('09:00', '10:00', 1)->create();
    $date = '2026-09-10';

    // Fill the slot's single seat.
    Appointment::factory()->create([
        'time_slot_id' => $slot->id,
        'appointment_at' => $date.' 09:00:00',
    ]);

    $component = fillValidAppointment(Livewire::test(Edit::class))
        ->set('appointment_date', $date)
        ->set('time_slot_id', $slot->id);

    expect($component->instance()->schedulingWarnings)
        ->toHaveCount(1)
        ->and($component->instance()->schedulingWarnings[0])->toContain('capacity');

    // Soft warning: the save still goes through.
    $component->call('save')->assertHasNoErrors();

    expect(Appointment::where('time_slot_id', $slot->id)->count())->toBe(2);
});

it('warns when the chosen date is a holiday', function () {
    HolidayMaster::factory()->create([
        'name' => 'REPUBLIC DAY',
        'holiday_date' => '2027-01-26',
        'is_recurring' => false,
        'is_active' => true,
    ]);

    $component = Livewire::test(Edit::class)->set('appointment_date', '2027-01-26');

    expect($component->instance()->schedulingWarnings)->toHaveCount(1)
        ->and($component->instance()->schedulingWarnings[0])->toContain('REPUBLIC DAY');
});

it('derives the driver stage from the linked pickup/drop job', function () {
    $a = Appointment::factory()->create(['status' => Appointment::STATUS_CONFIRMED]);

    expect($a->effectiveStatusLabel())->toBe('Confirmed');

    $job = PickupDrop::factory()->create([
        'appointment_id' => $a->id,
        'status' => PickupDrop::STATUS_DRIVER_ON_THE_WAY,
    ]);

    expect($a->fresh()->effectiveStatusLabel())->toBe('Driver on the Way');

    $job->update(['status' => PickupDrop::STATUS_VEHICLE_COLLECTED]);
    expect($a->fresh()->effectiveStatusLabel())->toBe('Vehicle Collected');

    // A terminal appointment state always wins over the driver stage.
    $a->update(['status' => Appointment::STATUS_CANCELLED]);
    expect($a->fresh()->effectiveStatusLabel())->toBe('Cancelled');
});

it('stamps rescheduled_from_at when the appointment is moved', function () {
    $a = Appointment::factory()->create(['appointment_at' => '2026-08-01 10:00:00']);

    Livewire::test(Edit::class, ['appointment' => $a])
        ->set('appointment_date', '2026-08-05')
        ->set('appointment_time', '11:00')
        ->call('save')
        ->assertHasNoErrors();

    expect($a->fresh()->rescheduled_from_at?->format('Y-m-d H:i'))->toBe('2026-08-01 10:00');
});

it('resolves the pickup address live from a linked saved address (edits propagate)', function () {
    $customer = CustomerMaster::factory()->create();
    $addr = CustomerAddress::factory()->create(['customer_id' => $customer->id, 'address_line' => '12 MG ROAD', 'region_id' => null]);
    $appt = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'pickup_address_id' => $addr->id,
        'pickup_address' => null,
    ]);

    expect($appt->resolvedPickupAddress())->toContain('12 MG ROAD');

    $addr->update(['address_line' => '99 NEW ROAD']);           // editing the saved address…
    expect($appt->fresh()->resolvedPickupAddress())->toContain('99 NEW ROAD'); // …propagates live
});

it('resolves the contact phone live from the customer when not overridden', function () {
    $customer = CustomerMaster::factory()->create(['phone' => '9811122233']);
    $appt = Appointment::factory()->create(['customer_id' => $customer->id, 'pickup_contact_phone' => null]);

    expect($appt->resolvedContactPhone())->toBe('9811122233');

    $customer->update(['phone' => '9800000000']);
    expect($appt->fresh()->resolvedContactPhone())->toBe('9800000000');
});

it('forward-syncs identity to a not-yet-collected linked pickup/drop when the appointment changes', function () {
    $appt = Appointment::factory()->create();
    $pickup = PickupDrop::factory()->create(['appointment_id' => $appt->id, 'status' => PickupDrop::STATUS_PENDING]);
    $newVehicle = CustomerVehicleMaster::factory()->create();

    $appt->update(['customer_vehicle_id' => $newVehicle->id]);

    expect($pickup->fresh()->customer_vehicle_id)->toBe($newVehicle->id);
});

it('does not forward-sync to a pickup/drop that is already collected', function () {
    $original = CustomerVehicleMaster::factory()->create();
    $appt = Appointment::factory()->create();
    $collected = PickupDrop::factory()->create([
        'appointment_id' => $appt->id,
        'status' => PickupDrop::STATUS_VEHICLE_COLLECTED,
        'customer_vehicle_id' => $original->id,
    ]);

    $appt->update(['customer_vehicle_id' => CustomerVehicleMaster::factory()->create()->id]);

    expect($collected->fresh()->customer_vehicle_id)->toBe($original->id);
});
