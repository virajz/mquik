<?php

namespace App\Modules\PriorityMaster\Livewire;

use App\Modules\PriorityMaster\Models\PriorityMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public ?string $code = null;

    /** Severity order — lower sorts first (Normal 10 → High 20 → Urgent 30). */
    public int $sort_order = 0;

    public string $applies_to = PriorityMaster::APPLIES_BOTH;

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
                Rule::unique('priorities', 'name')->ignore($this->editingId),
            ],
            'code' => ['nullable', 'string', 'max:20',
                Rule::unique('priorities', 'code')->ignore($this->editingId),
            ],
            'sort_order' => ['integer', 'min:0', 'max:100000'],
            'applies_to' => ['required', Rule::in(array_keys(PriorityMaster::scopes()))],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[On('priority-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $record = PriorityMaster::findOrFail($id);
        $this->editingId = $record->id;
        $this->name = $record->name;
        $this->code = $record->code;
        $this->sort_order = (int) $record->sort_order;
        $this->applies_to = $record->applies_to;
        $this->is_active = $record->is_active;
        $this->notes = $record->notes;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'priority_master.update' : 'priority_master.create');

        $data = $this->validate();

        // Workshop convention: capital typing on textual fields.
        $skip = ['is_active', 'applies_to'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            PriorityMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Priority #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $record = PriorityMaster::create($data);
            Flux::toast(text: 'Priority #'.$record->id.' created.', variant: 'success');
        }

        $this->dispatch('priority-master:saved');
        $this->resetForm();
        Flux::modal('priority-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = null;
        $this->sort_order = 0;
        $this->applies_to = PriorityMaster::APPLIES_BOTH;
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('priority-master::form');
    }
}
