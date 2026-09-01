<?php

use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DigitalInspection\Livewire\Index as InspectionList;
use App\Modules\DigitalInspection\Models\DigitalInspection;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InspectionItemMaster\Models\InspectionItemMaster;
use App\Modules\InspectionReport\Livewire\Index;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use App\Support\FinancialYear;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

// ---------------------------------------------------------------------------
// Numbering
// ---------------------------------------------------------------------------

it('numbers an inspection on the FY series', function () {
    $di = DigitalInspection::factory()->create();

    expect($di->fresh()->inspection_no)->toBe('MQ/VI/'.FinancialYear::label(now()).'/00001');
});

it('never issues an inspection number that collides with a live sheet', function () {
    $fy = FinancialYear::label(now());
    DigitalInspection::factory()->create();
    $second = DigitalInspection::factory()->create();
    DigitalInspection::factory()->create();
    $second->fresh()->delete();

    $fourth = DigitalInspection::factory()->create();

    expect($fourth->fresh()->inspection_no)->toBe('MQ/VI/'.$fy.'/00004')
        ->and(DigitalInspection::pluck('inspection_no')->duplicates())->toBeEmpty();
});

// ---------------------------------------------------------------------------
// Listing
// ---------------------------------------------------------------------------

it('shows only open inspections on load', function () {
    DigitalInspection::factory()->create(['status' => DigitalInspection::STATUS_PENDING]);
    DigitalInspection::factory()->create(['status' => DigitalInspection::STATUS_WIP]);
    DigitalInspection::factory()->completed()->create();
    DigitalInspection::factory()->create(['status' => DigitalInspection::STATUS_CANCELLED]);

    expect(Livewire::test(InspectionList::class)->viewData('rows')->total())->toBe(2)
        ->and(Livewire::test(InspectionList::class)->set('statusFilter', 'all')->viewData('rows')->total())->toBe(4);
});

it('finds a sheet by a plate typed without its spaces', function () {
    $vehicle = CustomerVehicleMaster::factory()->create(['registration_no' => 'GJ 05 XY 7777']);
    $jobCard = JobCard::factory()->create(['customer_vehicle_id' => $vehicle->id]);
    DigitalInspection::factory()->create(['job_card_id' => $jobCard->id]);
    DigitalInspection::factory()->create();

    foreach (['GJ 05 XY 7777', 'GJ05XY7777', 'GJ05%7777'] as $term) {
        expect(Livewire::test(InspectionList::class)->set('search', $term)->viewData('rows')->total())
            ->toBe(1, "searching [{$term}]");
    }
});

it('filters by department, service type, advisor and floor in-charge', function () {
    $dept = WorkshopDepartmentMaster::factory()->create();
    $serviceType = ServiceTypeMaster::factory()->create(['is_active' => true]);
    $advisor = EmployeeMaster::factory()->advisor()->create(['is_active' => true]);
    $floor = EmployeeMaster::factory()->floorIncharge()->create(['is_active' => true]);

    $wanted = DigitalInspection::factory()->create([
        'job_card_id' => JobCard::factory()->create([
            'workshop_department_id' => $dept->id,
            'service_type_id' => $serviceType->id,
        ])->id,
        'advisor_id' => $advisor->id,
        'floor_incharge_id' => $floor->id,
    ]);
    DigitalInspection::factory()->create();

    $only = fn (array $props) => Livewire::test(InspectionList::class)
        ->set('statusFilter', 'all')
        ->set($props)
        ->viewData('rows')
        ->pluck('id')
        ->all();

    expect($only(['departmentFilter' => (string) $dept->id]))->toBe([$wanted->id])
        ->and($only(['serviceTypeFilter' => (string) $serviceType->id]))->toBe([$wanted->id])
        ->and($only(['advisorFilter' => (string) $advisor->id]))->toBe([$wanted->id])
        ->and($only(['floorFilter' => (string) $floor->id]))->toBe([$wanted->id]);
});

it('points the date range at whichever stamp is chosen', function () {
    DigitalInspection::factory()->create([
        'status' => DigitalInspection::STATUS_COMPLETED,
        'started_at' => now()->subDays(10),
        'completed_at' => now(),
    ]);

    $count = fn (string $field, string $from) => Livewire::test(InspectionList::class)
        ->set('statusFilter', 'all')
        ->set('dateField', $field)
        ->set('dateFrom', $from)
        ->viewData('rows')
        ->total();

    // Started ten days ago, completed today: the same range answers differently.
    expect($count('completed_at', now()->toDateString()))->toBe(1)
        ->and($count('started_at', now()->toDateString()))->toBe(0);
});

it('sorts on every column heading without falling over', function () {
    DigitalInspection::factory()->count(2)->create();

    $columns = [
        'inspection_no', 'status', 'created_at', 'started_at', 'completed_at',
        'tat_seconds', 'items_count', 'job_card_no', 'registration_no', 'vehicle_name',
        'template', 'department', 'service_type', 'advisor', 'floor_incharge', 'technician', 'bay',
    ];

    foreach ($columns as $column) {
        foreach (['asc', 'desc'] as $direction) {
            expect(
                Livewire::test(InspectionList::class)
                    ->set('statusFilter', 'all')
                    ->set('sortBy', $column)
                    ->set('sortDirection', $direction)
                    ->viewData('rows')
                    ->total()
            )->toBe(2, "sorting by {$column} {$direction}");
        }
    }
});

it('refuses a sort column that is not on the whitelist', function () {
    $component = Livewire::test(InspectionList::class)->call('sort', 'technician');

    expect($component->get('sortBy'))->toBe('technician');

    $component->call('sort', 'password');

    expect($component->get('sortBy'))->toBe('technician');
});

// ---------------------------------------------------------------------------
// Reports
// ---------------------------------------------------------------------------

it('groups turnaround by day and technician', function () {
    $tech = EmployeeMaster::factory()->technician()->create(['is_active' => true]);
    $day = now()->subDay()->startOfDay()->addHours(9);

    // One hour and three hours on the same day: two sheets, two hours average.
    DigitalInspection::factory()->create([
        'assigned_technician_id' => $tech->id,
        'started_at' => $day,
        'completed_at' => $day->copy()->addHour(),
    ]);
    DigitalInspection::factory()->create([
        'assigned_technician_id' => $tech->id,
        'started_at' => $day,
        'completed_at' => $day->copy()->addHours(3),
    ]);

    $rows = Livewire::test(Index::class)->instance()->tatRows;

    expect($rows)->toHaveCount(1)
        ->and((int) $rows[0]->sheets)->toBe(2)
        ->and(Index::humanise((float) $rows[0]->avg_seconds))->toBe('2h')
        ->and(Index::humanise((float) $rows[0]->min_seconds))->toBe('1h')
        ->and(Index::humanise((float) $rows[0]->max_seconds))->toBe('3h');
});

it('leaves unfinished sheets out of the turnaround figures', function () {
    DigitalInspection::factory()->create(['started_at' => now()->subHour(), 'completed_at' => null]);

    expect(Livewire::test(Index::class)->instance()->tatRows)->toHaveCount(0);
});

it('lists future jobs the customer has not approved, and only those', function () {
    $item = InspectionItemMaster::factory()->create(['name' => 'BRAKE PADS']);

    $sheet = function (?string $approval, string $outcome, ?string $recommendation) use ($item) {
        $di = DigitalInspection::factory()->create(['customer_approval' => $approval]);
        $di->items()->create([
            'inspection_item_id' => $item->id,
            'outcome' => $outcome,
            'recommendation' => $recommendation,
            'sequence_no' => 1,
        ]);

        return $di;
    };

    $notAsked = $sheet(null, DigitalInspection::ACTION_FUTURE, 'replace');
    $deferred = $sheet('deferred', DigitalInspection::ACTION_FUTURE, 'repair');
    $declined = $sheet('declined', DigitalInspection::ACTION_FUTURE, 'skimming');

    // None of these belong on a follow-up list.
    $sheet('approved', DigitalInspection::ACTION_FUTURE, 'replace');   // already sold
    $sheet(null, DigitalInspection::ACTION_IMMEDIATE, 'replace');      // not a future job
    $sheet(null, DigitalInspection::ACTION_FUTURE, 'monitor');         // nothing to sell

    $rows = Livewire::test(Index::class)->set('tab', 'future')->instance()->futureRows;

    expect($rows->pluck('inspection_no')->all())
        ->toEqualCanonicalizing([
            $notAsked->fresh()->inspection_no,
            $deferred->fresh()->inspection_no,
            $declined->fresh()->inspection_no,
        ]);
});

it('renders the report page', function () {
    $this->get(route('inspection-report.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});
