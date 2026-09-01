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
use App\Modules\DistanceSlabMaster\Models\DistanceSlabMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\GateInOut\Models\GateInOut;
use App\Modules\HolidayMaster\Models\HolidayMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\JobDescriptionMaster\Models\JobDescriptionMaster;
use App\Modules\PendingReasonMaster\Models\PendingReasonMaster;
use App\Modules\PickupDrop\Models\PickupDrop;
use App\Modules\PickupDropOptionMaster\Models\PickupDropOptionMaster;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\RegionMaster\Models\RegionMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
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
        ->set('department_ids', [(string) WorkshopDepartmentMaster::factory()->create()->id])
        ->set('assigned_advisor_id', EmployeeMaster::factory()->create()->id)
        ->set('booking_channel_id', channel()->id)
        ->set('priority_id', PriorityMaster::firstOrCreate(['name' => 'NORMAL'], ['is_active' => true])->id)
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
    // Status derives from facts, so each row is built from the fact that produces it.
    Appointment::factory()->create([
        'appointment_at' => now()->addDay(),
        'pending_reason_id' => PendingReasonMaster::factory()->create()->id,
    ]);
    Appointment::factory()->create(['appointment_at' => now()->addDay()]);
    Appointment::factory()->create(['appointment_at' => now()->addDay(), 'cancelled_at' => now()]);

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
        ->set('statusFilter', 'all')
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
        ->set('statusFilter', 'all')
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
        ->and($a->status)->toBe(Appointment::STATUS_CONFIRMED)
        ->and($a->appointment_no)->toStartWith('APT-');
});

it('requires a booking channel and a pickup/drop option', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('department_ids', [(string) WorkshopDepartmentMaster::factory()->create()->id])
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
        ->set('priority_id', PriorityMaster::firstOrCreate(['name' => 'NORMAL'], ['is_active' => true])->id)
        ->set('notes', 'updated note')
        ->call('save')
        ->assertHasNoErrors();

    expect($a->fresh()->notes)->toBe('UPDATED NOTE')
        ->and($a->fresh()->appointment_no)->toBe($original);
});

it('requires a reason to cancel, and derives the cancelled status from it', function () {
    $a = Appointment::factory()->create();

    Livewire::test(Edit::class, ['appointment' => $a])
        ->call('cancelAppointment')
        ->assertHasErrors(['cancel_reason_id']);

    expect($a->fresh()->status)->not->toBe(Appointment::STATUS_CANCELLED);

    Livewire::test(Edit::class, ['appointment' => $a])
        ->set('cancel_reason_id', CancelReasonMaster::factory()->create()->id)
        ->call('cancelAppointment')
        ->assertHasNoErrors();

    expect($a->fresh()->status)->toBe(Appointment::STATUS_CANCELLED)
        ->and($a->fresh()->cancelled_at)->not->toBeNull();
});

it('restores a cancelled booking back to a derived status', function () {
    $a = Appointment::factory()->create([
        'cancelled_at' => now(),
        'cancel_reason_id' => CancelReasonMaster::factory()->create()->id,
    ]);
    $a->save();

    expect($a->fresh()->status)->toBe(Appointment::STATUS_CANCELLED);

    Livewire::test(Edit::class, ['appointment' => $a])->call('restoreAppointment');

    expect($a->fresh()->cancelled_at)->toBeNull()
        ->and($a->fresh()->status)->not->toBe(Appointment::STATUS_CANCELLED);
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

it('refines an unstarted booking with the live driver stage', function () {
    $a = Appointment::factory()->create(['appointment_at' => now()->addDay()]);

    expect($a->fresh()->effectiveStatusLabel())->toBe('Confirmed');

    $job = PickupDrop::factory()->create([
        'appointment_id' => $a->id,
        'departed_at' => now(),
    ]);

    expect($a->fresh()->effectiveStatusLabel())->toBe('Driver on the Way');

    // Once the driver has the car the derived ladder takes over, so the job's
    // later stages no longer talk over the appointment's own status.
    $job->fresh()->update(['collected_at' => now()]);
    expect($a->fresh()->status)->toBe(Appointment::STATUS_VEHICLE_COLLECTED)
        ->and($a->fresh()->effectiveStatusLabel())->toBe('Vehicle Collected');

    // A cancellation always wins over the driver stage.
    $a->forceFill(['cancelled_at' => now()])->save();
    expect($a->fresh()->effectiveStatusLabel())->toBe('Cancelled');
});

it('stamps rescheduled_from_at when the appointment is moved', function () {
    $a = Appointment::factory()->create(['appointment_at' => '2026-08-01 10:00:00']);

    Livewire::test(Edit::class, ['appointment' => $a])
        ->set('priority_id', PriorityMaster::firstOrCreate(['name' => 'NORMAL'], ['is_active' => true])->id)
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
        'collected_at' => now(),
        'customer_vehicle_id' => $original->id,
    ]);

    $appt->update(['customer_vehicle_id' => CustomerVehicleMaster::factory()->create()->id]);

    expect($collected->fresh()->customer_vehicle_id)->toBe($original->id);
});

it('finds a customer beyond the first page via server-side search', function () {
    // A customer whose name sorts last and sits far past any fixed slice.
    CustomerMaster::factory()->count(40)->create(['is_active' => true, 'first_name' => 'Aaron']);
    $target = CustomerMaster::factory()->create([
        'is_active' => true,
        'first_name' => 'Zoravar',
        'last_name' => 'Singh',
        'phone' => '9876500042',
    ]);

    $component = Livewire::test(Edit::class);

    // Default list is a small server-side slice ordered by first_name — the
    // target (a 'Z' name) is not in it.
    expect(collect($component->get('customers'))->pluck('id'))->not->toContain($target->id);

    // Searching by phone reaches it.
    $component->set('customerSearch', '9876500042');
    expect(collect($component->get('customers'))->pluck('id'))->toContain($target->id);

    // Searching by name reaches it too.
    $component->set('customerSearch', 'Zoravar');
    expect(collect($component->get('customers'))->pluck('id'))->toContain($target->id);
});

/*
|--------------------------------------------------------------------------
| Address regions + trip distance
|--------------------------------------------------------------------------
*/

/** A slot for tests whose option makes both legs mandatory. */
function bothLegsSlot(): TimeSlotMaster
{
    return TimeSlotMaster::firstOrCreate(
        ['name' => '09:00-10:00'],
        ['slot_start_time' => '09:00:00', 'slot_end_time' => '10:00:00', 'is_active' => true],
    );
}

/** An option that involves both legs, so pickup and drop fields are both live. */
function bothLegsOption(): PickupDropOptionMaster
{
    return PickupDropOptionMaster::firstOrCreate(
        ['name' => 'PICKUP AND DROP BOTH'],
        ['involves_pickup' => true, 'involves_drop' => true, 'is_active' => true],
    );
}

function state(string $name): RegionMaster
{
    return RegionMaster::firstOrCreate(['kind' => 'state', 'name' => $name], ['is_active' => true]);
}

it('creates a city inline under the chosen state and selects it', function () {
    $gujarat = state('GUJARAT');

    $component = fillValidAppointment(Livewire::test(Edit::class))
        ->set('pickup_drop_option_id', bothLegsOption()->id)
        ->set('drop_state_id', $gujarat->id)
        ->set('dropCitySearch', 'gandhinagar')
        ->call('createDropCity');

    $city = RegionMaster::find($component->get('drop_city_id'));

    expect($city->name)->toBe('GANDHINAGAR')
        ->and($city->kind)->toBe('city')
        ->and($city->parent_id)->toBe($gujarat->id);

    // Picking a city is itself a usable region — the area only refines it.
    expect($component->get('drop_region_id'))->toBe($city->id);
});

it('scopes an inline city to its state instead of reusing a same-named one', function () {
    $gujarat = state('GUJARAT');
    $maharashtra = state('MAHARASHTRA');

    $make = fn (int $stateId) => fillValidAppointment(Livewire::test(Edit::class))
        ->set('pickup_drop_option_id', bothLegsOption()->id)
        ->set('drop_state_id', $stateId)
        ->set('dropCitySearch', 'shirpur')
        ->call('createDropCity')
        ->get('drop_city_id');

    $first = $make($gujarat->id);
    $second = $make($maharashtra->id);

    expect($first)->not->toBe($second)
        ->and(RegionMaster::where('kind', 'city')->where('name', 'SHIRPUR')->count())->toBe(2);
});

it('creates an area under the chosen city on the pickup leg', function () {
    $component = fillValidAppointment(Livewire::test(Edit::class))
        ->set('pickup_drop_option_id', bothLegsOption()->id)
        ->set('pickup_state_id', state('GUJARAT')->id)
        ->set('pickupCitySearch', 'surat')
        ->call('createPickupCity')
        ->set('pickupAreaSearch', 'vesu')
        ->call('createPickupArea');

    $area = RegionMaster::find($component->get('pickup_region_id'));

    expect($area->name)->toBe('VESU')
        ->and($area->kind)->toBe('area')
        ->and($area->parent->name)->toBe('SURAT');
});

it('refuses to create a city with no state chosen', function () {
    $component = fillValidAppointment(Livewire::test(Edit::class))
        ->set('pickup_drop_option_id', bothLegsOption()->id)
        ->set('dropCitySearch', 'nowhere')
        ->call('createDropCity');

    expect($component->get('drop_city_id'))->toBeNull()
        ->and(RegionMaster::where('name', 'NOWHERE')->exists())->toBeFalse();
});

it('persists the region on both legs', function () {
    $area = RegionMaster::factory()->create(['kind' => 'area', 'name' => 'BODAKDEV', 'is_active' => true]);

    fillValidAppointment(Livewire::test(Edit::class))
        ->set('pickup_drop_option_id', bothLegsOption()->id)
        ->set('time_slot_id', bothLegsSlot()->id)
        ->set('drop_time_slot_id', bothLegsSlot()->id)
        ->set('pickup_address_choice', 'custom')
        ->set('pickup_address', '12 SOME LANE')
        ->set('pickup_region_id', $area->id)
        ->set('drop_address_choice', 'custom')
        ->set('drop_address', '9 OTHER ROAD')
        ->set('drop_region_id', $area->id)
        ->call('save')
        ->assertHasNoErrors();

    $appointment = Appointment::latest('id')->first();

    expect($appointment->pickup_region_id)->toBe($area->id)
        ->and($appointment->drop_region_id)->toBe($area->id);
});

it('resolves the distance slab and quotes its charge', function () {
    $slab = DistanceSlabMaster::firstOrCreate(
        ['name' => '6-15 KM'],
        ['code' => 'T2', 'min_km' => 6, 'max_km' => 15, 'charge_amount' => 300, 'is_active' => true],
    );

    $component = fillValidAppointment(Livewire::test(Edit::class))
        ->set('pickup_drop_option_id', bothLegsOption()->id)
        ->set('distance_km', '12');

    expect($component->get('distance_slab_id'))->toBe($slab->id)
        ->and((float) $component->get('distance_charge'))->toBe(300.0);
});

it('clears the distance when neither leg is driven', function () {
    $component = fillValidAppointment(Livewire::test(Edit::class))
        ->set('pickup_drop_option_id', bothLegsOption()->id)
        ->set('distance_km', '12')
        ->set('pickup_drop_option_id', pickupOption()->id);

    expect($component->get('distance_km'))->toBeNull()
        ->and($component->get('distance_slab_id'))->toBeNull()
        ->and($component->get('distance_charge'))->toBeNull();
});

it('persists the distance and its charge', function () {
    DistanceSlabMaster::firstOrCreate(
        ['name' => '0-5 KM'],
        ['code' => 'T1', 'min_km' => 0, 'max_km' => 5, 'charge_amount' => 200, 'is_active' => true],
    );

    fillValidAppointment(Livewire::test(Edit::class))
        ->set('pickup_drop_option_id', bothLegsOption()->id)
        ->set('time_slot_id', bothLegsSlot()->id)
        ->set('drop_time_slot_id', bothLegsSlot()->id)
        ->set('pickup_address_choice', 'custom')
        ->set('pickup_address', '12 SOME LANE')
        ->set('drop_address_choice', 'custom')
        ->set('drop_address', '9 OTHER ROAD')
        ->set('distance_km', '4')
        ->call('save')
        ->assertHasNoErrors();

    $appointment = Appointment::latest('id')->first();

    expect((float) $appointment->distance_km)->toBe(4.0)
        ->and((float) $appointment->distance_charge)->toBe(200.0);
});

it('no longer shows the entry date and time field', function () {
    $html = Livewire::test(Edit::class)->html();

    expect($html)->not->toContain('Entry Date');
});

/*
|--------------------------------------------------------------------------
| Derived status
|--------------------------------------------------------------------------
*/

it('does not offer a status field on the form', function () {
    $html = Livewire::test(Edit::class)->html();

    expect($html)->not->toContain('wire:model="status"');
});

it('completes the booking when a job card is raised against it', function () {
    $appointment = Appointment::factory()->create(['appointment_at' => now()->addDay()]);

    expect($appointment->fresh()->status)->toBe(Appointment::STATUS_CONFIRMED);

    JobCard::factory()->create([
        'appointment_id' => $appointment->id,
        'customer_vehicle_id' => $appointment->customer_vehicle_id,
    ]);

    expect($appointment->fresh()->status)->toBe(Appointment::STATUS_COMPLETED);
});

it('marks the booking arrived when an inward is raised for the vehicle', function () {
    $appointment = Appointment::factory()->create(['appointment_at' => now()->addDay()]);

    GateInOut::factory()->create([
        'customer_vehicle_id' => $appointment->customer_vehicle_id,
        'entered_at' => now(),
    ]);

    expect($appointment->fresh()->status)->toBe(Appointment::STATUS_ARRIVED);
});

it('treats a pending reason as what makes a booking pending', function () {
    $appointment = Appointment::factory()->create(['appointment_at' => now()->addDay()]);

    expect($appointment->fresh()->status)->toBe(Appointment::STATUS_CONFIRMED);

    $appointment->update(['pending_reason_id' => PendingReasonMaster::factory()->create()->id]);
    expect($appointment->fresh()->status)->toBe(Appointment::STATUS_PENDING);

    $appointment->update(['pending_reason_id' => null]);
    expect($appointment->fresh()->status)->toBe(Appointment::STATUS_CONFIRMED);
});

it('marks a passed booking as a no-show when nothing happened', function () {
    $appointment = Appointment::factory()->create(['appointment_at' => now()->subDay()]);

    expect($appointment->fresh()->status)->toBe(Appointment::STATUS_NO_SHOW);
});

/*
|--------------------------------------------------------------------------
| Department drives service type
|--------------------------------------------------------------------------
*/

it('offers no service type until a department is chosen', function () {
    $component = Livewire::test(Edit::class);

    expect($component->instance()->serviceTypes)->toHaveCount(0);
});

it('offers only the chosen department\'s service types', function () {
    $mechanical = WorkshopDepartmentMaster::factory()->create();
    $bodyshop = WorkshopDepartmentMaster::factory()->create();

    $mine = ServiceTypeMaster::factory()->create(['workshop_department_id' => $mechanical->id, 'is_active' => true]);
    ServiceTypeMaster::factory()->create(['workshop_department_id' => $bodyshop->id, 'is_active' => true]);

    $offered = Livewire::test(Edit::class)
        ->set('department_ids', [(string) $mechanical->id])
        ->instance()->serviceTypes;

    expect($offered->pluck('id')->all())->toBe([$mine->id]);
});

it('clears a service type belonging to the previous department', function () {
    $mechanical = WorkshopDepartmentMaster::factory()->create();
    $bodyshop = WorkshopDepartmentMaster::factory()->create();
    $mine = ServiceTypeMaster::factory()->create(['workshop_department_id' => $mechanical->id, 'is_active' => true]);

    $component = Livewire::test(Edit::class)
        ->set('department_ids', [(string) $mechanical->id])
        ->set('service_type_id', $mine->id)
        ->set('department_ids', [(string) $bodyshop->id]);

    expect($component->get('service_type_id'))->toBeNull();
});

it('rejects a service type from another department on save', function () {
    $mechanical = WorkshopDepartmentMaster::factory()->create();
    $bodyshop = WorkshopDepartmentMaster::factory()->create();
    $theirs = ServiceTypeMaster::factory()->create(['workshop_department_id' => $bodyshop->id, 'is_active' => true]);

    fillValidAppointment(Livewire::test(Edit::class))
        ->set('department_ids', [(string) $mechanical->id])
        ->set('service_type_id', $theirs->id)
        ->call('save')
        ->assertHasErrors(['service_type_id']);
});

it('drops a legacy mismatched pairing when the form opens', function () {
    $mechanical = WorkshopDepartmentMaster::factory()->create();
    $bodyshop = WorkshopDepartmentMaster::factory()->create();
    $theirs = ServiceTypeMaster::factory()->create(['workshop_department_id' => $bodyshop->id, 'is_active' => true]);

    // A row from before service types were scoped to a department.
    $appointment = Appointment::factory()->create([
        'workshop_department_id' => $mechanical->id,
        'service_type_id' => $theirs->id,
    ]);

    $component = Livewire::test(Edit::class, ['appointment' => $appointment]);

    expect($component->get('service_type_id'))->toBeNull()
        ->and($component->get('workshop_department_id'))->toBe($mechanical->id);
});

it('books a visit spanning multiple departments and reloads the selection', function () {
    $svc = WorkshopDepartmentMaster::factory()->create();
    $tyre = WorkshopDepartmentMaster::factory()->create();
    ServiceTypeMaster::factory()->create(['workshop_department_id' => $svc->id]);

    fillValidAppointment(Livewire::test(Edit::class))
        ->set('department_ids', [(string) $svc->id, (string) $tyre->id])
        ->call('save')
        ->assertHasNoErrors();

    $appointment = Appointment::latest('id')->first();

    expect($appointment->workshop_department_id)->toBe($svc->id) // primary = first picked
        ->and($appointment->workshopDepartments->pluck('id')->sort()->values()->all())
        ->toBe(collect([$svc->id, $tyre->id])->sort()->values()->all());

    $reopened = Livewire::test(Edit::class, ['appointment' => $appointment]);

    expect(collect($reopened->get('department_ids'))->map(fn ($id) => (int) $id)->sort()->values()->all())
        ->toBe(collect([$svc->id, $tyre->id])->sort()->values()->all());
});

it('offers the checklist grouped per department', function () {
    $a = WorkshopDepartmentMaster::factory()->create(['name' => 'AAA DEPT']);
    $b = WorkshopDepartmentMaster::factory()->create(['name' => 'BBB DEPT']);
    foreach ([$a, $b] as $dept) {
        $st = ServiceTypeMaster::factory()->create(['workshop_department_id' => $dept->id]);
        JobDescriptionMaster::factory()->create(['service_type_id' => $st->id, 'category' => 'frequent', 'is_active' => true]);
    }

    $boxes = Livewire::test(Edit::class)
        ->set('department_ids', [(string) $a->id, (string) $b->id])
        ->instance()->frequentServiceGroupsByDepartment;

    expect($boxes->keys()->all())->toBe(['AAA DEPT', 'BBB DEPT']);
});

it('shows the entry date and time, distinct from the appointment date, and sorts on it', function () {
    // Booked yesterday for a slot next week — the two dates must not be confusable.
    $row = Appointment::factory()->create(['appointment_at' => now()->addWeek()]);
    $row->forceFill(['created_at' => now()->subDay()])->saveQuietly();

    Livewire::test(Index::class)
        ->assertSee($row->fresh()->created_at->format('d/m/Y'))
        ->assertSee($row->fresh()->created_at->format('h:i A'))
        ->call('sort', 'created_at')
        ->assertSet('sortBy', 'created_at');
});

it('deletes through the one shared confirm modal rather than a modal per row', function () {
    $row = Appointment::factory()->create(['appointment_at' => now()->addDay()]);

    Livewire::test(Index::class)
        ->call('confirmDelete', $row->id)
        ->assertSet('deletingId', $row->id)
        ->assertSee($row->appointment_no)
        ->call('delete')
        ->assertSet('deletingId', null);

    expect(Appointment::find($row->id))->toBeNull();
});

it('leads the identity cell with the registration and drops the brand name', function () {
    $customer = CustomerMaster::factory()->create(['first_name' => 'RASHMI', 'phone' => '9876543210']);
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $brand = $vehicle->model->brand;

    $row = Appointment::factory()->create([
        'appointment_at' => now()->addDay(),
        'customer_id' => $customer->id,
        'customer_vehicle_id' => $vehicle->id,
    ]);

    $html = Livewire::test(Index::class)
        ->assertSee($vehicle->registration_no)
        ->assertSee($vehicle->model->name)
        ->assertSee('9876543210')
        ->assertSee('RASHMI')
        // The number is the way into the booking.
        ->assertSee(route('appointment.edit', $row), escape: false)
        ->html();

    // Brand only ate width; the model alone identifies the car.
    expect($html)->not->toContain($brand->name);
});
