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
use App\Modules\JobCard\Models\JobCardPhoto;
use App\Modules\JobCardCancelReasonMaster\Models\JobCardCancelReasonMaster;
use App\Modules\JobHistory\Models\JobCardHistoryEvent;
use App\Modules\VehicleInventoryItemMaster\Models\VehicleInventoryItemMaster;
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

it('uploads photos and stores rows + files on the public disk', function () {
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
        ->set('newPhotos', [
            UploadedFile::fake()->image('dent-front.jpg', 800, 600),
            UploadedFile::fake()->image('rear-quarter.jpg', 800, 600),
        ])
        ->set('newPhotoCaptions.0', 'dent on bumper')
        ->call('save')
        ->assertHasNoErrors();

    $jc = JobCard::first();
    expect($jc->photos)->toHaveCount(2);
    expect($jc->photos[0]->caption)->toBe('DENT ON BUMPER');  // upper-cased
    expect($jc->photos[0]->sequence_no)->toBe(1);
    expect($jc->photos[1]->sequence_no)->toBe(2);
    Storage::disk('public')->assertExists($jc->photos[0]->path);
    Storage::disk('public')->assertExists($jc->photos[1]->path);
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

it('drops a staged new-photo before submitting', function () {
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
        ->set('newPhotos', [
            UploadedFile::fake()->image('a.jpg'),
            UploadedFile::fake()->image('b.jpg'),
        ])
        ->call('removeNewPhoto', 0)
        ->call('save')
        ->assertHasNoErrors();

    expect(JobCard::first()->photos)->toHaveCount(1);
});

it('rejects oversized photos', function () {
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
        ->set('newPhotos', [
            // 9 MB image, photo cap is 8 MB
            UploadedFile::fake()->image('huge.jpg')->size(9000),
        ])
        ->call('save')
        ->assertHasErrors(['newPhotos.0']);

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
