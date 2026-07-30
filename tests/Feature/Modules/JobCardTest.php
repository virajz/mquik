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
use App\Modules\StandardObservationMaster\Models\StandardObservationMaster;
use App\Modules\VehicleInventoryItemMaster\Models\VehicleInventoryItemMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

it('links insurance company, policy, vendor, job description and customer approval', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $dept = WorkshopDepartmentMaster::factory()->create();
    $advisor = EmployeeMaster::factory()->create();
    $insurer = InsuranceCompanyMaster::factory()->create();
    $vendor = VendorMaster::factory()->create();
    $jobDescription = JobDescriptionMaster::factory()->create();
    $approval = CustomerApprovalTypeMaster::factory()->create();

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->set('insurance_company_id', $insurer->id)
        ->set('policy_no', 'pol-12345')
        ->set('vendor_id', $vendor->id)
        ->set('job_description_id', $jobDescription->id)
        ->set('customer_approval_type_id', $approval->id)
        ->call('save')
        ->assertHasNoErrors();

    $jc = JobCard::first();
    expect($jc->insurance_company_id)->toBe($insurer->id)
        ->and($jc->policy_no)->toBe('POL-12345')
        ->and($jc->vendor_id)->toBe($vendor->id)
        ->and($jc->job_description_id)->toBe($jobDescription->id)
        ->and($jc->customer_approval_type_id)->toBe($approval->id);
});

it('links digital inspections from the job card header', function () {
    $jc = JobCard::factory()->create();
    $di = DigitalInspection::factory()->create(['job_card_id' => $jc->id]);

    Livewire::test(Edit::class, ['jobCard' => $jc])
        ->assertSee('New inspection')
        ->assertSee($di->fresh()->inspection_no);
});

it('auto-sets the customer from the chosen vehicle (combined picker)', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);

    Livewire::test(Edit::class)
        ->set('customer_vehicle_id', $vehicle->id)   // pick vehicle only…
        ->assertSet('customer_id', $customer->id);    // …customer is derived
});

it('persists complaints with capital typing and severity', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $dept = WorkshopDepartmentMaster::factory()->create();
    $advisor = EmployeeMaster::factory()->create();
    $type = ComplaintTypeMaster::factory()->create();
    $brake = StandardObservationMaster::factory()->create(['name' => 'BRAKE PADS WORN OUT']);
    $ac = StandardObservationMaster::factory()->create(['name' => 'AC COOLING LOW']);

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->call('addComplaint')
        ->call('addComplaint')
        ->set('complaints.0.standard_observation_id', $brake->id)
        ->set('complaints.0.severity', 'high')
        ->set('complaints.0.complaint_type_id', $type->id)
        ->set('complaints.1.standard_observation_id', $ac->id)
        ->set('complaints.1.severity', 'medium')
        ->call('save')
        ->assertHasNoErrors();

    $jc = JobCard::with('complaints')->first();
    expect($jc->complaints)->toHaveCount(2)
        ->and($jc->complaints[0]->standard_observation_id)->toBe($brake->id)
        ->and($jc->complaints[0]->description)->toBe('BRAKE PADS WORN OUT')   // derived from the picked phrase
        ->and($jc->complaints[0]->severity)->toBe('high')
        ->and($jc->complaints[0]->complaint_type_id)->toBe($type->id)
        ->and($jc->complaints[0]->sequence_no)->toBe(1)
        ->and($jc->complaints[1]->description)->toBe('AC COOLING LOW')
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
        ->set('complaints.0.standard_observation_id', StandardObservationMaster::factory()->create()->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(JobCard::first()->complaints)->toHaveCount(1);
});

it('does not persist a complaint row unless a complaint is picked (no free typing)', function () {
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
        ->set('complaints.0.severity', 'high')   // no observation picked — nothing to type
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

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->set('requestedRepairIds', [$alignment->id, $acGas->id])
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

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->set("inventoryItems.{$a->id}.status", 'present')          // stays present → not stored
        ->set("inventoryItems.{$b->id}.status", 'missing')
        ->set("inventoryItems.{$b->id}.condition_notes", 'no jack in boot')
        ->set("inventoryItems.{$c->id}.status", 'damaged')
        ->set("inventoryItems.{$c->id}.damage_type_id", $damage->id)
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
    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->set("inventoryItems.{$item->id}.status", 'damaged')
        ->set("inventoryItems.{$item->id}.damage_type_id", $damage->id)
        ->set("inventoryItems.{$item->id}.status", 'missing')
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
        ['standard_observation_id' => StandardObservationMaster::factory()->create()->id, 'description' => 'OLD ONE', 'severity' => 'low', 'sequence_no' => 1],
        ['standard_observation_id' => StandardObservationMaster::factory()->create()->id, 'description' => 'OLD TWO', 'severity' => 'high', 'sequence_no' => 2],
    ]);
    $invItem = VehicleInventoryItemMaster::factory()->create();

    Livewire::test(Edit::class, ['jobCard' => $jc])
        ->call('removeComplaint', 1)                              // drop second complaint
        ->set("inventoryItems.{$invItem->id}.status", 'missing')
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

it('captures slot photos and extra photos, tagging type + group', function () {
    Storage::fake('public');

    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);
    $dept = WorkshopDepartmentMaster::factory()->create();
    $advisor = EmployeeMaster::factory()->create();

    $front = PhotoTypeMaster::factory()->inGroup('EXTERIOR', 101)->create(['name' => 'FRONT']);
    $odo = PhotoTypeMaster::factory()->inGroup('METER', 301)->create(['name' => 'ODOMETER']);

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->set("slotFiles.{$front->id}", UploadedFile::fake()->image('front.jpg', 800, 600))
        ->set("slotFiles.{$odo->id}", UploadedFile::fake()->image('odo.jpg', 800, 600))
        ->set('extraFiles', [UploadedFile::fake()->image('scratch.jpg', 800, 600)])
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

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->set('extraFiles', [UploadedFile::fake()->image('scratch.jpg', 800, 600)])
        ->set('extraDamageTypes.0', $scratch->id)
        ->set('extraLocations.0', 'front-left bumper')
        ->call('save')
        ->assertHasNoErrors();

    $extra = JobCard::first()->photos->firstWhere('photo_type_id', null);
    expect($extra->damage_type_id)->toBe($scratch->id)
        ->and($extra->location_note)->toBe('FRONT-LEFT BUMPER');
});

it('shows the read-only customer & vehicle summary once both are picked', function () {
    $customer = CustomerMaster::factory()->create(); // factory always sets a business type
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);

    Livewire::test(Edit::class)
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

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->set("slotFiles.{$front->id}", UploadedFile::fake()->image('a.jpg'))
        ->call('clearSlotFile', $front->id)
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

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        // 9 MB image, photo cap is 8 MB
        ->set("slotFiles.{$front->id}", UploadedFile::fake()->image('huge.jpg')->size(9000))
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

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->set('terms_accepted', true)
        ->set('signatureUpload', UploadedFile::fake()->image('signature.png'))
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

    Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('workshop_department_id', $dept->id)
        ->set('assigned_advisor_id', $advisor->id)
        ->set('signatureUpload', UploadedFile::fake()->image('big-sig.png')->size(3000))  // 3 MB > 2 MB cap
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
