<?php

namespace App\Modules\Payroll\Livewire;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\Payroll\Models\Payroll;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Payroll')]
class Edit extends Component
{
    public ?int $editingId = null;

    public ?int $employee_id = null;

    public int $period_year = 2026;

    public int $period_month = 1;

    public float $basic_amount = 0;

    public float $hra_amount = 0;

    public float $da_amount = 0;

    public float $allowances_amount = 0;

    public float $incentive_amount = 0;

    public float $deductions_amount = 0;

    public ?string $payment_date = null;

    public string $status = Payroll::STATUS_DRAFT;

    public ?string $notes = null;

    public function mount(?Payroll $payroll = null): void
    {
        $now = now();
        $this->period_year = (int) $now->format('Y');
        $this->period_month = (int) $now->format('n');

        if ($payroll && $payroll->exists) {
            $this->load($payroll);
        }
    }

    protected function load(Payroll $record): void
    {
        $this->editingId = $record->id;
        $this->employee_id = $record->employee_id;
        $this->period_year = $record->period_year;
        $this->period_month = $record->period_month;
        $this->basic_amount = (float) $record->basic_amount;
        $this->hra_amount = (float) $record->hra_amount;
        $this->da_amount = (float) $record->da_amount;
        $this->allowances_amount = (float) $record->allowances_amount;
        $this->incentive_amount = (float) $record->incentive_amount;
        $this->deductions_amount = (float) $record->deductions_amount;
        $this->payment_date = $record->payment_date?->format('Y-m-d');
        $this->status = $record->status;
        $this->notes = $record->notes;
    }

    protected function rules(): array
    {
        return [
            'employee_id' => [
                'required', 'integer',
                Rule::exists('employees', 'id')->where('is_active', true),
                Rule::unique('payrolls', 'employee_id')
                    ->where(fn ($q) => $q->where('period_year', $this->period_year)->where('period_month', $this->period_month))
                    ->ignore($this->editingId),
            ],
            'period_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
            'basic_amount' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'hra_amount' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'da_amount' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'allowances_amount' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'incentive_amount' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'deductions_amount' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'payment_date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['required', Rule::in(array_keys(Payroll::statuses()))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function grossPreview(): float
    {
        return $this->basic_amount + $this->hra_amount + $this->da_amount + $this->allowances_amount + $this->incentive_amount;
    }

    #[Computed]
    public function netPreview(): float
    {
        return $this->grossPreview() - $this->deductions_amount;
    }

    public function save()
    {
        $data = $this->validate();

        $data['gross_amount'] = $data['basic_amount'] + $data['hra_amount'] + $data['da_amount'] + $data['allowances_amount'] + $data['incentive_amount'];
        $data['net_amount'] = $data['gross_amount'] - $data['deductions_amount'];

        if (filled($data['notes'] ?? null)) {
            $data['notes'] = strtoupper($data['notes']);
        }

        if ($this->editingId) {
            Payroll::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Payroll #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $record = Payroll::create($data);
            Flux::toast(text: 'Payroll #'.$record->id.' created.', variant: 'success');
        }

        return redirect()->route('payroll.index');
    }

    public function render()
    {
        return view('payroll::edit');
    }
}
