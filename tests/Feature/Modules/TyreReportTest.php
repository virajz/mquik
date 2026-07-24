<?php

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\TyreReport\Livewire\Edit;
use App\Modules\TyreReport\Livewire\Index;
use App\Modules\TyreReport\Models\TyreReport;
use App\Modules\TyreReport\Models\TyreReportLine;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    TyreReport::factory()->count(2)->create();

    $this->get(route('tyre-report.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('starts a new report with exactly the five wheel positions', function () {
    $component = Livewire::test(Edit::class);

    expect($component->get('lines'))->toHaveCount(5)
        ->and(collect($component->get('lines'))->pluck('position')->all())
        ->toBe(['front_left', 'front_right', 'rear_left', 'rear_right', 'spare']);
});

it('creates a report, stamps TR number, and saves all five lines', function () {
    $customer = CustomerMaster::factory()->create();
    $vehicle = CustomerVehicleMaster::factory()->create(['customer_id' => $customer->id]);

    $component = Livewire::test(Edit::class)
        ->set('customer_id', $customer->id)
        ->set('customer_vehicle_id', $vehicle->id)
        ->set('odometer_km', 42000)
        ->set('lines.0.tyre_size', '205/45 r16')
        ->set('lines.0.condition', 'replace')
        ->set('lines.0.has_crack', true)
        ->set('lines.0.tread_depth_mm', 1.8)
        ->call('save')
        ->assertHasNoErrors();

    $report = TyreReport::firstOrFail();
    expect($report->report_no)->toBe('TR-'.str_pad((string) $report->id, 5, '0', STR_PAD_LEFT))
        ->and($report->lines)->toHaveCount(5);

    $fl = $report->lines->firstWhere('position', 'front_left');
    expect($fl->tyre_size)->toBe('205/45 R16')   // uppercased
        ->and($fl->condition)->toBe('replace')
        ->and($fl->has_crack)->toBeTrue()
        ->and($report->replaceCount())->toBe(1);
});

it('rejects a vehicle that does not belong to the chosen customer', function () {
    $a = CustomerMaster::factory()->create();
    $b = CustomerMaster::factory()->create();
    $vehicleOfB = CustomerVehicleMaster::factory()->create(['customer_id' => $b->id]);

    Livewire::test(Edit::class)
        ->set('customer_id', $a->id)
        ->set('customer_vehicle_id', $vehicleOfB->id)
        ->call('save')
        ->assertHasErrors(['customer_vehicle_id']);
});

it('re-saves the same five lines on edit without duplicating them', function () {
    $report = TyreReport::factory()->withLines()->create();
    expect($report->lines)->toHaveCount(5);

    Livewire::test(Edit::class, ['tyreReport' => $report])
        ->set('lines.4.condition', 'repair')
        ->call('save')
        ->assertHasNoErrors();

    $report->refresh();
    expect($report->lines)->toHaveCount(5)
        ->and($report->lines->firstWhere('position', 'spare')->condition)->toBe('repair');
});

it('computes worn percent from tread depth', function () {
    $new = new TyreReportLine(['tread_depth_mm' => 8]);
    $limit = new TyreReportLine(['tread_depth_mm' => 1.6]);
    $mid = new TyreReportLine(['tread_depth_mm' => 4.8]);

    expect($new->wornPercent())->toBe(0)
        ->and($limit->wornPercent())->toBe(100)
        ->and($mid->wornPercent())->toBe(50);
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('tyre-report.index'))->assertRedirect(route('login'));
});
