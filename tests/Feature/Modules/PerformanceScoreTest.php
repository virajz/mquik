<?php

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\Payroll\Models\Payroll;
use App\Modules\PerformanceScore\Livewire\Edit;
use App\Modules\PerformanceScore\Livewire\Index;
use App\Modules\PerformanceScore\Models\PerformanceScore;
use App\Modules\PerformanceSlabMaster\Models\PerformanceSlabMaster;
use App\Modules\SmartSalary\Models\SmartSalary;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(adminUser());
});

it('renders the index page', function () {
    PerformanceScore::factory()->count(2)->create();

    $this->get(route('performance-score.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

it('requires authentication', function () {
    auth()->logout();
    $this->get(route('performance-score.index'))->assertRedirect(route('login'));
});

it('computes net points, achievement % and matches a slab to an incentive', function () {
    $employee = EmployeeMaster::factory()->create();
    $slab = PerformanceSlabMaster::factory()->create([
        'name' => 'SLAB MID', 'min_percent' => 60, 'max_percent' => 79.99, 'incentive_amount' => 150,
    ]);

    Livewire::test(Edit::class)
        ->set('employee_id', $employee->id)
        ->set('period_year', 2026)
        ->set('period_month', 6)
        ->set('lines', [
            ['id' => null, 'smart_salary_kpi_id' => null, 'name' => 'A', 'polarity' => 'positive', 'max_points' => 10, 'points_awarded' => 10, 'sequence_no' => 1],
            ['id' => null, 'smart_salary_kpi_id' => null, 'name' => 'B', 'polarity' => 'positive', 'max_points' => 10, 'points_awarded' => 10, 'sequence_no' => 2],
            ['id' => null, 'smart_salary_kpi_id' => null, 'name' => 'PENALTY', 'polarity' => 'negative', 'max_points' => 5, 'points_awarded' => 5, 'sequence_no' => 3],
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $s = PerformanceScore::firstOrFail();
    // positive 20, negative 5, net 15, max (positive only) 20 → 75% → slab MID → 150
    expect((float) $s->total_positive)->toBe(20.0)
        ->and((float) $s->total_negative)->toBe(5.0)
        ->and((float) $s->net_points)->toBe(15.0)
        ->and((float) $s->max_points)->toBe(20.0)
        ->and((float) $s->achievement_percent)->toBe(75.0)
        ->and($s->performance_slab_id)->toBe($slab->id)
        ->and((float) $s->incentive_amount)->toBe(150.0)
        ->and($s->lines()->count())->toBe(3);
});

it('snapshots polarity and weight when adding a KPI, defaulting positive points to full weight', function () {
    $employee = EmployeeMaster::factory()->create();
    $kpi = SmartSalary::factory()->create(['polarity' => 'positive', 'weight' => 10]);

    $component = Livewire::test(Edit::class)
        ->set('employee_id', $employee->id)
        ->call('addKpi', $kpi->id);

    $lines = $component->get('lines');
    expect($lines)->toHaveCount(1)
        ->and($lines[0]['polarity'])->toBe('positive')
        ->and((float) $lines[0]['max_points'])->toBe(10.0)
        ->and((float) $lines[0]['points_awarded'])->toBe(10.0);
});

it('adds all active KPIs and does not duplicate', function () {
    SmartSalary::factory()->create(['polarity' => 'positive', 'weight' => 10]);
    SmartSalary::factory()->create(['polarity' => 'negative', 'weight' => 5]);

    $component = Livewire::test(Edit::class)->call('addAllKpis')->call('addAllKpis');

    expect($component->get('lines'))->toHaveCount(2);
});

it('pushes the computed incentive into the employee payroll for the period', function () {
    $employee = EmployeeMaster::factory()->create();
    PerformanceSlabMaster::factory()->create(['min_percent' => 100, 'max_percent' => null, 'incentive_amount' => 300]);

    $score = PerformanceScore::factory()->create(['employee_id' => $employee->id, 'period_year' => 2026, 'period_month' => 6]);
    $score->lines()->create(['smart_salary_kpi_id' => null, 'polarity' => 'positive', 'max_points' => 10, 'points_awarded' => 10, 'sequence_no' => 1]);

    Livewire::test(Edit::class, ['performanceScore' => $score])->call('pushToPayroll');

    $payroll = Payroll::where('employee_id', $employee->id)->where('period_year', 2026)->where('period_month', 6)->first();
    expect($payroll)->not->toBeNull()
        ->and((float) $payroll->incentive_amount)->toBe(300.0)
        ->and((float) $payroll->gross_amount)->toBe(300.0)
        ->and((float) $payroll->net_amount)->toBe(300.0);
});

it('prevents two scores for the same employee and period', function () {
    $employee = EmployeeMaster::factory()->create();
    PerformanceScore::factory()->create(['employee_id' => $employee->id, 'period_year' => 2026, 'period_month' => 6]);

    Livewire::test(Edit::class)
        ->set('employee_id', $employee->id)
        ->set('period_year', 2026)
        ->set('period_month', 6)
        ->call('save')
        ->assertHasErrors(['employee_id']);
});

it('deletes a score from the index', function () {
    $s = PerformanceScore::factory()->create();

    Livewire::test(Index::class)->call('delete', $s->id);

    expect(PerformanceScore::find($s->id))->toBeNull();
});
