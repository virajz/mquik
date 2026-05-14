<?php

use App\Models\User;
use App\Modules\Appointment\Livewire\Edit;
use App\Modules\Appointment\Livewire\Index;
use App\Modules\Appointment\Models\Appointment;
use App\Modules\BusinessTypeMaster\Models\BusinessTypeMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use App\Support\Menu;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

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
    $a1 = EmployeeMaster::factory()->create(['name' => 'PRIYANKA']);
    $a2 = EmployeeMaster::factory()->create(['name' => 'VINOD']);
    Appointment::factory()->create(['channel' => 'email', 'assigned_advisor_id' => $a1->id]);
    Appointment::factory()->create(['channel' => 'phone_call', 'assigned_advisor_id' => $a2->id]);

    Livewire::test(Index::class)
        ->set('channelFilter', 'email')
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
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $dept = WorkshopDepartmentMaster::factory()->create();
    $advisor = EmployeeMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->set('notes', 'customer requested early delivery')
        ->call('save')
        ->assertHasNoErrors();

    $a = Appointment::first();
    expect($a)->not->toBeNull()
        ->and($a->notes)->toBe('CUSTOMER REQUESTED EARLY DELIVERY')
        ->and($a->status)->toBe(Appointment::STATUS_PENDING)
        ->and($a->appointment_no)->toStartWith('APT-');
});

it('rejects a customer_vehicle that does not belong to the chosen customer', function () {
    $customerA = CustomerMaster::factory()->create();
    $customerB = CustomerMaster::factory()->create();
    $vehicleOfB = CustomerVehicleMaster::factory()->create(['customer_id' => $customerB->id]);
    $dept = WorkshopDepartmentMaster::factory()->create();
    $advisor = EmployeeMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('customer_id', $customerA->id)
        ->set('customer_vehicle_id', $vehicleOfB->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->call('save')
        ->assertHasErrors(['customer_vehicle_id']);
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

it('requires pickup address when requires_pickup is on', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $dept = WorkshopDepartmentMaster::factory()->create();
    $advisor = EmployeeMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->set('requires_pickup', true)
        ->call('save')
        ->assertHasErrors(['pickup_address']);
});

it('clears pickup fields when requires_pickup is toggled off', function () {
    Livewire::test(Edit::class)
        ->set('requires_pickup', true)
        ->set('pickup_address', 'SOME ADDRESS')
        ->set('pickup_contact_phone', '9999999999')
        ->set('requires_pickup', false)
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

it('quick-adds a customer via the trait and selects them', function () {
    $bt = BusinessTypeMaster::firstOrCreate(['name' => 'WALKING'], ['is_active' => true]);

    Livewire::test(Edit::class)
        ->set('quickCustomer.first_name', 'NEW WALKIN')
        ->set('quickCustomer.phone', '9876543210')
        ->set('quickCustomer.business_type_id', $bt->id)
        ->call('createQuickCustomer')
        ->assertHasNoErrors();

    $created = CustomerMaster::where('first_name', 'NEW WALKIN')->first();
    expect($created)->not->toBeNull();
});

it('Edit::save blocks a user without create permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('appointment.view');
    $this->actingAs($user);

    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $dept = WorkshopDepartmentMaster::factory()->create();
    $advisor = EmployeeMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->call('save')
        ->assertStatus(403);

    expect(Appointment::count())->toBe(0);
});

it('Index::delete blocks a user without delete permission', function () {
    $a = Appointment::factory()->create();

    $user = User::factory()->create();
    $user->givePermissionTo('appointment.view');
    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('delete', $a->id)
        ->assertStatus(403);

    expect(Appointment::find($a->id))->not->toBeNull();
});

it('deletes an appointment from the index', function () {
    $a = Appointment::factory()->create();

    Livewire::test(Index::class)
        ->call('delete', $a->id);

    expect(Appointment::find($a->id))->toBeNull();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('appointment.index'))->assertRedirect(route('login'));
});

it('sidebar mode toggle now appears because Appointment is the first operations item', function () {
    $menu = app(Menu::class);

    expect($menu->availableModes()->all())->toEqualCanonicalizing(['operations', 'setup']);
});
