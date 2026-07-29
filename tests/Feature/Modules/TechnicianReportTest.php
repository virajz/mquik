<?php

use App\Models\User;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FinalWorkOrder\Models\FinalWorkOrder;
use App\Modules\TechnicianReport\Livewire\Index;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the report page', function () {
    $this->get(route('technician-report.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('aggregates net TAT and item hours per technician', function () {
    $tech = EmployeeMaster::factory()->create(['name' => 'RAVI TECH']);

    // 2h gross, 30m pause → 90m net TAT; two items totalling 2.5 hours.
    $fwo = FinalWorkOrder::factory()->completed()->create([
        'technician_id' => $tech->id,
        'started_at' => now()->subHours(3),
        'ended_at' => now()->subHour(),
    ]);
    $fwo->pauses()->create(['paused_at' => now()->subHours(2)->subMinutes(30), 'resumed_at' => now()->subHours(2)]);
    $fwo->items()->create(['label' => 'A', 'result' => 'ok', 'hours' => 1.5, 'sequence_no' => 1]);
    $fwo->items()->create(['label' => 'B', 'result' => 'ok', 'hours' => 1.0, 'sequence_no' => 2]);

    Livewire::test(Index::class)
        ->assertViewHas('rows', function ($rows) {
            $row = collect($rows)->firstWhere('technician', 'RAVI TECH');

            return $row
                && $row['orders'] === 1
                && (float) $row['item_hours'] === 2.5
                && $row['gross_mins'] === 120
                && $row['pause_mins'] === 30
                && $row['net_tat_mins'] === 90;
        });
});

it('excludes non-completed orders when completed-only is on', function () {
    $tech = EmployeeMaster::factory()->create();
    FinalWorkOrder::factory()->wip()->create(['technician_id' => $tech->id]);

    Livewire::test(Index::class)
        ->assertViewHas('rows', fn ($rows) => count($rows) === 0);
});

it('exports the technician report as CSV', function () {
    $tech = EmployeeMaster::factory()->create();
    FinalWorkOrder::factory()->completed()->create([
        'technician_id' => $tech->id,
        'started_at' => now()->subHour(),
        'ended_at' => now(),
    ]);

    Livewire::test(Index::class)
        ->call('download')
        ->assertFileDownloaded();
});

it('blocks export without permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('technician_report.view');
    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('download')
        ->assertForbidden();
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('technician-report.index'))->assertRedirect(route('login'));
});
