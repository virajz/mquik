<?php

namespace App\Modules\TimeSlotMaster\Livewire;

use App\Modules\TimeSlotMaster\Models\TimeSlotMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public ?string $code = null;

    public string $slot_start_time = '';

    public string $slot_end_time = '';

    /** Soft cap — the appointment form warns past this but still allows an override. */
    public int $max_vehicles_per_slot = 5;

    public int $buffer_minutes = 0;

    public bool $is_active = true;

    public ?string $notes = null;

    /**
     * Validation rules — defined as a method (not #[Validate] attributes)
     * so we can use Rule::unique with the editing record's ID for updates.
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255',
                Rule::unique('time_slots', 'name')->ignore($this->editingId),
            ],
            'code' => ['nullable', 'string', 'max:20',
                Rule::unique('time_slots', 'code')->ignore($this->editingId),
            ],
            'slot_start_time' => ['required', 'date_format:H:i'],
            'slot_end_time' => ['required', 'date_format:H:i', 'after:slot_start_time'],
            'max_vehicles_per_slot' => ['required', 'integer', 'min:1', 'max:999'],
            'buffer_minutes' => ['integer', 'min:0', 'max:480'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[On('time-slot-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $record = TimeSlotMaster::findOrFail($id);
        $this->editingId = $record->id;
        $this->name = $record->name;
        $this->code = $record->code;
        // Stored as H:i:s — the time inputs bind H:i.
        $this->slot_start_time = substr((string) $record->slot_start_time, 0, 5);
        $this->slot_end_time = substr((string) $record->slot_end_time, 0, 5);
        $this->max_vehicles_per_slot = (int) $record->max_vehicles_per_slot;
        $this->buffer_minutes = (int) $record->buffer_minutes;
        $this->is_active = $record->is_active;
        $this->notes = $record->notes;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'time_slot_master.update' : 'time_slot_master.create');

        $data = $this->validate();

        // Workshop convention: capital typing on textual fields.
        $skip = ['is_active'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            TimeSlotMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Time Slot #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $record = TimeSlotMaster::create($data);
            Flux::toast(text: 'Time Slot #'.$record->id.' created.', variant: 'success');
        }

        $this->dispatch('time-slot-master:saved');
        $this->resetForm();
        Flux::modal('time-slot-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = null;
        $this->slot_start_time = '';
        $this->slot_end_time = '';
        $this->max_vehicles_per_slot = 5;
        $this->buffer_minutes = 0;
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('time-slot-master::form');
    }
}
