<?php

namespace App\Modules\HolidayMaster\Livewire;

use App\Modules\HolidayMaster\Models\HolidayMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public ?string $code = null;

    public string $holiday_type = 'company';

    public ?string $holiday_date = null;

    public bool $is_recurring = false;

    public bool $is_active = true;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255',
                Rule::unique('holidays', 'name')->ignore($this->editingId),
            ],
            'code' => ['nullable', 'string', 'max:20',
                Rule::unique('holidays', 'code')->ignore($this->editingId),
            ],
            'holiday_type' => ['required', Rule::in(array_keys(HolidayMaster::holidayTypes()))],
            'holiday_date' => ['nullable', 'date'],
            'is_recurring' => ['boolean'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[On('holiday-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $record = HolidayMaster::findOrFail($id);
        $this->editingId = $record->id;
        $this->name = $record->name;
        $this->code = $record->code;
        $this->holiday_type = $record->holiday_type;
        $this->holiday_date = $record->holiday_date?->format('Y-m-d');
        $this->is_recurring = $record->is_recurring;
        $this->is_active = $record->is_active;
        $this->notes = $record->notes;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'holiday_master.update' : 'holiday_master.create');

        $data = $this->validate();

        // Workshop convention: capital typing on textual fields (skip enums/dates/flags).
        $skip = ['is_active', 'is_recurring', 'holiday_type', 'holiday_date'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            HolidayMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Holiday #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $record = HolidayMaster::create($data);
            Flux::toast(text: 'Holiday #'.$record->id.' created.', variant: 'success');
        }

        $this->dispatch('holiday-master:saved');
        $this->resetForm();
        Flux::modal('holiday-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = null;
        $this->holiday_type = 'company';
        $this->holiday_date = null;
        $this->is_recurring = false;
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('holiday-master::form');
    }
}
