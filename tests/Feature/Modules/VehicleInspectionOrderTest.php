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
use App\Modules\TechnicianFinding\Models\TechnicianFinding;
use App\Modules\VehicleInspectionOrder\Livewire\Edit;
use App\Modules\VehicleInspectionOrder\Livewire\Index;
use App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrder;
use App\Modules\WorkOrderHoldReasonMaster\Models\WorkOrderHoldReasonMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
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
    expect($order->order_no)->toBe('VIO-'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT))
        ->and($order->priority->name)->toBe('HIGH')
        ->and($order->status)->toBe(VehicleInspectionOrder::STATUS_ASSIGNMENT_PENDING);
});

it('snapshots template items into the checklist when a template is picked', function () {
    $template = InspectionTemplateMaster::factory()->create(['is_active' => true]);
    $a = InspectionItemMaster::factory()->create(['name' => 'BRAKE PADS']);
    $b = InspectionItemMaster::factory()->create(['name' => 'OIL LEVEL']);
    $template->items()->attach([$a->id => ['position' => 1], $b->id => ['position' => 2]]);

    $component = Livewire::test(Edit::class)->set('inspection_template_id', $template->id);

    expect($component->get('items'))->toHaveCount(2)
        ->and($component->get('items')[0]['label'])->toBe('BRAKE PADS')
        ->and($component->get('items')[0]['result'])->toBe('pending');
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

it('logs a work order started history event on the job card when status moves to wip', function () {
    $jobCard = JobCard::factory()->create();
    $order = VehicleInspectionOrder::factory()->create(['job_card_id' => $jobCard->id]);

    Livewire::test(Edit::class, ['vehicleInspectionOrder' => $order])
        ->set('status', VehicleInspectionOrder::STATUS_WIP)
        ->call('save')
        ->assertHasNoErrors();

    expect($order->fresh()->started_at)->not->toBeNull();

    $event = JobCardHistoryEvent::where('job_card_id', $jobCard->id)
        ->where('event_type', JobCardHistoryEvent::TYPE_WORK_ORDER_STARTED)
        ->first();
    expect($event)->not->toBeNull();
});

it('saves the pause/resume log', function () {
    $order = VehicleInspectionOrder::factory()->wip()->create();
    $reason = WorkOrderHoldReasonMaster::factory()->create(['name' => 'WAITING FOR PARTS']);

    Livewire::test(Edit::class, ['vehicleInspectionOrder' => $order])
        ->set('pauses', [
            ['id' => null, 'hold_reason_id' => $reason->id, 'paused_date' => '2026-06-21', 'paused_time' => '10:00', 'resumed_date' => '2026-06-21', 'resumed_time' => '10:30', 'notes' => 'tea'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $order->refresh()->load('pauses');
    expect($order->pauses)->toHaveCount(1)
        ->and($order->pauses->first()->hold_reason_id)->toBe($reason->id)
        ->and($order->pauses->first()->notes)->toBe('TEA');
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
