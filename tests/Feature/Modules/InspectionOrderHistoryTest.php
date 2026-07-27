<?php

use App\Models\User;
use App\Modules\BayMaster\Models\BayMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InspectionOrderHistory\Livewire\Index;
use App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrder;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the history page', function () {
    VehicleInspectionOrder::factory()->count(3)->create();

    $this->get(route('inspection-order-history.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('filters by technician', function () {
    $tech = EmployeeMaster::factory()->create(['name' => 'RAVI TECH']);
    $a = VehicleInspectionOrder::factory()->create(['technician_id' => $tech->id]);
    $b = VehicleInspectionOrder::factory()->create();

    Livewire::test(Index::class)
        ->set('technicianFilter', (string) $tech->id)
        ->assertSee($a->order_no)
        ->assertDontSee($b->order_no);
});

it('filters by status', function () {
    $done = VehicleInspectionOrder::factory()->create(['status' => VehicleInspectionOrder::STATUS_COMPLETED]);
    $open = VehicleInspectionOrder::factory()->create(['status' => VehicleInspectionOrder::STATUS_WIP]);

    Livewire::test(Index::class)
        ->set('statusFilter', VehicleInspectionOrder::STATUS_COMPLETED)
        ->assertSee($done->order_no)
        ->assertDontSee($open->order_no);
});

it('filters by bay', function () {
    $bay = BayMaster::factory()->create(['name' => 'BAY 7']);
    $in = VehicleInspectionOrder::factory()->create(['bay_id' => $bay->id]);
    $out = VehicleInspectionOrder::factory()->create();

    Livewire::test(Index::class)
        ->set('bayFilter', (string) $bay->id)
        ->assertSee($in->order_no)
        ->assertDontSee($out->order_no);
});

it('exports the filtered set as CSV', function () {
    VehicleInspectionOrder::factory()->create(['status' => VehicleInspectionOrder::STATUS_COMPLETED]);

    $response = Livewire::test(Index::class)
        ->set('statusFilter', VehicleInspectionOrder::STATUS_COMPLETED)
        ->call('download');

    $response->assertFileDownloaded();
});

it('blocks export without permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('inspection_order_history.view');
    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('download')
        ->assertForbidden();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('inspection-order-history.index'))->assertRedirect(route('login'));
});
