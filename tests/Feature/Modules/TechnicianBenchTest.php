<?php

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\TechnicianBench\Livewire\Index;
use App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrder;
use App\Modules\WorkOrderHoldReasonMaster\Models\WorkOrderHoldReasonMaster;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the bench page', function () {
    $this->get(route('technician-bench.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('technician-bench.index'))->assertRedirect(route('login'));
});

it('lists only the picked technician\'s task lines', function () {
    $mine = EmployeeMaster::factory()->create();
    $theirs = EmployeeMaster::factory()->create();
    $order = VehicleInspectionOrder::factory()->create();

    $order->workScopes()->create(['description' => 'BRAKE BLEEDING', 'sequence_no' => 1, 'technician_id' => $mine->id]);
    $order->workScopes()->create(['description' => 'CLUTCH OVERHAUL', 'sequence_no' => 2, 'technician_id' => $theirs->id]);

    Livewire::test(Index::class)
        ->set('technicianId', $mine->id)
        ->assertSee('BRAKE BLEEDING')
        ->assertDontSee('CLUTCH OVERHAUL');
});

it('starts then pauses a task, accumulating elapsed seconds', function () {
    $tech = EmployeeMaster::factory()->create();
    $order = VehicleInspectionOrder::factory()->create();
    $scope = $order->workScopes()->create(['description' => 'PMS', 'sequence_no' => 1, 'technician_id' => $tech->id]);

    $component = Livewire::test(Index::class)
        ->set('technicianId', $tech->id)
        ->call('start', $scope->id);

    $scope->refresh();
    expect($scope->work_status)->toBe('in_progress')
        ->and($scope->run_started_at)->not->toBeNull();

    $this->travel(5)->seconds();
    // Pausing now goes through the reason picker — a gap without a reason
    // explains nothing when the advisor reads it back.
    $component->call('askPauseReason', $scope->id)
        ->set('pauseReasonId', WorkOrderHoldReasonMaster::factory()->create(['is_active' => true])->id)
        ->call('pause')
        ->assertHasNoErrors();

    $scope->refresh();
    expect($scope->work_status)->toBe('paused')
        ->and($scope->run_started_at)->toBeNull()
        ->and($scope->duration_seconds)->toBeGreaterThanOrEqual(5);
});

it('keeps one running task per technician across different work orders', function () {
    $tech = EmployeeMaster::factory()->create();
    $orderA = VehicleInspectionOrder::factory()->create();
    $orderB = VehicleInspectionOrder::factory()->create();

    $a = $orderA->workScopes()->create(['description' => 'PMS', 'sequence_no' => 1, 'technician_id' => $tech->id]);
    $b = $orderB->workScopes()->create(['description' => 'WHEEL ALIGNMENT', 'sequence_no' => 1, 'technician_id' => $tech->id]);

    Livewire::test(Index::class)
        ->set('technicianId', $tech->id)
        ->call('start', $a->id)
        ->call('start', $b->id);

    expect($a->fresh()->work_status)->toBe('paused')
        ->and($b->fresh()->work_status)->toBe('in_progress');
});

it('completes a task and stamps completed_at', function () {
    $tech = EmployeeMaster::factory()->create();
    $order = VehicleInspectionOrder::factory()->create();
    $scope = $order->workScopes()->create(['description' => 'PMS', 'sequence_no' => 1, 'technician_id' => $tech->id]);

    Livewire::test(Index::class)
        ->set('technicianId', $tech->id)
        ->call('askCompletionType', $scope->id)
        ->set('completionType', 'fully')
        ->call('complete')
        ->assertHasNoErrors();

    $scope->refresh();
    expect($scope->work_status)->toBe('completed')
        ->and($scope->completed_at)->not->toBeNull()
        // Completion type is recorded per line, by whoever did the work.
        ->and($scope->completion_type)->toBe('fully');
});

it('refuses to touch a task assigned to somebody else', function () {
    $mine = EmployeeMaster::factory()->create();
    $theirs = EmployeeMaster::factory()->create();
    $order = VehicleInspectionOrder::factory()->create();
    $scope = $order->workScopes()->create(['description' => 'PMS', 'sequence_no' => 1, 'technician_id' => $theirs->id]);

    Livewire::test(Index::class)
        ->set('technicianId', $mine->id)
        ->call('start', $scope->id);

    expect($scope->fresh()->work_status)->toBe('pending');
});

it('hides completed tasks unless asked for them', function () {
    $tech = EmployeeMaster::factory()->create();
    $order = VehicleInspectionOrder::factory()->create();
    $order->workScopes()->create([
        'description' => 'FINISHED TASK', 'sequence_no' => 1,
        'technician_id' => $tech->id, 'work_status' => 'completed',
    ]);

    Livewire::test(Index::class)
        ->set('technicianId', $tech->id)
        ->assertDontSee('FINISHED TASK')
        ->set('openOnly', false)
        ->assertSee('FINISHED TASK');
});
