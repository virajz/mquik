<?php

use App\Modules\ComplaintTypeMaster\Models\ComplaintTypeMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InspectionItemMaster\Models\InspectionItemMaster;
use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\JobHistory\Models\JobCardHistoryEvent;
use App\Modules\PhotoTypeMaster\Models\PhotoTypeMaster;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\ServicePackageMaster\Models\ServicePackageMaster;
use App\Modules\TechnicianBench\Livewire\Index as TechnicianBench;
use App\Modules\TechnicianFinding\Models\TechnicianFinding;
use App\Modules\VehicleInspectionOrder\Livewire\Edit;
use App\Modules\VehicleInspectionOrder\Livewire\Index;
use App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrder;
use App\Modules\WorkOrderHoldReasonMaster\Models\WorkOrderHoldReasonMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use App\Support\FinancialYear;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    VehicleInspectionOrder::factory()->count(2)->create();

    $this->get(route('vehicle-inspection-order.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('vehicle-inspection-order.index'))->assertRedirect(route('login'));
});

it('creates a work order, stamps VIO number, and redirects into the editor', function () {
    $jobCard = JobCard::factory()->create();

    $high = PriorityMaster::factory()->create([
        'name' => 'HIGH', 'sort_order' => 20, 'applies_to' => 'both',
    ]);

    Livewire::test(Edit::class)
        ->set('job_card_id', $jobCard->id)
        ->set('priority_id', $high->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $order = VehicleInspectionOrder::firstOrFail();
    expect($order->order_no)->toBe('MQ/VIO/'.FinancialYear::label($order->ordered_at ?? $order->created_at).'/00001')
        ->and($order->priority->name)->toBe('HIGH')
        // Status is derived, never typed: the job card brought a technician with
        // it, so the order is already assigned rather than waiting for one.
        ->and($order->status)->toBe(VehicleInspectionOrder::STATUS_ASSIGNED);
});

it('adds a named checklist to the order, and can take it back off again', function () {
    $template = InspectionTemplateMaster::factory()->create(['is_active' => true, 'name' => 'PMS STANDARD']);
    $a = InspectionItemMaster::factory()->create(['name' => 'BRAKE PADS']);
    $b = InspectionItemMaster::factory()->create(['name' => 'OIL LEVEL']);
    $template->items()->attach([$a->id => ['position' => 1], $b->id => ['position' => 2]]);

    $component = Livewire::test(Edit::class)
        ->set('addTemplateId', (string) $template->id)
        ->call('addTemplateItems');

    expect($component->get('items'))->toHaveCount(2)
        ->and($component->get('items')[0]['label'])->toBe('BRAKE PADS')
        ->and($component->get('items')[0]['result'])->toBe('pending')
        ->and($component->get('items')[0]['inspection_template_id'])->toBe($template->id);

    // Several checklists can sit on one order, so each is removable by name.
    $component->call('removeTemplateItems', $template->id);
    expect($component->get('items'))->toHaveCount(0);
});

it('saves checklist items with results and before/after photos', function () {
    Storage::fake('public');
    $order = VehicleInspectionOrder::factory()->create();

    Livewire::test(Edit::class, ['vehicleInspectionOrder' => $order])
        ->set('items', [
            ['id' => null, 'inspection_item_id' => null, 'inspection_item_group_id' => null, 'label' => 'brake pads', 'group_name' => null, 'result' => 'ia', 'notes' => 'worn', 'sequence_no' => 1, 'before_photo_path' => null, 'after_photo_path' => null],
            ['id' => null, 'inspection_item_id' => null, 'inspection_item_group_id' => null, 'label' => 'oil level', 'group_name' => null, 'result' => 'ok', 'notes' => null, 'sequence_no' => 2, 'before_photo_path' => null, 'after_photo_path' => null],
        ])
        ->set('itemBeforeFiles.0', UploadedFile::fake()->image('before.jpg'))
        ->set('itemAfterFiles.0', UploadedFile::fake()->image('after.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $order->refresh()->load('items');
    expect($order->items)->toHaveCount(2);

    $brake = $order->items->firstWhere('label', 'BRAKE PADS');
    expect($brake->result)->toBe('ia')
        ->and($brake->before_photo_path)->not->toBeNull()
        ->and($brake->after_photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($brake->before_photo_path);
    Storage::disk('public')->assertExists($brake->after_photo_path);
});

it('derives WIP and logs the history event when a technician starts a line', function () {
    $jobCard = JobCard::factory()->create();
    $order = VehicleInspectionOrder::factory()->create([
        'job_card_id' => $jobCard->id,
        'status' => VehicleInspectionOrder::STATUS_ASSIGNED,
    ]);
    $tech = EmployeeMaster::factory()->create(['is_active' => true]);
    // The bench only shows work that reaches this technician.
    $scope = $order->workScopes()->create(['description' => 'CLUTCH JUDDER', 'sequence_no' => 1, 'technician_id' => $tech->id]);

    Livewire::test(TechnicianBench::class)
        ->set('technicianId', $tech->id)
        ->call('start', $scope->id);

    expect($order->fresh()->status)->toBe(VehicleInspectionOrder::STATUS_WIP)
        ->and($order->fresh()->started_at)->not->toBeNull();

    $event = JobCardHistoryEvent::where('job_card_id', $jobCard->id)
        ->where('event_type', JobCardHistoryEvent::TYPE_WORK_ORDER_STARTED)
        ->first();
    expect($event)->not->toBeNull();
});

it('records a pause with its reason when the technician pauses, and closes it on resume', function () {
    $order = VehicleInspectionOrder::factory()->wip()->create();
    $reason = WorkOrderHoldReasonMaster::factory()->create(['name' => 'WAITING FOR PARTS', 'is_active' => true]);
    $tech = EmployeeMaster::factory()->create(['is_active' => true]);
    // The bench only shows work that reaches this technician.
    $scope = $order->workScopes()->create(['description' => 'CLUTCH JUDDER', 'sequence_no' => 1, 'technician_id' => $tech->id]);

    $bench = Livewire::test(TechnicianBench::class)
        ->set('technicianId', $tech->id)
        ->call('start', $scope->id)
        ->call('askPauseReason', $scope->id)
        ->set('pauseReasonId', $reason->id)
        ->call('pause')
        ->assertHasNoErrors();

    $order->refresh()->load('pauses');
    expect($order->pauses)->toHaveCount(1)
        ->and($order->pauses->first()->hold_reason_id)->toBe($reason->id)
        ->and($order->pauses->first()->paused_by_id)->toBe($tech->id)
        ->and($order->pauses->first()->vehicle_inspection_order_scope_id)->toBe($scope->id)
        ->and($order->pauses->first()->resumed_at)->toBeNull()
        ->and($order->fresh()->status)->toBe(VehicleInspectionOrder::STATUS_ON_HOLD);

    // Resuming closes the same gap rather than opening a second one.
    $bench->call('start', $scope->id);
    $order->refresh()->load('pauses');
    expect($order->pauses)->toHaveCount(1)
        ->and($order->pauses->first()->resumed_at)->not->toBeNull();
});

it('refuses to pause without a reason', function () {
    $order = VehicleInspectionOrder::factory()->wip()->create();
    $tech = EmployeeMaster::factory()->create(['is_active' => true]);
    // The bench only shows work that reaches this technician.
    $scope = $order->workScopes()->create(['description' => 'CLUTCH JUDDER', 'sequence_no' => 1, 'technician_id' => $tech->id]);

    Livewire::test(TechnicianBench::class)
        ->set('technicianId', $tech->id)
        ->call('start', $scope->id)
        ->call('askPauseReason', $scope->id)
        ->call('pause')
        ->assertHasErrors('pauseReasonId');

    expect($order->pauses()->count())->toBe(0);
});

it('deletes a work order from the index', function () {
    $order = VehicleInspectionOrder::factory()->create();

    Livewire::test(Index::class)->call('delete', $order->id);

    expect(VehicleInspectionOrder::find($order->id))->toBeNull();
});

it('saves work scope lines covering complaints, job descriptions and packages', function () {
    $order = VehicleInspectionOrder::factory()->create();
    $complaint = ComplaintTypeMaster::factory()->create();
    $package = ServicePackageMaster::factory()->create();

    Livewire::test(Edit::class, ['vehicleInspectionOrder' => $order])
        ->call('addWorkScope')
        ->set('workScopes.0.complaint_type_id', $complaint->id)
        ->set('workScopes.0.description', 'fr side noise')
        ->call('addWorkScope')
        ->set('workScopes.1.service_package_id', $package->id)
        ->set('workScopes.1.description', 'pms')
        ->call('save')
        ->assertHasNoErrors();

    $order->refresh()->load('workScopes');
    expect($order->workScopes)->toHaveCount(2)
        ->and($order->workScopes->first()->description)->toBe('FR SIDE NOISE')
        ->and($order->workScopes->first()->complaint_type_id)->toBe($complaint->id)
        ->and($order->workScopes->last()->service_package_id)->toBe($package->id);

    // Removing a line deletes it rather than orphaning it.
    Livewire::test(Edit::class, ['vehicleInspectionOrder' => $order])
        ->call('removeWorkScope', 1)
        ->call('save')
        ->assertHasNoErrors();

    expect($order->fresh()->workScopes)->toHaveCount(1);
});

it('requires a description on every work scope line', function () {
    $order = VehicleInspectionOrder::factory()->create();

    Livewire::test(Edit::class, ['vehicleInspectionOrder' => $order])
        ->call('addWorkScope')
        ->set('workScopes.0.description', '')
        ->call('save')
        ->assertHasErrors(['workScopes.0.description']);
});

it('flags a work scope line as additional work performed', function () {
    $order = VehicleInspectionOrder::factory()->create();

    Livewire::test(Edit::class, ['vehicleInspectionOrder' => $order])
        ->call('addWorkScope')
        ->set('workScopes.0.description', 'brake pad replacement')
        ->set('workScopes.0.is_additional', true)
        ->call('addWorkScope')
        ->set('workScopes.1.description', 'pms')
        ->call('save')
        ->assertHasNoErrors();

    $order->refresh()->load('workScopes');
    expect($order->workScopes->firstWhere('description', 'BRAKE PAD REPLACEMENT')->is_additional)->toBeTrue()
        ->and($order->workScopes->firstWhere('description', 'PMS')->is_additional)->toBeFalse();
});

it('stores order-level photo evidence against a photo type', function () {
    Storage::fake('public');
    $order = VehicleInspectionOrder::factory()->create();
    $type = PhotoTypeMaster::factory()->create(['name' => 'DAMAGE PHOTO']);

    Livewire::test(Edit::class, ['vehicleInspectionOrder' => $order])
        ->call('addPhoto')
        ->set('photos.0.photo_type_id', $type->id)
        ->set('photos.0.notes', 'dent on left door')
        ->set('photoFiles.0', UploadedFile::fake()->image('damage.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $photo = $order->fresh()->photos->first();
    expect($photo)->not->toBeNull()
        ->and($photo->photo_type_id)->toBe($type->id)
        ->and($photo->notes)->toBe('dent on left door');
    Storage::disk('public')->assertExists($photo->path);
});

it('drops photo rows that never got an image', function () {
    $order = VehicleInspectionOrder::factory()->create();

    Livewire::test(Edit::class, ['vehicleInspectionOrder' => $order])
        ->call('addPhoto')
        ->call('save')
        ->assertHasNoErrors();

    expect($order->fresh()->photos)->toHaveCount(0);
});

it('surfaces technician findings as the order\'s additional work', function () {
    $order = VehicleInspectionOrder::factory()->create();
    TechnicianFinding::factory()->create([
        'vehicle_inspection_order_id' => $order->id,
        'description' => 'BRAKE DISC WORN',
    ]);

    expect($order->fresh()->findings)->toHaveCount(1)
        ->and($order->fresh()->findings->first()->description)->toBe('BRAKE DISC WORN');
});

it('counts pending, active, completed and cancelled inspections', function () {
    VehicleInspectionOrder::factory()->create(['status' => VehicleInspectionOrder::STATUS_ASSIGNMENT_PENDING]);
    VehicleInspectionOrder::factory()->create(['status' => VehicleInspectionOrder::STATUS_ASSIGNED]);
    VehicleInspectionOrder::factory()->create(['status' => VehicleInspectionOrder::STATUS_WIP]);
    VehicleInspectionOrder::factory()->create(['status' => VehicleInspectionOrder::STATUS_ON_HOLD]);
    VehicleInspectionOrder::factory()->create(['status' => VehicleInspectionOrder::STATUS_COMPLETED]);
    VehicleInspectionOrder::factory()->create(['status' => VehicleInspectionOrder::STATUS_CANCELLED]);

    // Active spans assigned + wip + on_hold.
    Livewire::test(Index::class)->assertViewHas('kpis', fn ($k) => $k['pending'] === 1
        && $k['active'] === 3
        && $k['completed'] === 1
        && $k['cancelled'] === 1);
});

it('starts then pauses a task timer, accumulating elapsed seconds', function () {
    $tech = EmployeeMaster::factory()->create();
    $order = VehicleInspectionOrder::factory()->create(['technician_id' => $tech->id]);
    $scope = $order->workScopes()->create(['description' => 'PMS', 'sequence_no' => 1]);

    $component = Livewire::test(Edit::class, ['vehicleInspectionOrder' => $order])->call('startScope', 0);

    $scope->refresh();
    expect($scope->work_status)->toBe('in_progress')
        ->and($scope->run_started_at)->not->toBeNull()
        ->and($scope->technician_id)->toBe($tech->id);

    $this->travel(5)->seconds();
    $component->call('pauseScope', 0);

    $scope->refresh();
    expect($scope->work_status)->toBe('paused')
        ->and($scope->run_started_at)->toBeNull()
        ->and($scope->duration_seconds)->toBeGreaterThanOrEqual(5);
});

it('keeps one active task at a time per technician', function () {
    $tech = EmployeeMaster::factory()->create();
    $order = VehicleInspectionOrder::factory()->create(['technician_id' => $tech->id]);
    $a = $order->workScopes()->create(['description' => 'PMS', 'sequence_no' => 1]);
    $b = $order->workScopes()->create(['description' => 'WHEEL ALIGNMENT', 'sequence_no' => 2]);

    Livewire::test(Edit::class, ['vehicleInspectionOrder' => $order])
        ->call('startScope', 0)
        ->call('startScope', 1);

    expect($a->fresh()->work_status)->toBe('paused')
        ->and($b->fresh()->work_status)->toBe('in_progress');
});

it('completes a task timer and stamps completed_at', function () {
    $tech = EmployeeMaster::factory()->create();
    $order = VehicleInspectionOrder::factory()->create(['technician_id' => $tech->id]);
    $scope = $order->workScopes()->create(['description' => 'PMS', 'sequence_no' => 1]);

    Livewire::test(Edit::class, ['vehicleInspectionOrder' => $order])
        ->call('startScope', 0)
        ->call('completeScope', 0);

    $scope->refresh();
    expect($scope->work_status)->toBe('completed')
        ->and($scope->completed_at)->not->toBeNull()
        ->and($scope->run_started_at)->toBeNull();
});

it('does not reset a running timer when the order form is saved', function () {
    $tech = EmployeeMaster::factory()->create();
    $order = VehicleInspectionOrder::factory()->create(['technician_id' => $tech->id]);
    $scope = $order->workScopes()->create(['description' => 'PMS', 'sequence_no' => 1]);

    Livewire::test(Edit::class, ['vehicleInspectionOrder' => $order])
        ->call('startScope', 0)
        ->call('save')
        ->assertHasNoErrors();

    $scope->refresh();
    expect($scope->work_status)->toBe('in_progress')
        ->and($scope->run_started_at)->not->toBeNull();
});

it('populates department, service type, advisor and technician when a job card is picked', function () {
    $dept = WorkshopDepartmentMaster::factory()->create();
    $advisor = EmployeeMaster::factory()->create();
    $tech = EmployeeMaster::factory()->create();
    $jobCard = JobCard::factory()->create([
        'workshop_department_id' => $dept->id,
        'assigned_advisor_id' => $advisor->id,
        'assigned_technician_id' => $tech->id,
    ]);

    Livewire::test(Edit::class)
        ->set('job_card_id', $jobCard->id)
        ->assertSet('department_id', $dept->id)
        ->assertSet('advisor_id', $advisor->id)
        ->assertSet('technician_id', $tech->id)
        ->assertSet('service_type_id', $jobCard->service_type_id);
});
