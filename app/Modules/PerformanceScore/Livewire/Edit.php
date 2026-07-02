<?php

namespace App\Modules\PerformanceScore\Livewire;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\Payroll\Models\Payroll;
use App\Modules\PerformanceScore\Models\PerformanceScore;
use App\Modules\PerformanceSlabMaster\Models\PerformanceSlabMaster;
use App\Modules\SmartSalary\Models\SmartSalary;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Performance Score')]
class Edit extends Component
{
    public ?int $editingId = null;

    public ?int $employee_id = null;

    public int $period_year;

    public int $period_month;

    public string $status = 'draft';

    public ?string $notes = null;

    #[Url(as: 'from-employee')]
    public ?int $fromEmployee = null;

    /** @var list<array<string, mixed>> */
    public array $lines = [];

    public function mount(?PerformanceScore $performanceScore = null): void
    {
        $this->period_year = (int) now()->format('Y');
        $this->period_month = (int) now()->format('n');

        if ($performanceScore && $performanceScore->exists) {
            $this->load($performanceScore);

            return;
        }

        if ($this->fromEmployee) {
            $emp = EmployeeMaster::find($this->fromEmployee);
            if ($emp) {
                $this->employee_id = $emp->id;
            }
        }
    }

    protected function load(PerformanceScore $s): void
    {
        $s->load('lines.kpi:id,name');

        $this->editingId = $s->id;
        $this->employee_id = $s->employee_id;
        $this->period_year = $s->period_year;
        $this->period_month = $s->period_month;
        $this->status = $s->status;
        $this->notes = $s->notes;

        $this->lines = $s->lines->map(fn ($l) => [
            'id' => $l->id,
            'smart_salary_kpi_id' => $l->smart_salary_kpi_id,
            'name' => $l->kpi?->name ?? 'KPI',
            'polarity' => $l->polarity,
            'max_points' => (float) $l->max_points,
            'points_awarded' => (float) $l->points_awarded,
            'sequence_no' => (int) $l->sequence_no,
        ])->all();
    }

    public function addKpi(int $kpiId): void
    {
        if (collect($this->lines)->firstWhere('smart_salary_kpi_id', $kpiId)) {
            return;
        }

        $kpi = SmartSalary::find($kpiId);
        if (! $kpi) {
            return;
        }

        $this->pushLine($kpi);
    }

    public function addAllKpis(): void
    {
        $existing = collect($this->lines)->pluck('smart_salary_kpi_id')->filter()->all();

        $kpis = SmartSalary::query()
            ->where('is_active', true)
            ->whereNotIn('id', $existing)
            ->orderByRaw("case when polarity = 'positive' then 0 else 1 end")
            ->orderBy('sort_order')
            ->get();

        foreach ($kpis as $kpi) {
            $this->pushLine($kpi);
        }
    }

    protected function pushLine(SmartSalary $kpi): void
    {
        $this->lines[] = [
            'id' => null,
            'smart_salary_kpi_id' => $kpi->id,
            'name' => $kpi->name,
            'polarity' => $kpi->polarity,
            'max_points' => (float) $kpi->weight,
            // positive KPIs default to their full points (achieved); penalties default to 0.
            'points_awarded' => $kpi->polarity === SmartSalary::POLARITY_POSITIVE ? (float) $kpi->weight : 0.0,
            'sequence_no' => count($this->lines) + 1,
        ];
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    protected function matchSlab(float $percent): ?PerformanceSlabMaster
    {
        $p = max(0.0, $percent);

        return PerformanceSlabMaster::query()
            ->where('is_active', true)
            ->where('min_percent', '<=', $p)
            ->where(fn ($q) => $q->whereNull('max_percent')->orWhere('max_percent', '>=', $p))
            ->orderByDesc('min_percent')
            ->first();
    }

    /**
     * @return array{positive: float, negative: float, net: float, max: float, percent: float, slab: ?PerformanceSlabMaster, incentive: float}
     */
    #[Computed]
    public function totals(): array
    {
        $positive = 0.0;
        $negative = 0.0;
        $max = 0.0;

        foreach ($this->lines as $row) {
            $points = (float) ($row['points_awarded'] ?? 0);
            if (($row['polarity'] ?? 'positive') === 'negative') {
                $negative += $points;
            } else {
                $positive += $points;
                $max += (float) ($row['max_points'] ?? 0);
            }
        }

        $net = round($positive - $negative, 2);
        $percent = $max > 0 ? round($net / $max * 100, 2) : 0.0;
        $slab = $this->matchSlab($percent);

        return [
            'positive' => round($positive, 2),
            'negative' => round($negative, 2),
            'net' => $net,
            'max' => round($max, 2),
            'percent' => $percent,
            'slab' => $slab,
            'incentive' => $slab ? (float) $slab->incentive_amount : 0.0,
        ];
    }

    protected function rules(): array
    {
        return [
            'employee_id' => [
                'required', 'integer', 'exists:employees,id',
                Rule::unique('performance_scores', 'employee_id')
                    ->where(fn ($q) => $q->where('period_year', $this->period_year)->where('period_month', $this->period_month))
                    ->ignore($this->editingId),
            ],
            'period_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
            'status' => ['required', Rule::in(array_keys(PerformanceScore::statuses()))],
            'notes' => ['nullable', 'string', 'max:2000'],

            'lines' => ['array'],
            'lines.*.polarity' => ['required', 'in:positive,negative'],
            'lines.*.max_points' => ['numeric', 'min:0'],
            'lines.*.points_awarded' => ['numeric', 'min:0'],
        ];
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'employee_code'])
            ->map(fn ($e) => ['id' => $e->id, 'label' => $e->name.' · '.$e->employee_code]);
    }

    #[Computed]
    public function kpis()
    {
        return SmartSalary::query()->where('is_active', true)
            ->orderByRaw("case when polarity = 'positive' then 0 else 1 end")
            ->orderBy('sort_order')
            ->get(['id', 'name', 'polarity', 'weight']);
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'performance_score.update' : 'performance_score.create');

        $data = $this->validate();
        $lines = $data['lines'] ?? [];
        unset($data['lines']);

        $totals = $this->totals();
        $data['total_positive'] = $totals['positive'];
        $data['total_negative'] = $totals['negative'];
        $data['net_points'] = $totals['net'];
        $data['max_points'] = $totals['max'];
        $data['achievement_percent'] = $totals['percent'];
        $data['performance_slab_id'] = $totals['slab']?->id;
        $data['incentive_amount'] = $totals['incentive'];

        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }

        $isCreate = $this->editingId === null;

        $score = DB::transaction(function () use ($data, $lines, $isCreate) {
            if ($isCreate) {
                $row = PerformanceScore::create($data);
                $this->editingId = $row->id;
            } else {
                $row = PerformanceScore::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncLines($row, $lines);

            return $row;
        });

        Flux::toast(text: 'Performance score #'.$score->id.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        if ($isCreate) {
            return redirect()->route('performance-score.edit', $score->id);
        }

        return redirect()->route('performance-score.index');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncLines(PerformanceScore $score, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $local = $this->lines[$i] ?? [];
            $payload = [
                'smart_salary_kpi_id' => $local['smart_salary_kpi_id'] ?? null,
                'polarity' => $row['polarity'],
                'max_points' => (float) ($row['max_points'] ?? 0),
                'points_awarded' => (float) ($row['points_awarded'] ?? 0),
                'sequence_no' => $i + 1,
            ];

            if (! empty($local['id'])) {
                $ex = $score->lines()->whereKey($local['id'])->first();
                if ($ex) {
                    $ex->update($payload);
                    $keptIds[] = $ex->id;

                    continue;
                }
            }

            $created = $score->lines()->create($payload);
            $keptIds[] = $created->id;
            $this->lines[$i]['id'] = $created->id;
        }

        $score->lines()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * Feed the computed incentive into the employee's payroll for this period.
     */
    public function pushToPayroll(): void
    {
        $this->authorize('performance_score.update');

        if (! $this->editingId || ! $this->employee_id) {
            return;
        }

        $incentive = $this->totals()['incentive'];

        $payroll = Payroll::firstOrNew([
            'employee_id' => $this->employee_id,
            'period_year' => $this->period_year,
            'period_month' => $this->period_month,
        ]);

        $payroll->incentive_amount = $incentive;
        $payroll->gross_amount = (float) $payroll->basic_amount + (float) $payroll->hra_amount
            + (float) $payroll->da_amount + (float) $payroll->allowances_amount + $incentive;
        $payroll->net_amount = (float) $payroll->gross_amount - (float) $payroll->deductions_amount;
        $payroll->save();

        Flux::toast(text: 'Incentive ₹'.number_format($incentive, 2).' pushed to payroll for '.$this->periodLabel().'.', variant: 'success');
    }

    protected function periodLabel(): string
    {
        return (PerformanceScore::months()[$this->period_month] ?? '?').' '.$this->period_year;
    }

    public function render()
    {
        return view('performance-score::edit');
    }
}
