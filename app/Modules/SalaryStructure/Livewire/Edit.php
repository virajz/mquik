<?php

namespace App\Modules\SalaryStructure\Livewire;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\SalaryComponentMaster\Models\SalaryComponentMaster;
use App\Modules\SalaryStructure\Models\SalaryStructure;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Salary Structure')]
class Edit extends Component
{
    public ?int $editingId = null;

    public ?int $employee_id = null;

    public ?string $effective_from = null;

    public string $status = 'draft';

    public float $basic_salary = 0;

    public ?string $notes = null;

    #[Url(as: 'from-employee')]
    public ?int $fromEmployee = null;

    /** @var list<array<string, mixed>> */
    public array $lines = [];

    public function mount(?SalaryStructure $salaryStructure = null): void
    {
        if ($salaryStructure && $salaryStructure->exists) {
            $this->load($salaryStructure);

            return;
        }

        $this->effective_from = now()->startOfMonth()->format('Y-m-d');

        if ($this->fromEmployee) {
            $emp = EmployeeMaster::find($this->fromEmployee);
            if ($emp) {
                $this->employee_id = $emp->id;
            }
        }
    }

    protected function load(SalaryStructure $s): void
    {
        $s->load('lines.component:id,name');

        $this->editingId = $s->id;
        $this->employee_id = $s->employee_id;
        $this->effective_from = $s->effective_from?->format('Y-m-d');
        $this->status = $s->status;
        $this->basic_salary = (float) $s->basic_salary;
        $this->notes = $s->notes;

        $this->lines = $s->lines->map(fn ($l) => [
            'id' => $l->id,
            'salary_component_id' => $l->salary_component_id,
            'name' => $l->component?->name ?? 'Component',
            'component_type' => $l->component_type,
            'calc_method' => $l->calc_method,
            'value' => (float) $l->value,
            'sequence_no' => (int) $l->sequence_no,
        ])->all();
    }

    public function addComponent(int $componentId): void
    {
        if (collect($this->lines)->firstWhere('salary_component_id', $componentId)) {
            return; // already added
        }

        $c = SalaryComponentMaster::find($componentId);
        if (! $c) {
            return;
        }

        $this->lines[] = [
            'id' => null,
            'salary_component_id' => $c->id,
            'name' => $c->name,
            'component_type' => $c->component_type,
            'calc_method' => $c->calc_method,
            'value' => (float) $c->default_value,
            'sequence_no' => count($this->lines) + 1,
        ];
    }

    public function addAllComponents(): void
    {
        $existing = collect($this->lines)->pluck('salary_component_id')->filter()->all();

        $components = SalaryComponentMaster::query()
            ->where('is_active', true)
            ->whereRaw("upper(coalesce(code, '')) <> 'BASIC'")
            ->whereNotIn('id', $existing)
            ->orderByRaw("case when component_type = 'earning' then 0 else 1 end")
            ->orderBy('name')
            ->get();

        foreach ($components as $c) {
            $this->lines[] = [
                'id' => null,
                'salary_component_id' => $c->id,
                'name' => $c->name,
                'component_type' => $c->component_type,
                'calc_method' => $c->calc_method,
                'value' => (float) $c->default_value,
                'sequence_no' => count($this->lines) + 1,
            ];
        }
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function lineAmount(array $row, float $basic): float
    {
        $value = (float) ($row['value'] ?? 0);

        return ($row['calc_method'] ?? 'fixed') === 'percent_of_basic'
            ? round($basic * $value / 100, 2)
            : round($value, 2);
    }

    /**
     * @return array{basic: float, earnings: float, gross: float, deductions: float, net: float}
     */
    #[Computed]
    public function totals(): array
    {
        $basic = (float) $this->basic_salary;
        $earnings = 0.0;
        $deductions = 0.0;

        foreach ($this->lines as $row) {
            $amount = $this->lineAmount($row, $basic);
            if (($row['component_type'] ?? 'earning') === 'deduction') {
                $deductions += $amount;
            } else {
                $earnings += $amount;
            }
        }

        $gross = round($basic + $earnings, 2);
        $deductions = round($deductions, 2);

        return [
            'basic' => round($basic, 2),
            'earnings' => round($earnings, 2),
            'gross' => $gross,
            'deductions' => $deductions,
            'net' => round($gross - $deductions, 2),
        ];
    }

    protected function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'effective_from' => ['required', 'date'],
            'status' => ['required', Rule::in(array_keys(SalaryStructure::statuses()))],
            'basic_salary' => ['numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'lines' => ['array'],
            'lines.*.component_type' => ['required', 'in:earning,deduction'],
            'lines.*.calc_method' => ['required', 'in:fixed,percent_of_basic'],
            'lines.*.value' => ['numeric', 'min:0'],
        ];
    }

    #[Computed]
    public function employees()
    {
        $rows = EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'employee_code']);

        return $rows->map(fn ($e) => [
            'id' => $e->id,
            'label' => $e->name.' · '.$e->employee_code,
        ]);
    }

    #[Computed]
    public function components()
    {
        return SalaryComponentMaster::query()
            ->where('is_active', true)
            ->whereRaw("upper(coalesce(code, '')) <> 'BASIC'")
            ->orderByRaw("case when component_type = 'earning' then 0 else 1 end")
            ->orderBy('name')
            ->get(['id', 'name', 'component_type']);
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'salary_structure.update' : 'salary_structure.create');

        $data = $this->validate();
        $lines = $data['lines'] ?? [];
        unset($data['lines']);

        $totals = $this->totals();
        $data['gross_earnings'] = $totals['gross'];
        $data['total_deductions'] = $totals['deductions'];
        $data['net_salary'] = $totals['net'];

        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }

        $isCreate = $this->editingId === null;

        $structure = DB::transaction(function () use ($data, $lines, $isCreate) {
            if ($isCreate) {
                $row = SalaryStructure::create($data);
                $this->editingId = $row->id;
            } else {
                $row = SalaryStructure::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncLines($row, $lines);

            return $row;
        });

        Flux::toast(text: 'Salary structure #'.$structure->id.($isCreate ? ' created.' : ' updated.'), variant: 'success');

        if ($isCreate) {
            return redirect()->route('salary-structure.edit', $structure->id);
        }

        return redirect()->route('salary-structure.index');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncLines(SalaryStructure $structure, array $rows): void
    {
        $basic = (float) $this->basic_salary;
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $local = $this->lines[$i] ?? [];
            $payload = [
                'salary_component_id' => $local['salary_component_id'] ?? null,
                'component_type' => $row['component_type'],
                'calc_method' => $row['calc_method'],
                'value' => (float) ($row['value'] ?? 0),
                'amount' => $this->lineAmount($row, $basic),
                'sequence_no' => $i + 1,
            ];

            if (! empty($local['id'])) {
                $ex = $structure->lines()->whereKey($local['id'])->first();
                if ($ex) {
                    $ex->update($payload);
                    $keptIds[] = $ex->id;

                    continue;
                }
            }

            $created = $structure->lines()->create($payload);
            $keptIds[] = $created->id;
            $this->lines[$i]['id'] = $created->id;
        }

        $structure->lines()->whereNotIn('id', $keptIds)->delete();
    }

    public function render()
    {
        return view('salary-structure::edit');
    }
}
