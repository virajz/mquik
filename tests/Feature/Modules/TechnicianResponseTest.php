<?php

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TechnicianBench\Livewire\Response;
use App\Modules\TechnicianFinding\Models\TechnicianFinding;
use App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrder;
use App\Support\FinancialYear;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('numbers a work order on the FY series', function () {
    $order = VehicleInspectionOrder::factory()->create(['ordered_at' => now()]);

    expect($order->fresh()->order_no)
        ->toBe('MQ/VIO/'.FinancialYear::label(now()).'/00001');
});

it('never issues a number that collides with a live order', function () {
    $fy = FinancialYear::label(now());

    // A gap in the middle is the case a count()-based sequence gets wrong.
    $first = VehicleInspectionOrder::factory()->create(['ordered_at' => now()]);
    $second = VehicleInspectionOrder::factory()->create(['ordered_at' => now()]);
    $third = VehicleInspectionOrder::factory()->create(['ordered_at' => now()]);
    $second->fresh()->delete();

    $fourth = VehicleInspectionOrder::factory()->create(['ordered_at' => now()]);

    expect($fourth->fresh()->order_no)->toBe('MQ/VIO/'.$fy.'/00004')
        ->and(VehicleInspectionOrder::pluck('order_no')->duplicates())->toBeEmpty();
});

it('numbers each financial year on its own run', function () {
    VehicleInspectionOrder::factory()->create(['ordered_at' => now()]);
    $prior = VehicleInspectionOrder::factory()->create(['ordered_at' => now()->subYear()]);

    expect($prior->fresh()->order_no)
        ->toBe('MQ/VIO/'.FinancialYear::label(now()->subYear()).'/00001');
});

it('shows the vehicle and never the customer', function () {
    $customer = CustomerMaster::factory()->create(['first_name' => 'RAJESH', 'last_name' => 'KUMAR']);
    $vehicle = CustomerVehicleMaster::factory()->create([
        'customer_id' => $customer->id,
        'registration_no' => 'KA01AB1234',
    ]);
    $jobCard = JobCard::factory()->create([
        'customer_id' => $customer->id,
        'customer_vehicle_id' => $vehicle->id,
    ]);
    $order = VehicleInspectionOrder::factory()->create(['job_card_id' => $jobCard->id]);

    Livewire::test(Response::class, ['vehicleInspectionOrder' => $order])
        ->assertSee('KA 01 AB 1234')
        ->assertSee($order->fresh()->order_no)
        ->assertDontSee('RAJESH');
});

it('lists the work scope as the todo list', function () {
    $order = VehicleInspectionOrder::factory()->create();
    $order->workScopes()->create(['description' => 'CLUTCH JUDDER', 'sequence_no' => 1]);
    $order->workScopes()->create(['description' => 'AC NOT COOLING', 'sequence_no' => 2]);

    Livewire::test(Response::class, ['vehicleInspectionOrder' => $order])
        ->assertSee('CLUTCH JUDDER')
        ->assertSee('AC NOT COOLING');
});

it('records a part the technician wants replaced', function () {
    $order = VehicleInspectionOrder::factory()->create();
    $tech = EmployeeMaster::factory()->create(['is_active' => true]);
    $spare = SpareMaster::factory()->create(['is_active' => true, 'name' => 'CLUTCH PLATE']);

    Livewire::test(Response::class, ['vehicleInspectionOrder' => $order])
        ->set('technicianId', $tech->id)
        ->call('newFinding', 'spare')
        ->set('findingSpareId', $spare->id)
        ->set('findingDescription', 'replace clutch plate')
        ->set('findingQuantity', 2)
        ->call('saveFinding')
        ->assertHasNoErrors();

    $finding = TechnicianFinding::where('vehicle_inspection_order_id', $order->id)->firstOrFail();

    expect($finding->finding_type)->toBe(TechnicianFinding::TYPE_SPARE)
        ->and($finding->spare_id)->toBe($spare->id)
        ->and($finding->description)->toBe('REPLACE CLUTCH PLATE')
        ->and((float) $finding->quantity)->toBe(2.0)
        ->and($finding->reported_by_id)->toBe($tech->id)
        ->and($finding->status)->toBe(TechnicianFinding::STATUS_RECOMMENDED);
});

it('records labour work such as a gearbox overhaul', function () {
    $order = VehicleInspectionOrder::factory()->create();
    $labour = LabourMaster::factory()->create(['is_active' => true, 'name' => 'GEAR BOX OVERHAUL']);

    Livewire::test(Response::class, ['vehicleInspectionOrder' => $order])
        ->call('newFinding', 'labour')
        ->set('findingLabourId', $labour->id)
        ->set('findingDescription', 'gear box overhaul')
        ->call('saveFinding')
        ->assertHasNoErrors();

    $finding = TechnicianFinding::where('vehicle_inspection_order_id', $order->id)->firstOrFail();

    expect($finding->finding_type)->toBe(TechnicianFinding::TYPE_LABOUR)
        ->and($finding->labour_id)->toBe($labour->id)
        ->and($finding->recommendation)->toBe('additional_labour');
});

it('will not record a part finding without a part', function () {
    $order = VehicleInspectionOrder::factory()->create();

    Livewire::test(Response::class, ['vehicleInspectionOrder' => $order])
        ->call('newFinding', 'spare')
        ->set('findingDescription', 'something is wrong')
        ->call('saveFinding')
        ->assertHasErrors('findingSpareId');

    expect(TechnicianFinding::where('vehicle_inspection_order_id', $order->id)->count())->toBe(0);
});

it('leaves a finding alone once the advisor has actioned it', function () {
    $order = VehicleInspectionOrder::factory()->create();
    $finding = TechnicianFinding::factory()->create([
        'vehicle_inspection_order_id' => $order->id,
        'job_card_id' => $order->job_card_id,
        'status' => TechnicianFinding::STATUS_APPROVED,
    ]);

    Livewire::test(Response::class, ['vehicleInspectionOrder' => $order])
        ->call('deleteFinding', $finding->id);

    expect(TechnicianFinding::find($finding->id))->not->toBeNull();
});

it('refuses to touch a finding belonging to another work order', function () {
    $order = VehicleInspectionOrder::factory()->create();
    $other = VehicleInspectionOrder::factory()->create();
    $finding = TechnicianFinding::factory()->create([
        'vehicle_inspection_order_id' => $other->id,
        'job_card_id' => $other->job_card_id,
        'status' => TechnicianFinding::STATUS_RECOMMENDED,
    ]);

    Livewire::test(Response::class, ['vehicleInspectionOrder' => $order])
        ->call('deleteFinding', $finding->id);

    expect(TechnicianFinding::find($finding->id))->not->toBeNull();
});

it('saves the technician remark against the order', function () {
    $order = VehicleInspectionOrder::factory()->create();

    Livewire::test(Response::class, ['vehicleInspectionOrder' => $order])
        ->set('technicianRemark', 'road tested, noise gone')
        ->call('saveRemark')
        ->assertHasNoErrors();

    expect($order->fresh()->technician_remark)->toBe('ROAD TESTED, NOISE GONE');
});

it('keeps the first start time when work is resumed after a pause', function () {
    $order = VehicleInspectionOrder::factory()->create();
    $tech = EmployeeMaster::factory()->create(['is_active' => true]);
    $scope = $order->workScopes()->create([
        'description' => 'CLUTCH JUDDER', 'sequence_no' => 1, 'technician_id' => $tech->id,
    ]);

    $page = Livewire::test(Response::class, ['vehicleInspectionOrder' => $order])
        ->set('technicianId', $tech->id)
        ->call('start', $scope->id);

    $firstStart = $scope->fresh()->work_started_at;
    expect($firstStart)->not->toBeNull();

    // Backdate, then resume: the start must not jump forward to now.
    $scope->fresh()->forceFill(['work_started_at' => now()->subHours(3)])->save();
    $page->call('start', $scope->id);

    expect($scope->fresh()->work_started_at->lt(now()->subHours(2)))->toBeTrue();
});
