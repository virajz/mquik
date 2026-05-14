<?php

use App\Models\User;
use App\Modules\Appointment\Models\Appointment;
use App\Modules\CourierCompanyMaster\Models\CourierCompanyMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\PickupDrop\Livewire\Edit;
use App\Modules\PickupDrop\Livewire\Index;
use App\Modules\PickupDrop\Models\PickupDrop;
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
        ->set('statusFilter', 'delivered')
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
        ->set('address', '12 maple st')
        ->set('driver_employee_id', $driver->id)
        ->call('save')
        ->assertHasNoErrors();

    $row = PickupDrop::first();
    expect($row->address)->toBe('12 MAPLE ST')
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
        ->set('address', 'somewhere')
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
        ->set('address', 'somewhere')
        ->set('driver_employee_id', $driver->id)
        ->set('vendor_courier_id', $courier->id)
        ->call('save')
        ->assertHasErrors(['vendor_courier_id']);

    expect(PickupDrop::count())->toBe(0);
});

it('rejects vehicle that does not belong to the chosen customer', function () {
    $customerA = CustomerMaster::factory()->create();
    $customerB = CustomerMaster::factory()->create();
    $vehicleOfB = CustomerVehicleMaster::factory()->create(['customer_id' => $customerB->id]);
    $driver = EmployeeMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('customer_id', $customerA->id)
        ->set('customer_vehicle_id', $vehicleOfB->id)
        ->set('address', 'wherever')
        ->set('driver_employee_id', $driver->id)
        ->call('save')
        ->assertHasErrors(['customer_vehicle_id']);
});

it('prefills from an appointment when from-appointment query param is set', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'customer_vehicle_id' => $vehicle->id,
        'requires_pickup' => true,
        'pickup_address' => '99 OAK STREET',
        'pickup_contact_phone' => '9999999999',
    ]);

    Livewire::test(Edit::class, ['fromAppointment' => $appointment->id])
        ->assertSet('appointment_id', $appointment->id)
        ->assertSet('customer_id', $customer->id)
        ->assertSet('customer_vehicle_id', $vehicle->id)
        ->assertSet('address', '99 OAK STREET')
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
        ->set('address', 'somewhere')
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
        ->set('address', 'somewhere')
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
