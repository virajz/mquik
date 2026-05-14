<?php

use App\Models\User;
use App\Modules\Appointment\Models\Appointment;
use App\Modules\ComplaintTypeMaster\Models\ComplaintTypeMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobCard\Livewire\Edit;
use App\Modules\JobCard\Livewire\Index;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\JobCard\Models\JobCardComplaint;
use App\Modules\JobCard\Models\JobCardInventoryItem;
use App\Modules\VehicleInventoryItemMaster\Models\VehicleInventoryItemMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    JobCard::factory()->count(3)->create();

    $this->get(route('job-card.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('auto-stamps JC-00001 style job_card_no on create', function () {
    $jc = JobCard::factory()->create();

    expect($jc->fresh()->job_card_no)->toBe('JC-'.str_pad((string) $jc->id, 5, '0', STR_PAD_LEFT));
});

it('filters by status, advisor, and dept', function () {
    $advisor = EmployeeMaster::factory()->create();
    $dept = WorkshopDepartmentMaster::factory()->create();
    JobCard::factory()->create();
    JobCard::factory()->inProgress()->create();
    JobCard::factory()->create(['assigned_advisor_id' => $advisor->id]);
    JobCard::factory()->create(['workshop_department_id' => $dept->id]);

    Livewire::test(Index::class)
        ->set('statusFilter', 'in_progress')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1)
        ->set('statusFilter', 'all')
        ->set('advisorFilter', (string) $advisor->id)
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1)
        ->set('advisorFilter', 'all')
        ->set('deptFilter', (string) $dept->id)
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1);
});

it('creates a job card with capital typing and timestamps', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $dept = WorkshopDepartmentMaster::factory()->create();
    $advisor = EmployeeMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->set('opened_date', '2026-08-01')
        ->set('opened_time', '09:30')
        ->set('promised_date', '2026-08-02')
        ->set('promised_time', '17:00')
        ->set('km_at_service', 45000)
        ->set('fuel_level', 'half')
        ->set('suggested_services', 'check brake fluid')
        ->set('terms_accepted', true)
        ->call('save')
        ->assertHasNoErrors();

    $jc = JobCard::first();
    expect($jc->job_card_no)->toStartWith('JC-')
        ->and($jc->opened_at->format('Y-m-d H:i'))->toBe('2026-08-01 09:30')
        ->and($jc->promised_at->format('Y-m-d H:i'))->toBe('2026-08-02 17:00')
        ->and($jc->km_at_service)->toBe(45000)
        ->and($jc->suggested_services)->toBe('CHECK BRAKE FLUID')
        ->and($jc->terms_accepted)->toBeTrue()
        ->and($jc->terms_accepted_at)->not->toBeNull();
});

it('rejects vehicle that does not belong to the chosen customer', function () {
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

it('persists complaints with capital typing and severity', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $dept = WorkshopDepartmentMaster::factory()->create();
    $advisor = EmployeeMaster::factory()->create();
    $type = ComplaintTypeMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->call('addComplaint')
        ->call('addComplaint')
        ->set('complaints.0.description', 'brake noise on left turn')
        ->set('complaints.0.severity', 'high')
        ->set('complaints.0.complaint_type_id', $type->id)
        ->set('complaints.1.description', 'ac not cooling')
        ->set('complaints.1.severity', 'medium')
        ->call('save')
        ->assertHasNoErrors();

    $jc = JobCard::with('complaints')->first();
    expect($jc->complaints)->toHaveCount(2)
        ->and($jc->complaints[0]->description)->toBe('BRAKE NOISE ON LEFT TURN')
        ->and($jc->complaints[0]->severity)->toBe('high')
        ->and($jc->complaints[0]->complaint_type_id)->toBe($type->id)
        ->and($jc->complaints[0]->sequence_no)->toBe(1)
        ->and($jc->complaints[1]->description)->toBe('AC NOT COOLING')
        ->and($jc->complaints[1]->severity)->toBe('medium');
});

it('strips blank complaint rows before validating', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $dept = WorkshopDepartmentMaster::factory()->create();
    $advisor = EmployeeMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->call('addComplaint')
        ->call('addComplaint')
        ->set('complaints.0.description', 'real complaint')
        ->call('save')
        ->assertHasNoErrors();

    expect(JobCard::first()->complaints)->toHaveCount(1);
});

it('persists only checked inventory items', function () {
    $a = VehicleInventoryItemMaster::factory()->create(['name' => 'SPARE TYRE']);
    $b = VehicleInventoryItemMaster::factory()->create(['name' => 'JACK']);
    $c = VehicleInventoryItemMaster::factory()->create(['name' => 'MUSIC SYSTEM']);

    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $dept = WorkshopDepartmentMaster::factory()->create();
    $advisor = EmployeeMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->set("inventoryItems.{$a->id}.is_present", true)
        ->set("inventoryItems.{$b->id}.is_present", true)
        ->set("inventoryItems.{$b->id}.condition_notes", 'rusty handle')
        ->call('save')
        ->assertHasNoErrors();

    $jc = JobCard::with('inventoryItems')->first();
    expect($jc->inventoryItems)->toHaveCount(2)
        ->and($jc->inventoryItems->where('vehicle_inventory_item_id', $a->id)->first()->is_present)->toBeTrue()
        ->and($jc->inventoryItems->where('vehicle_inventory_item_id', $b->id)->first()->condition_notes)->toBe('RUSTY HANDLE')
        ->and($jc->inventoryItems->where('vehicle_inventory_item_id', $c->id)->count())->toBe(0);
});

it('prefills from an appointment when from-appointment query param is set', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $dept = WorkshopDepartmentMaster::factory()->create();
    $advisor = EmployeeMaster::factory()->create();
    $appointment = Appointment::factory()->create([
        'customer_id' => $customer->id,
        'customer_vehicle_id' => $vehicle->id,
        'workshop_department_id' => $dept->id,
        'assigned_advisor_id' => $advisor->id,
    ]);

    Livewire::test(Edit::class, ['fromAppointment' => $appointment->id])
        ->assertSet('appointment_id', $appointment->id)
        ->assertSet('customer_id', $customer->id)
        ->assertSet('customer_vehicle_id', $vehicle->id)
        ->assertSet('assigned_advisor_id', $advisor->id);
});

it('updates an existing job card and re-syncs complaints + inventory', function () {
    $jc = JobCard::factory()->create();
    $type = ComplaintTypeMaster::factory()->create();
    $jc->complaints()->createMany([
        ['description' => 'OLD ONE', 'severity' => 'low', 'sequence_no' => 1],
        ['description' => 'OLD TWO', 'severity' => 'high', 'sequence_no' => 2],
    ]);
    $invItem = VehicleInventoryItemMaster::factory()->create();

    Livewire::test(Edit::class, ['jobCard' => $jc])
        ->call('removeComplaint', 1)                              // drop second complaint
        ->set("inventoryItems.{$invItem->id}.is_present", true)
        ->call('save')
        ->assertHasNoErrors();

    expect($jc->fresh()->complaints)->toHaveCount(1)
        ->and(JobCardComplaint::where('job_card_id', $jc->id)->count())->toBe(1)
        ->and(JobCardInventoryItem::where('job_card_id', $jc->id)->where('vehicle_inventory_item_id', $invItem->id)->exists())->toBeTrue();
});

it('Edit::save blocks a user without create permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('job_card.view');
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

    expect(JobCard::count())->toBe(0);
});

it('deleting a job card cascades complaints and inventory rows', function () {
    $jc = JobCard::factory()->create();
    $invItem = VehicleInventoryItemMaster::factory()->create();
    $jc->complaints()->create(['description' => 'X', 'severity' => 'low', 'sequence_no' => 1]);
    $jc->inventoryItems()->create(['vehicle_inventory_item_id' => $invItem->id, 'is_present' => true]);

    Livewire::test(Index::class)->call('delete', $jc->id);

    expect(JobCard::find($jc->id))->toBeNull()
        ->and(JobCardComplaint::where('job_card_id', $jc->id)->count())->toBe(0)
        ->and(JobCardInventoryItem::where('job_card_id', $jc->id)->count())->toBe(0);
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('job-card.index'))->assertRedirect(route('login'));
});
