<?php

namespace App\Modules\LateMemo\Livewire;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\LateMemo\Models\LateMemo;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public ?int $employee_id = null;

    public string $memo_date = '';

    public int $late_by_minutes = 30;

    public ?string $reason = null;

    public string $status = LateMemo::STATUS_ISSUED;

    public ?int $issued_by_employee_id = null;

    public ?string $decision_notes = null;

    #[On('late-memo:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            $this->memo_date = now()->format('Y-m-d');

            return;
        }

        $record = LateMemo::findOrFail($id);
        $this->editingId = $record->id;
        $this->employee_id = $record->employee_id;
        $this->memo_date = $record->memo_date?->format('Y-m-d') ?? '';
        $this->late_by_minutes = (int) $record->late_by_minutes;
        $this->reason = $record->reason;
        $this->status = $record->status;
        $this->issued_by_employee_id = $record->issued_by_employee_id;
        $this->decision_notes = $record->decision_notes;
    }

    protected function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'memo_date' => ['required', 'date_format:Y-m-d'],
            'late_by_minutes' => ['required', 'integer', 'min:0', 'max:720'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(array_keys(LateMemo::statuses()))],
            'issued_by_employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'decision_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function save(): void
    {
        $data = $this->validate();

        foreach (['reason', 'decision_notes'] as $k) {
            if (filled($data[$k] ?? null)) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        if ($data['status'] === LateMemo::STATUS_ISSUED && ! $this->editingId) {
            $data['issued_at'] = now();
        }

        if ($this->editingId) {
            LateMemo::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Late memo #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $record = LateMemo::create($data);
            Flux::toast(text: 'Late memo #'.$record->id.' issued.', variant: 'success');
        }

        $this->dispatch('late-memo:saved');
        $this->resetForm();
        Flux::modal('late-memo-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->employee_id = null;
        $this->memo_date = '';
        $this->late_by_minutes = 30;
        $this->reason = null;
        $this->status = LateMemo::STATUS_ISSUED;
        $this->issued_by_employee_id = null;
        $this->decision_notes = null;
    }

    public function render()
    {
        return view('late-memo::form');
    }
}
