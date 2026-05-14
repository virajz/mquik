<?php

namespace App\Modules\LeaveManagement\Livewire;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\LeaveManagement\Models\LeaveManagement;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public ?int $employee_id = null;

    public string $leave_type = 'CL';

    public string $from_date = '';

    public string $to_date = '';

    public float $days_count = 1.0;

    public ?string $reason = null;

    public string $status = LeaveManagement::STATUS_PENDING;

    public ?int $approved_by_employee_id = null;

    public ?string $decision_notes = null;

    #[On('leave-management:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $record = LeaveManagement::findOrFail($id);
        $this->editingId = $record->id;
        $this->employee_id = $record->employee_id;
        $this->leave_type = $record->leave_type;
        $this->from_date = $record->from_date?->format('Y-m-d') ?? '';
        $this->to_date = $record->to_date?->format('Y-m-d') ?? '';
        $this->days_count = (float) $record->days_count;
        $this->reason = $record->reason;
        $this->status = $record->status;
        $this->approved_by_employee_id = $record->approved_by_employee_id;
        $this->decision_notes = $record->decision_notes;
    }

    protected function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'leave_type' => ['required', Rule::in(array_keys(LeaveManagement::leaveTypes()))],
            'from_date' => ['required', 'date_format:Y-m-d'],
            'to_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'days_count' => ['required', 'numeric', 'min:0.5', 'max:365'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(array_keys(LeaveManagement::statuses()))],
            'approved_by_employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'decision_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function save(): void
    {
        $data = $this->validate();

        foreach (['reason', 'decision_notes'] as $k) {
            if (filled($data[$k] ?? null)) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        // Stamp approved_at when status is approved/rejected.
        if (in_array($data['status'], [LeaveManagement::STATUS_APPROVED, LeaveManagement::STATUS_REJECTED], true)) {
            $data['approved_at'] = now();
        }

        if ($this->editingId) {
            LeaveManagement::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Leave request #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $record = LeaveManagement::create($data);
            Flux::toast(text: 'Leave request #'.$record->id.' created.', variant: 'success');
        }

        $this->dispatch('leave-management:saved');
        $this->resetForm();
        Flux::modal('leave-management-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->employee_id = null;
        $this->leave_type = 'CL';
        $this->from_date = '';
        $this->to_date = '';
        $this->days_count = 1.0;
        $this->reason = null;
        $this->status = LeaveManagement::STATUS_PENDING;
        $this->approved_by_employee_id = null;
        $this->decision_notes = null;
    }

    public function render()
    {
        return view('leave-management::form');
    }
}
