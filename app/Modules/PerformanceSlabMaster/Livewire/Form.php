<?php

namespace App\Modules\PerformanceSlabMaster\Livewire;

use App\Modules\PerformanceSlabMaster\Models\PerformanceSlabMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public ?string $code = null;

    public float $min_percent = 0;

    public ?float $max_percent = null;

    public float $incentive_amount = 0;

    public bool $is_active = true;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255',
                Rule::unique('performance_slabs', 'name')->ignore($this->editingId),
            ],
            'code' => ['nullable', 'string', 'max:20',
                Rule::unique('performance_slabs', 'code')->ignore($this->editingId),
            ],
            'min_percent' => ['numeric', 'min:0', 'max:1000'],
            'max_percent' => ['nullable', 'numeric', 'min:0', 'max:1000', 'gte:min_percent'],
            'incentive_amount' => ['numeric', 'min:0'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[On('performance-slab-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $record = PerformanceSlabMaster::findOrFail($id);
        $this->editingId = $record->id;
        $this->name = $record->name;
        $this->code = $record->code;
        $this->min_percent = (float) $record->min_percent;
        $this->max_percent = $record->max_percent === null ? null : (float) $record->max_percent;
        $this->incentive_amount = (float) $record->incentive_amount;
        $this->is_active = $record->is_active;
        $this->notes = $record->notes;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'performance_slab_master.update' : 'performance_slab_master.create');

        $data = $this->validate();

        // Workshop convention: capital typing on textual fields.
        $skip = ['is_active'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            PerformanceSlabMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Performance Slab #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $record = PerformanceSlabMaster::create($data);
            Flux::toast(text: 'Performance Slab #'.$record->id.' created.', variant: 'success');
        }

        $this->dispatch('performance-slab-master:saved');
        $this->resetForm();
        Flux::modal('performance-slab-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = null;
        $this->min_percent = 0;
        $this->max_percent = null;
        $this->incentive_amount = 0;
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('performance-slab-master::form');
    }
}
