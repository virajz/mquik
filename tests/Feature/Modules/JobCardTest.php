<?php

use App\Models\User;
use App\Modules\Appointment\Models\Appointment;
use App\Modules\ComplaintTypeMaster\Models\ComplaintTypeMaster;
use App\Modules\CustomerApprovalTypeMaster\Models\CustomerApprovalTypeMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DamageTypeMaster\Models\DamageTypeMaster;
use App\Modules\DigitalInspection\Models\DigitalInspection;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\GateInOut\Models\GateInOut;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\JobCard\Livewire\Edit;
use App\Modules\JobCard\Livewire\Index;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\JobCard\Models\JobCardComplaint;
use App\Modules\JobCard\Models\JobCardInventoryItem;
use App\Modules\JobCard\Models\JobCardPhoto;
use App\Modules\JobCardCancelReasonMaster\Models\JobCardCancelReasonMaster;
use App\Modules\JobCardPendingReasonMaster\Models\JobCardPendingReasonMaster;
use App\Modules\JobDescriptionMaster\Models\JobDescriptionMaster;
use App\Modules\JobHistory\Models\JobCardHistoryEvent;
use App\Modules\JobStageMaster\Models\JobStageMaster;
use App\Modules\PhotoTypeMaster\Models\PhotoTypeMaster;
use App\Modules\RequestedRepairMaster\Models\RequestedRepairMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrder;
use App\Modules\VehicleInventoryItemMaster\Models\VehicleInventoryItemMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use App\Support\FinancialYear;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

/**
 * A create form with the fields that are now mandatory already filled.
 *
 * Gate entry, service type, fuel level, who accepted the terms, and an owner
 * for the work became required when the job-card form was reworked; every
 * create-flow test needs them, and none of them is what the test is about.
 */
function newJobCardForm(): Testable
{
    return Livewire::test(Edit::class)
        ->set('gate_event_id', GateInOut::factory()->create()->id)
        ->set('fuel_level', 'half')
        ->set('terms_accepted_by', 'customer');
}

it('renders the index page', function () {
    JobCard::factory()->count(3)->create();

    $this->get(route('job-card.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('auto-stamps an FY-aware job_card_no on create', function () {
    $jc = JobCard::factory()->create();

    expect($jc->fresh()->job_card_no)
        ->toBe('MQ/JC/'.FinancialYear::label($jc->opened_at ?? $jc->created_at).'/0001');
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

    newJobCardForm()
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
        // Set last: picking a department clears the service type and the people
        // on it, so these have to come after whatever the test itself sets.
        ->set('service_type_id', ServiceTypeMaster::factory()->create(['is_active' => true])->id)
        ->set('assigned_technician_id', EmployeeMaster::factory()->create(['is_active' => true])->id)
        ->call('save')
        ->assertHasNoErrors();

    $jc = JobCard::first();
    expect($jc->job_card_no)->toStartWith('MQ/JC/')
        ->and($jc->opened_at->format('Y-m-d H:i'))->toBe('2026-08-01 09:30')
        ->and($jc->promised_at->format('Y-m-d H:i'))->toBe('2026-08-02 17:00')
        ->and($jc->km_at_service)->toBe(45000)
        ->and($jc->suggested_services)->toBe('CHECK BRAKE FLUID')
        ->and($jc->terms_accepted)->toBeTrue()
        ->and($jc->terms_accepted_at)->not->toBeNull();
});

it('links insurance company, vendor, job description and customer approval', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    // Insurance is a bodyshop concern; on any other department the form drops it.
    $dept = WorkshopDepartmentMaster::factory()->create(['name' => 'BODYSHOP']);
    $advisor = EmployeeMaster::factory()->create();
    $insurer = InsuranceCompanyMaster::factory()->create();
    $vendor = VendorMaster::factory()->create();
    $jobDescription = JobDescriptionMaster::factory()->create();
    $approval = CustomerApprovalTypeMaster::factory()->create();

    newJobCardForm()
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->set('vendor_id', $vendor->id)   // outside contractor owns the work
        ->set('job_description_id', $jobDescription->id)
        ->set('customer_approval_type_id', $approval->id)
        // Set last: picking a department clears the service type and the people
        // on it, so these have to come after whatever the test itself sets.
        // It must be an insurance service type: the insurer and policy fields
        // are gated on the service type's is_insurance flag, not on its name,
        // and a non-insurance type clears them.
        ->set('service_type_id', ServiceTypeMaster::factory()->create(['is_active' => true, 'is_insurance' => true])->id)
        ->set('insurance_company_id', $insurer->id)
        // No technician here on purpose: an outside vendor owns this work, and
        // picking a technician would take the vendor back off.
        ->call('save')
        ->assertHasNoErrors();

    $jc = JobCard::first();
    expect($jc->insurance_company_id)->toBe($insurer->id)
        ->and($jc->vendor_id)->toBe($vendor->id)
        ->and($jc->job_description_id)->toBe($jobDescription->id)
        ->and($jc->customer_approval_type_id)->toBe($approval->id);
});

it('links digital inspections from the job card header', function () {
    $jc = JobCard::factory()->create();
    $di = DigitalInspection::factory()->create(['job_card_id' => $jc->id]);

    // Quick-jump links: the header lists this card's inspections and work orders.
    Livewire::test(Edit::class, ['jobCard' => $jc])
        ->assertSee($di->fresh()->inspection_no);
});

it('auto-sets the customer from the chosen vehicle (combined picker)', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);

    newJobCardForm()
        ->set('customer_vehicle_id', $vehicle->id)   // pick vehicle only…
        ->assertSet('customer_id', $customer->id);    // …customer is derived
});

it('persists complaints picked from the requested-repair master', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $dept = WorkshopDepartmentMaster::factory()->create();
    $advisor = EmployeeMaster::factory()->create();
    $type = ComplaintTypeMaster::factory()->create();
    $brake = RequestedRepairMaster::factory()->create(['name' => 'BRAKE PADS WORN OUT', 'is_active' => true, 'complaint_type_id' => $type->id]);
    $ac = RequestedRepairMaster::factory()->create(['name' => 'AC COOLING LOW', 'is_active' => true]);

    newJobCardForm()
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->call('addComplaint')
        ->call('addComplaint')
        ->set('complaints.0.requested_repair_id', $brake->id)
        ->set('complaints.1.requested_repair_id', $ac->id)
        ->set('service_type_id', ServiceTypeMaster::factory()->create(['is_active' => true])->id)
        ->set('assigned_technician_id', EmployeeMaster::factory()->create(['is_active' => true])->id)
        ->call('save')
        ->assertHasNoErrors();

    $jc = JobCard::with('complaints')->first();
    expect($jc->complaints)->toHaveCount(2)
        ->and($jc->complaints[0]->requested_repair_id)->toBe($brake->id)
        // Users pick, they don't type: the repair's own name is the complaint text.
        ->and($jc->complaints[0]->description)->toBe('BRAKE PADS WORN OUT')
        // And the group comes with it, rather than being chosen separately.
        ->and($jc->complaints[0]->complaint_type_id)->toBe($type->id)
        ->and($jc->complaints[0]->sequence_no)->toBe(1)
        ->and($jc->complaints[1]->description)->toBe('AC COOLING LOW');
});

it('strips blank complaint rows before validating', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $dept = WorkshopDepartmentMaster::factory()->create();
    $advisor = EmployeeMaster::factory()->create();

    newJobCardForm()
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->call('addComplaint')
        ->call('addComplaint')
        ->set('complaints.0.requested_repair_id', RequestedRepairMaster::factory()->create(['is_active' => true])->id)
        // Set last: picking a department clears the service type and the people
        // on it, so these have to come after whatever the test itself sets.
        ->set('service_type_id', ServiceTypeMaster::factory()->create(['is_active' => true])->id)
        ->set('assigned_technician_id', EmployeeMaster::factory()->create(['is_active' => true])->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(JobCard::first()->complaints)->toHaveCount(1);
});

it('does not persist a complaint row unless a complaint is picked (no free typing)', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $dept = WorkshopDepartmentMaster::factory()->create();
    $advisor = EmployeeMaster::factory()->create();

    newJobCardForm()
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->call('addComplaint')
        // Set last: picking a department clears the service type and the people
        // on it, so these have to come after whatever the test itself sets.
        ->set('service_type_id', ServiceTypeMaster::factory()->create(['is_active' => true])->id)
        ->set('assigned_technician_id', EmployeeMaster::factory()->create(['is_active' => true])->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(JobCard::first()->complaints)->toHaveCount(0);
});

it('syncs requested repairs (many-to-many) on the job card', function () {
    $alignment = RequestedRepairMaster::factory()->create(['name' => 'WHEEL ALIGNMENT']);
    $acGas = RequestedRepairMaster::factory()->create(['name' => 'AC GAS REFILL']);

    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $dept = WorkshopDepartmentMaster::factory()->create();
    $advisor = EmployeeMaster::factory()->create();

    newJobCardForm()
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->set('requestedRepairIds', [$alignment->id, $acGas->id])
        // Set last: picking a department clears the service type and the people
        // on it, so these have to come after whatever the test itself sets.
        ->set('service_type_id', ServiceTypeMaster::factory()->create(['is_active' => true])->id)
        ->set('assigned_technician_id', EmployeeMaster::factory()->create(['is_active' => true])->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(JobCard::first()->requestedRepairs->pluck('id')->sort()->values()->all())
        ->toBe(collect([$alignment->id, $acGas->id])->sort()->values()->all());
});

it('persists missing and damaged inventory exceptions, skips plain present items', function () {
    $a = VehicleInventoryItemMaster::factory()->create(['name' => 'SPARE TYRE']);
    $b = VehicleInventoryItemMaster::factory()->create(['name' => 'JACK']);
    $c = VehicleInventoryItemMaster::factory()->create(['name' => 'MUSIC SYSTEM']);
    $damage = DamageTypeMaster::factory()->create(['name' => 'BROKEN']);

    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $dept = WorkshopDepartmentMaster::factory()->create();
    $advisor = EmployeeMaster::factory()->create();

    newJobCardForm()
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->set("inventoryItems.{$a->id}.status", 'present')          // stays present → not stored
        ->set("inventoryItems.{$b->id}.status", 'missing')
        ->set("inventoryItems.{$b->id}.condition_notes", 'no jack in boot')
        ->set("inventoryItems.{$c->id}.status", 'damaged')
        ->set("inventoryItems.{$c->id}.damage_type_id", $damage->id)
        // Set last: picking a department clears the service type and the people
        // on it, so these have to come after whatever the test itself sets.
        ->set('service_type_id', ServiceTypeMaster::factory()->create(['is_active' => true])->id)
        ->set('assigned_technician_id', EmployeeMaster::factory()->create(['is_active' => true])->id)
        ->call('save')
        ->assertHasNoErrors();

    $jc = JobCard::with('inventoryItems')->first();
    $missing = $jc->inventoryItems->firstWhere('vehicle_inventory_item_id', $b->id);
    $damaged = $jc->inventoryItems->firstWhere('vehicle_inventory_item_id', $c->id);

    expect($jc->inventoryItems)->toHaveCount(2)
        ->and($jc->inventoryItems->where('vehicle_inventory_item_id', $a->id)->count())->toBe(0)
        ->and($missing->status)->toBe('missing')
        ->and($missing->is_present)->toBeFalse()
        ->and($missing->condition_notes)->toBe('NO JACK IN BOOT')
        ->and($damaged->status)->toBe('damaged')
        ->and($damaged->is_present)->toBeFalse()
        ->and($damaged->damage_type_id)->toBe($damage->id);
});

it('clears damage type when a damaged item is switched back to present or missing', function () {
    $item = VehicleInventoryItemMaster::factory()->create(['name' => 'STEPNEY']);
    $damage = DamageTypeMaster::factory()->create(['name' => 'DENT']);

    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $dept = WorkshopDepartmentMaster::factory()->create();
    $advisor = EmployeeMaster::factory()->create();

    // Mark damaged with a damage type, then flip to missing before saving.
    newJobCardForm()
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->set("inventoryItems.{$item->id}.status", 'damaged')
        ->set("inventoryItems.{$item->id}.damage_type_id", $damage->id)
        ->set("inventoryItems.{$item->id}.status", 'missing')
        // Set last: picking a department clears the service type and the people
        // on it, so these have to come after whatever the test itself sets.
        ->set('service_type_id', ServiceTypeMaster::factory()->create(['is_active' => true])->id)
        ->set('assigned_technician_id', EmployeeMaster::factory()->create(['is_active' => true])->id)
        ->call('save')
        ->assertHasNoErrors();

    $row = JobCardInventoryItem::where('vehicle_inventory_item_id', $item->id)->first();
    expect($row->status)->toBe('missing')
        ->and($row->damage_type_id)->toBeNull();
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
        ['requested_repair_id' => RequestedRepairMaster::factory()->create(['is_active' => true])->id, 'description' => 'OLD ONE', 'sequence_no' => 1],
        ['requested_repair_id' => RequestedRepairMaster::factory()->create(['is_active' => true])->id, 'description' => 'OLD TWO', 'sequence_no' => 2],
    ]);
    $invItem = VehicleInventoryItemMaster::factory()->create();

    Livewire::test(Edit::class, ['jobCard' => $jc])
        ->call('removeComplaint', 1)                              // drop second complaint
        ->set("inventoryItems.{$invItem->id}.status", 'missing')
        // Set last: picking a department clears the service type and the people
        // on it, so these have to come after whatever the test itself sets.
        ->set('service_type_id', ServiceTypeMaster::factory()->create(['is_active' => true])->id)
        ->set('assigned_technician_id', EmployeeMaster::factory()->create(['is_active' => true])->id)
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

    newJobCardForm()
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        // Set last: picking a department clears the service type and the people
        // on it, so these have to come after whatever the test itself sets.
        ->set('service_type_id', ServiceTypeMaster::factory()->create(['is_active' => true])->id)
        ->set('assigned_technician_id', EmployeeMaster::factory()->create(['is_active' => true])->id)
        ->call('save')
        ->assertStatus(403);

    expect(JobCard::count())->toBe(0);
});

it('deleting a job card cascades complaints and inventory rows', function () {
    $jc = JobCard::factory()->create();
    $invItem = VehicleInventoryItemMaster::factory()->create();
    $jc->complaints()->create(['description' => 'X', 'sequence_no' => 1]);
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

it('captures slot photos and extra photos, tagging type + group', function () {
    Storage::fake('public');

    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $dept = WorkshopDepartmentMaster::factory()->create();
    $advisor = EmployeeMaster::factory()->create();

    $front = PhotoTypeMaster::factory()->inGroup('EXTERIOR', 101)->create(['name' => 'FRONT']);
    $odo = PhotoTypeMaster::factory()->inGroup('METER', 301)->create(['name' => 'ODOMETER']);

    newJobCardForm()
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->set("slotFiles.{$front->id}", UploadedFile::fake()->image('front.jpg', 800, 600))
        ->set("slotFiles.{$odo->id}", UploadedFile::fake()->image('odo.jpg', 800, 600))
        ->set('extraFiles', [UploadedFile::fake()->image('scratch.jpg', 800, 600)])
        // Set last: picking a department clears the service type and the people
        // on it, so these have to come after whatever the test itself sets.
        ->set('service_type_id', ServiceTypeMaster::factory()->create(['is_active' => true])->id)
        ->set('assigned_technician_id', EmployeeMaster::factory()->create(['is_active' => true])->id)
        ->call('save')
        ->assertHasNoErrors();

    $jc = JobCard::first();
    expect($jc->photos)->toHaveCount(3);

    $frontPhoto = $jc->photos->firstWhere('photo_type_id', $front->id);
    expect($frontPhoto->photo_group)->toBe('EXTERIOR');
    Storage::disk('public')->assertExists($frontPhoto->path);

    $odoPhoto = $jc->photos->firstWhere('photo_type_id', $odo->id);
    expect($odoPhoto->photo_group)->toBe('METER');

    $extra = $jc->photos->firstWhere('photo_type_id', null);
    expect($extra->photo_group)->toBe('ADDITIONAL');
    Storage::disk('public')->assertExists($extra->path);
});

it('tags an additional photo with damage type and location', function () {
    Storage::fake('public');

    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $dept = WorkshopDepartmentMaster::factory()->create();
    $advisor = EmployeeMaster::factory()->create();
    $scratch = DamageTypeMaster::factory()->create(['name' => 'SCRATCH']);

    newJobCardForm()
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->set('extraFiles', [UploadedFile::fake()->image('scratch.jpg', 800, 600)])
        ->set('extraDamageTypes.0', $scratch->id)
        ->set('extraLocations.0', 'front-left bumper')
        // Set last: picking a department clears the service type and the people
        // on it, so these have to come after whatever the test itself sets.
        ->set('service_type_id', ServiceTypeMaster::factory()->create(['is_active' => true])->id)
        ->set('assigned_technician_id', EmployeeMaster::factory()->create(['is_active' => true])->id)
        ->call('save')
        ->assertHasNoErrors();

    $extra = JobCard::first()->photos->firstWhere('photo_type_id', null);
    expect($extra->damage_type_id)->toBe($scratch->id)
        ->and($extra->location_note)->toBe('FRONT-LEFT BUMPER');
});

it('shows the read-only customer & vehicle summary once both are picked', function () {
    $customer = CustomerMaster::factory()->create(); // factory always sets a business type
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);

    newJobCardForm()
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->assertSee('Customer Type')
        ->assertSee($customer->businessType->name)
        ->assertSee('Vehicle')               // vehicle details block renders…
        ->assertSee($vehicle->model->name);  // …with the vehicle's make/model
});

it('replaces a slot photo on retake instead of duplicating it', function () {
    Storage::fake('public');

    $front = PhotoTypeMaster::factory()->inGroup('EXTERIOR', 101)->create(['name' => 'FRONT']);
    $jc = JobCard::factory()->create();
    $oldPath = UploadedFile::fake()->image('old-front.jpg')->store("job-cards/{$jc->id}/photos", 'public');
    JobCardPhoto::create([
        'job_card_id' => $jc->id,
        'photo_type_id' => $front->id,
        'photo_group' => 'EXTERIOR',
        'path' => $oldPath,
        'sequence_no' => 1,
    ]);

    Livewire::test(Edit::class, ['jobCard' => $jc])
        ->set("slotFiles.{$front->id}", UploadedFile::fake()->image('new-front.jpg', 800, 600))
        // Set last: picking a department clears the service type and the people
        // on it, so these have to come after whatever the test itself sets.
        ->set('service_type_id', ServiceTypeMaster::factory()->create(['is_active' => true])->id)
        ->set('assigned_technician_id', EmployeeMaster::factory()->create(['is_active' => true])->id)
        ->call('save')
        ->assertHasNoErrors();

    $slotPhotos = JobCardPhoto::where('job_card_id', $jc->id)->where('photo_type_id', $front->id)->get();
    expect($slotPhotos)->toHaveCount(1);
    Storage::disk('public')->assertMissing($oldPath);          // old file gone
    Storage::disk('public')->assertExists($slotPhotos->first()->path);
});

it('removes existing photos on edit, deleting the file too', function () {
    Storage::fake('public');

    $jc = JobCard::factory()->create();
    $stored = UploadedFile::fake()->image('old.jpg')->store("job-cards/{$jc->id}/photos", 'public');
    $photo = JobCardPhoto::create([
        'job_card_id' => $jc->id,
        'path' => $stored,
        'original_name' => 'old.jpg',
        'mime_type' => 'image/jpeg',
        'sequence_no' => 1,
    ]);

    Livewire::test(Edit::class, ['jobCard' => $jc])
        ->call('removeExistingPhoto', $photo->id)
        // Set last: picking a department clears the service type and the people
        // on it, so these have to come after whatever the test itself sets.
        ->set('service_type_id', ServiceTypeMaster::factory()->create(['is_active' => true])->id)
        ->set('assigned_technician_id', EmployeeMaster::factory()->create(['is_active' => true])->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(JobCardPhoto::find($photo->id))->toBeNull();
    Storage::disk('public')->assertMissing($stored);
});

it('undoes a pending photo removal before save', function () {
    Storage::fake('public');

    $jc = JobCard::factory()->create();
    $stored = UploadedFile::fake()->image('keepme.jpg')->store("job-cards/{$jc->id}/photos", 'public');
    $photo = JobCardPhoto::create([
        'job_card_id' => $jc->id,
        'path' => $stored,
        'original_name' => 'keepme.jpg',
        'sequence_no' => 1,
    ]);

    Livewire::test(Edit::class, ['jobCard' => $jc])
        ->call('removeExistingPhoto', $photo->id)
        ->call('undoRemoveExistingPhoto', $photo->id)
        // Set last: picking a department clears the service type and the people
        // on it, so these have to come after whatever the test itself sets.
        ->set('service_type_id', ServiceTypeMaster::factory()->create(['is_active' => true])->id)
        ->set('assigned_technician_id', EmployeeMaster::factory()->create(['is_active' => true])->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(JobCardPhoto::find($photo->id))->not->toBeNull();
    Storage::disk('public')->assertExists($stored);
});

it('drops a staged slot photo before submitting', function () {
    Storage::fake('public');

    $front = PhotoTypeMaster::factory()->inGroup('EXTERIOR', 101)->create(['name' => 'FRONT']);
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $dept = WorkshopDepartmentMaster::factory()->create();
    $advisor = EmployeeMaster::factory()->create();

    newJobCardForm()
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->set("slotFiles.{$front->id}", UploadedFile::fake()->image('a.jpg'))
        ->call('clearSlotFile', $front->id)
        // Set last: picking a department clears the service type and the people
        // on it, so these have to come after whatever the test itself sets.
        ->set('service_type_id', ServiceTypeMaster::factory()->create(['is_active' => true])->id)
        ->set('assigned_technician_id', EmployeeMaster::factory()->create(['is_active' => true])->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(JobCard::first()->photos)->toHaveCount(0);
});

it('rejects oversized photos', function () {
    Storage::fake('public');

    $front = PhotoTypeMaster::factory()->inGroup('EXTERIOR', 101)->create(['name' => 'FRONT']);
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $dept = WorkshopDepartmentMaster::factory()->create();
    $advisor = EmployeeMaster::factory()->create();

    newJobCardForm()
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        // 9 MB image, photo cap is 8 MB
        ->set("slotFiles.{$front->id}", UploadedFile::fake()->image('huge.jpg')->size(9000))
        // Set last: picking a department clears the service type and the people
        // on it, so these have to come after whatever the test itself sets.
        ->set('service_type_id', ServiceTypeMaster::factory()->create(['is_active' => true])->id)
        ->set('assigned_technician_id', EmployeeMaster::factory()->create(['is_active' => true])->id)
        ->call('save')
        ->assertHasErrors(['slotFiles.'.$front->id]);

    expect(JobCard::count())->toBe(0);
});

it('captures customer signature on save and stamps the path', function () {
    Storage::fake('public');

    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $dept = WorkshopDepartmentMaster::factory()->create();
    $advisor = EmployeeMaster::factory()->create();

    newJobCardForm()
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->set('terms_accepted', true)
        ->set('signatureUpload', UploadedFile::fake()->image('signature.png'))
        // Set last: picking a department clears the service type and the people
        // on it, so these have to come after whatever the test itself sets.
        ->set('service_type_id', ServiceTypeMaster::factory()->create(['is_active' => true])->id)
        ->set('assigned_technician_id', EmployeeMaster::factory()->create(['is_active' => true])->id)
        ->call('save')
        ->assertHasNoErrors();

    $jc = JobCard::first();
    expect($jc->customer_signature_path)->not->toBeNull();
    expect($jc->customer_signature_path)->toStartWith("job-cards/{$jc->id}/signature/");
    Storage::disk('public')->assertExists($jc->customer_signature_path);
});

it('replaces an existing signature and deletes the old file', function () {
    Storage::fake('public');

    $jc = JobCard::factory()->create();
    $oldPath = UploadedFile::fake()->image('old-sig.png')->store("job-cards/{$jc->id}/signature", 'public');
    $jc->forceFill(['customer_signature_path' => $oldPath])->save();

    Livewire::test(Edit::class, ['jobCard' => $jc])
        ->set('signatureUpload', UploadedFile::fake()->image('new-sig.png'))
        // Set last: picking a department clears the service type and the people
        // on it, so these have to come after whatever the test itself sets.
        ->set('service_type_id', ServiceTypeMaster::factory()->create(['is_active' => true])->id)
        ->set('assigned_technician_id', EmployeeMaster::factory()->create(['is_active' => true])->id)
        ->call('save')
        ->assertHasNoErrors();

    $fresh = $jc->fresh();
    expect($fresh->customer_signature_path)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($fresh->customer_signature_path);
});

it('clears an existing signature when markClearSignature is invoked', function () {
    Storage::fake('public');

    $jc = JobCard::factory()->create();
    $oldPath = UploadedFile::fake()->image('to-clear.png')->store("job-cards/{$jc->id}/signature", 'public');
    $jc->forceFill(['customer_signature_path' => $oldPath])->save();

    Livewire::test(Edit::class, ['jobCard' => $jc])
        ->call('markClearSignature')
        // Set last: picking a department clears the service type and the people
        // on it, so these have to come after whatever the test itself sets.
        ->set('service_type_id', ServiceTypeMaster::factory()->create(['is_active' => true])->id)
        ->set('assigned_technician_id', EmployeeMaster::factory()->create(['is_active' => true])->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($jc->fresh()->customer_signature_path)->toBeNull();
    Storage::disk('public')->assertMissing($oldPath);
});

it('rejects oversized signature uploads', function () {
    Storage::fake('public');

    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $dept = WorkshopDepartmentMaster::factory()->create();
    $advisor = EmployeeMaster::factory()->create();

    newJobCardForm()
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->set('signatureUpload', UploadedFile::fake()->image('big-sig.png')->size(3000))  // 3 MB > 2 MB cap
        // Set last: picking a department clears the service type and the people
        // on it, so these have to come after whatever the test itself sets.
        ->set('service_type_id', ServiceTypeMaster::factory()->create(['is_active' => true])->id)
        ->set('assigned_technician_id', EmployeeMaster::factory()->create(['is_active' => true])->id)
        ->call('save')
        ->assertHasErrors(['signatureUpload']);

    expect(JobCard::count())->toBe(0);
});

it('cascades photo rows when a job card is deleted', function () {
    $jc = JobCard::factory()->create();
    JobCardPhoto::create([
        'job_card_id' => $jc->id,
        'path' => "job-cards/{$jc->id}/photos/x.jpg",
        'sequence_no' => 1,
    ]);

    Livewire::test(Index::class)->call('delete', $jc->id);

    expect(JobCardPhoto::where('job_card_id', $jc->id)->count())->toBe(0);
});

it('cancels a job card with a reason via the Index modal flow', function () {
    $jc = JobCard::factory()->create();
    $reason = JobCardCancelReasonMaster::factory()->create();

    Livewire::test(Index::class)
        ->call('openCancelModal', $jc->id)
        ->assertSet('cancellingId', $jc->id)
        ->set('cancel_reason_id', $reason->id)
        ->set('cancellation_notes', 'customer rescheduled to next month')
        ->call('confirmCancel')
        ->assertHasNoErrors();

    $fresh = $jc->fresh();
    expect($fresh->status)->toBe(JobCard::STATUS_CANCELLED)
        ->and($fresh->cancel_reason_id)->toBe($reason->id)
        ->and($fresh->cancelled_at)->not->toBeNull()
        ->and($fresh->cancellation_notes)->toBe('CUSTOMER RESCHEDULED TO NEXT MONTH');
});

it('records a cancelled history event when cancelled via the modal', function () {
    $jc = JobCard::factory()->create();
    $reason = JobCardCancelReasonMaster::factory()->create();

    Livewire::test(Index::class)
        ->call('openCancelModal', $jc->id)
        ->set('cancel_reason_id', $reason->id)
        ->call('confirmCancel');

    expect(JobCardHistoryEvent::where('job_card_id', $jc->id)
        ->where('event_type', JobCardHistoryEvent::TYPE_CANCELLED)
        ->exists())->toBeTrue();
});

it('requires a cancellation reason', function () {
    $jc = JobCard::factory()->create();

    Livewire::test(Index::class)
        ->call('openCancelModal', $jc->id)
        ->set('cancellation_notes', 'just because')
        ->call('confirmCancel')
        ->assertHasErrors(['cancel_reason_id']);

    expect($jc->fresh()->status)->toBe(JobCard::STATUS_OPEN);
});

it('refuses to cancel a job card that is already cancelled / closed / completed', function () {
    $closed = JobCard::factory()->create(['status' => JobCard::STATUS_CLOSED]);
    $completed = JobCard::factory()->completed()->create();
    $cancelled = JobCard::factory()->create(['status' => JobCard::STATUS_CANCELLED]);
    $reason = JobCardCancelReasonMaster::factory()->create();

    foreach ([$closed, $completed, $cancelled] as $jc) {
        $beforeStatus = $jc->fresh()->status;
        Livewire::test(Index::class)
            ->call('openCancelModal', $jc->id)
            ->set('cancel_reason_id', $reason->id)
            ->call('confirmCancel')
            ->assertHasNoErrors();
        expect($jc->fresh()->status)->toBe($beforeStatus);
    }
});

it('blocks cancel for a user without job_card.cancel permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('job_card.view');
    $this->actingAs($user);

    $jc = JobCard::factory()->create();
    $reason = JobCardCancelReasonMaster::factory()->create();

    Livewire::test(Index::class)
        ->call('openCancelModal', $jc->id)
        ->assertStatus(403);

    expect($jc->fresh()->status)->toBe(JobCard::STATUS_OPEN);
});

it('logs a stage change to history and persists current_stage_id', function () {
    $jc = JobCard::factory()->create();
    $stage = JobStageMaster::factory()->inTrack('insurance', 201)->create(['name' => 'DOCUMENT COLLECTION']);

    $jc->update(['current_stage_id' => $stage->id]);

    expect($jc->fresh()->current_stage_id)->toBe($stage->id)
        ->and(JobCardHistoryEvent::where('job_card_id', $jc->id)
            ->where('event_type', JobCardHistoryEvent::TYPE_STAGE_CHANGED)->exists())->toBeTrue();
});

it('logs a pending reason change and persists pending_reason_id', function () {
    $jc = JobCard::factory()->create();
    $reason = JobCardPendingReasonMaster::factory()->create(['name' => 'SPARE AWAITED']);

    $jc->update(['pending_reason_id' => $reason->id]);

    expect($jc->fresh()->pending_reason_id)->toBe($reason->id)
        ->and(JobCardHistoryEvent::where('job_card_id', $jc->id)
            ->where('event_type', JobCardHistoryEvent::TYPE_PENDING_REASON_CHANGED)->exists())->toBeTrue();
});

it('lists other job cards for the same vehicle in the history drawer', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $past = JobCard::factory()->create(['customer_id' => $customer->id, 'customer_vehicle_id' => $vehicle->id]);
    $current = JobCard::factory()->create(['customer_id' => $customer->id, 'customer_vehicle_id' => $vehicle->id]);
    $otherVehicleCard = JobCard::factory()->create();

    $component = Livewire::test(Edit::class, ['jobCard' => $current]);

    $ids = $component->instance()->vehicleJobCards->pluck('id');
    expect($ids)->toContain($past->id)
        ->and($ids)->not->toContain($current->id)
        ->and($ids)->not->toContain($otherVehicleCard->id);

    // The other vehicle's card never appears in the drawer markup.
    $component->assertSee($past->job_card_no)->assertDontSee($otherVehicleCard->job_card_no);
});

it('stamps technician_assigned_at when a technician is assigned and clears it when unassigned', function () {
    $tech = EmployeeMaster::factory()->create();
    $card = JobCard::factory()->create(['assigned_technician_id' => null]);
    expect($card->technician_assigned_at)->toBeNull();

    $card->update(['assigned_technician_id' => $tech->id]);
    expect($card->fresh()->technician_assigned_at)->not->toBeNull();

    $card->update(['assigned_technician_id' => null]);
    expect($card->fresh()->technician_assigned_at)->toBeNull();
});

it('server-side searches the vehicle picker by registration and owner name', function () {
    $owner = CustomerMaster::factory()->create(['is_active' => true, 'first_name' => 'Zoravar', 'last_name' => 'Singh']);
    $veh = CustomerVehicleMaster::factory()->create(['is_active' => true, 'customer_id' => $owner->id, 'registration_no' => 'GJ01ZZ9999']);
    $other = CustomerVehicleMaster::factory()->create(['is_active' => true, 'registration_no' => 'GJ01AA1111']);

    // Owner name reaches the vehicle via the relation whereHas path.
    $ids = collect(Livewire::test(Edit::class)->set('vehicleSearch', 'Zoravar')->get('vehiclePickerOptions'))->pluck('id');
    expect($ids)->toContain($veh->id)->not->toContain($other->id);

    // Registration number reaches it too.
    $ids = collect(Livewire::test(Edit::class)->set('vehicleSearch', 'GJ01ZZ9999')->get('vehiclePickerOptions'))->pluck('id');
    expect($ids)->toContain($veh->id)->not->toContain($other->id);
});

it('lists the job card\'s work orders and offers the assign-work-order link', function () {
    $jobCard = JobCard::factory()->create();
    $vio = VehicleInspectionOrder::factory()
        ->create(['job_card_id' => $jobCard->id]);

    Livewire::test(Edit::class, ['jobCard' => $jobCard])
        ->assertSee($vio->order_no)
        ->assertSee(route('vehicle-inspection-order.create', ['from-job-card' => $jobCard->id]), escape: false);
});

// ---------------------------------------------------------------------------
// The create form must be able to satisfy its own validation
// ---------------------------------------------------------------------------

it('creates a card from only the fields the create screen actually shows', function () {
    // The regression this guards: fuel_level was required but rendered only on
    // the edit screen, so a fully-filled create form failed on a field nobody
    // could see and the button appeared to do nothing.
    $vehicle = CustomerVehicleMaster::factory()->create();
    $dept = WorkshopDepartmentMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('gate_event_id', GateInOut::factory()->create([
            'customer_id' => $vehicle->customer_id,
            'customer_vehicle_id' => $vehicle->id,
        ])->id)
        ->set('customer_id', $vehicle->customer_id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('service_type_id', ServiceTypeMaster::factory()->create(['is_active' => true])->id)
        ->set('assigned_advisor_id', EmployeeMaster::factory()->create(['is_active' => true])->id)
        ->set('assigned_technician_id', EmployeeMaster::factory()->create(['is_active' => true])->id)
        ->set('fuel_level', 'half')
        ->call('save')
        ->assertHasNoErrors();

    expect(JobCard::count())->toBe(1);
});

// ---------------------------------------------------------------------------
// Insurance is asked at intake, not afterwards
// ---------------------------------------------------------------------------

it('asks for the insurer and policy on a bodyshop card, and only at intake', function () {
    $vehicle = CustomerVehicleMaster::factory()->create();
    $bodyshop = WorkshopDepartmentMaster::factory()->create(['name' => 'BODYSHOP']);
    $insurer = InsuranceCompanyMaster::factory()->create(['is_active' => true]);

    $component = Livewire::test(Edit::class)
        ->set('gate_event_id', GateInOut::factory()->create([
            'customer_id' => $vehicle->customer_id,
            'customer_vehicle_id' => $vehicle->id,
        ])->id)
        ->set('customer_id', $vehicle->customer_id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $bodyshop->id)
        // Insurance work, not a paid repair: that is what puts the section on screen.
        ->set('service_type_id', ServiceTypeMaster::factory()->create([
            'is_active' => true, 'is_insurance' => true,
        ])->id)
        ->set('assigned_advisor_id', EmployeeMaster::factory()->create(['is_active' => true])->id)
        ->set('assigned_technician_id', EmployeeMaster::factory()->create(['is_active' => true])->id)
        ->set('fuel_level', 'half')
        ->set('insurance_company_id', $insurer->id)
        ->set('policy_no', 'POL-98765');

    expect($component->html())->toContain('wire:model="policy_no"');

    $component->call('save')->assertHasNoErrors();

    $card = JobCard::firstOrFail();

    expect($card->insurance_company_id)->toBe($insurer->id)
        ->and($card->policy_no)->toBe('POL-98765');

    // Afterwards it is shown, not edited — the claim module owns it from here.
    expect(Livewire::test(Edit::class, ['jobCard' => $card])->html())
        ->not->toContain('wire:model="policy_no"')
        ->not->toContain('wire:model="insurance_company_id"')
        ->toContain('POL-98765');
});

it('does not ask about insurance on a card that is not bodyshop work', function () {
    $dept = WorkshopDepartmentMaster::factory()->create(['name' => 'MECHANICAL']);

    $html = Livewire::test(Edit::class)
        ->set('workshop_department_id', $dept->id)
        ->html();

    expect($html)->not->toContain('wire:model="insurance_company_id"');
});

it('drops the insurer and policy when the card moves off bodyshop work', function () {
    $bodyshop = WorkshopDepartmentMaster::factory()->create(['name' => 'BODYSHOP']);
    $mechanical = WorkshopDepartmentMaster::factory()->create(['name' => 'MECHANICAL']);

    $component = Livewire::test(Edit::class)
        ->set('workshop_department_id', $bodyshop->id)
        ->set('insurance_company_id', InsuranceCompanyMaster::factory()->create(['is_active' => true])->id)
        ->set('policy_no', 'POL-1')
        ->set('workshop_department_id', $mechanical->id);

    expect($component->get('insurance_company_id'))->toBeNull()
        ->and($component->get('policy_no'))->toBeNull();
});

it('requires the insurer only when the service type is flagged as insurance', function () {
    $bodyshop = WorkshopDepartmentMaster::factory()->create(['name' => 'BODYSHOP']);
    // Named nothing like "insurance" on purpose: the old rule matched on the
    // name and would have missed this one entirely.
    $claim = ServiceTypeMaster::factory()->create([
        'name' => 'CASHLESS CLAIM', 'workshop_department_id' => $bodyshop->id,
        'is_active' => true, 'is_insurance' => true,
    ]);
    $paid = ServiceTypeMaster::factory()->create([
        'name' => 'PAID DENT REPAIR', 'workshop_department_id' => $bodyshop->id,
        'is_active' => true, 'is_insurance' => false,
    ]);

    $vehicle = CustomerVehicleMaster::factory()->create();
    $form = fn ($serviceType) => Livewire::test(Edit::class)
        ->set('gate_event_id', GateInOut::factory()->create([
            'customer_id' => $vehicle->customer_id,
            'customer_vehicle_id' => $vehicle->id,
        ])->id)
        ->set('customer_id', $vehicle->customer_id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $bodyshop->id)
        ->set('service_type_id', $serviceType->id)
        ->set('assigned_advisor_id', EmployeeMaster::factory()->create(['is_active' => true])->id)
        ->set('assigned_technician_id', EmployeeMaster::factory()->create(['is_active' => true])->id)
        ->set('fuel_level', 'half');

    $form($claim)->call('save')->assertHasErrors('insurance_company_id');
    $form($paid)->call('save')->assertHasNoErrors();
});

it('hides insurance on a bodyshop repair the customer is paying for', function () {
    $bodyshop = WorkshopDepartmentMaster::factory()->create(['name' => 'BODYSHOP']);
    $repair = ServiceTypeMaster::factory()->create([
        'name' => 'BODYSHOP REPAIR', 'workshop_department_id' => $bodyshop->id,
        'is_active' => true, 'is_insurance' => false,
    ]);
    $claim = ServiceTypeMaster::factory()->create([
        'name' => 'BODYSHOP CLAIM', 'workshop_department_id' => $bodyshop->id,
        'is_active' => true, 'is_insurance' => true,
    ]);

    $component = Livewire::test(Edit::class)->set('workshop_department_id', $bodyshop->id);

    // The department alone does not put it on screen.
    expect($component->html())->not->toContain('wire:model="policy_no"');

    $component->set('service_type_id', $repair->id);
    expect($component->html())->not->toContain('wire:model="policy_no"');

    $component->set('service_type_id', $claim->id);
    expect($component->html())->toContain('wire:model="policy_no"');

    // And moving back to paid work must not leave an insurer attached.
    $component
        ->set('insurance_company_id', InsuranceCompanyMaster::factory()->create(['is_active' => true])->id)
        ->set('policy_no', 'POL-1')
        ->set('service_type_id', $repair->id);

    expect($component->get('insurance_company_id'))->toBeNull()
        ->and($component->get('policy_no'))->toBeNull();
});

it('sets a pending reason on the card and stamps it on the history', function () {
    $card = JobCard::factory()->create();
    $reason = JobCardPendingReasonMaster::factory()->create(['name' => 'SPARE AWAITED', 'is_active' => true]);

    Livewire::test(Edit::class, ['jobCard' => $card])
        ->set('pending_reason_id', $reason->id)
        ->set('service_type_id', $card->service_type_id)
        ->call('save')
        ->assertHasNoErrors();

    expect($card->fresh()->pending_reason_id)->toBe($reason->id)
        ->and(JobCardHistoryEvent::where('job_card_id', $card->id)
            ->where('event_type', JobCardHistoryEvent::TYPE_PENDING_REASON_CHANGED)->exists())->toBeTrue()
        // "since" is read off the timeline, not a column, so the two cannot drift.
        ->and(Livewire::test(Edit::class, ['jobCard' => $card->fresh()])->instance()->pendingSince)
        ->not->toBeNull();
});

it('adds a pending reason to the master without leaving the card', function () {
    $card = JobCard::factory()->create();

    $component = Livewire::test(Edit::class, ['jobCard' => $card])
        ->call('openPendingReasonQuickAdd')
        ->set('pendingReasonQuickName', 'waiting for insurance survey')
        ->call('createPendingReason')
        ->assertHasNoErrors();

    $created = JobCardPendingReasonMaster::where('name', 'WAITING FOR INSURANCE SURVEY')->firstOrFail();

    expect($created->is_active)->toBeTrue()
        ->and($component->get('pending_reason_id'))->toBe($created->id);
});

it('stamps a complaint with the moment it was recorded, without asking', function () {
    // The "Reported at" input is gone from the row; the value still has to land.
    $card = JobCard::factory()->create();

    Livewire::test(Edit::class, ['jobCard' => $card])
        ->set('service_type_id', $card->service_type_id)
        ->call('addComplaint')
        ->set('complaints.0.requested_repair_id', RequestedRepairMaster::factory()->create(['is_active' => true])->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($card->fresh()->complaints()->latest('id')->first()->reported_at)->not->toBeNull();
});

it('ticks complaints from the grouped list, one at a time or a whole category', function () {
    $dept = WorkshopDepartmentMaster::factory()->create();
    $card = JobCard::factory()->create(['workshop_department_id' => $dept->id]);
    $brakes = ComplaintTypeMaster::factory()->create(['name' => 'BRAKE']);

    // Only 'frequent' repairs get a tick box; 'general' ones live in the picker
    // below it. Promotion to frequent is what the master form's "Shown as" does.
    $pads = RequestedRepairMaster::factory()->create(['name' => 'PAD NOISE', 'is_active' => true, 'category' => 'frequent', 'complaint_type_id' => $brakes->id]);
    $disc = RequestedRepairMaster::factory()->create(['name' => 'DISC WARPED', 'is_active' => true, 'category' => 'frequent', 'complaint_type_id' => $brakes->id]);
    foreach ([$pads, $disc] as $repair) {
        $repair->workshopDepartments()->syncWithoutDetaching([$dept->id]);
    }

    $component = Livewire::test(Edit::class, ['jobCard' => $card]);

    expect($component->instance()->complaintGroups->keys()->all())->toContain('BRAKE');

    // One at a time: ticking adds a row, ticking again takes it away.
    $component->call('toggleComplaint', $pads->id);
    expect($component->get('complaints'))->toHaveCount(1)
        ->and($component->instance()->selectedComplaintIds)->toBe([(string) $pads->id]);

    $component->call('toggleComplaint', $pads->id);
    expect($component->get('complaints'))->toHaveCount(0);

    // The category header takes the whole group with it, both ways.
    $component->call('toggleComplaintGroup', 'BRAKE');
    expect($component->get('complaints'))->toHaveCount(2);

    $component->call('toggleComplaintGroup', 'BRAKE');
    expect($component->get('complaints'))->toHaveCount(0);
});

it('keeps the repeat flag and reported time when a complaint is ticked', function () {
    $dept = WorkshopDepartmentMaster::factory()->create();
    $card = JobCard::factory()->create(['workshop_department_id' => $dept->id]);
    $repair = RequestedRepairMaster::factory()->create(['is_active' => true]);
    $repair->workshopDepartments()->syncWithoutDetaching([$dept->id]);

    Livewire::test(Edit::class, ['jobCard' => $card])
        ->call('toggleComplaint', $repair->id)
        ->set('complaints.0.is_repeat_job', true)
        ->set('service_type_id', $card->service_type_id)
        ->call('save')
        ->assertHasNoErrors();

    $complaint = $card->fresh()->complaints()->firstOrFail();

    expect($complaint->requested_repair_id)->toBe($repair->id)
        ->and($complaint->is_repeat_job)->toBeTrue()
        // Stamped, never typed — the field is not on screen.
        ->and($complaint->reported_at)->not->toBeNull();
});
