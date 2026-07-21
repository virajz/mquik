<?php

namespace App\Modules\DistanceSlabMaster\Livewire;

use App\Modules\DistanceSlabMaster\Models\DistanceSlabMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public ?string $code = null;

    public int $min_km = 0;

    public ?int $max_km = null;

    public string $charge_amount = '0';

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
                Rule::unique('distance_slabs', 'name')->ignore($this->editingId),
            ],
            'code' => ['nullable', 'string', 'max:20',
                Rule::unique('distance_slabs', 'code')->ignore($this->editingId),
            ],
            'min_km' => ['required', 'integer', 'min:0', 'max:9999'],
            'max_km' => ['nullable', 'integer', 'min:0', 'max:9999', 'gte:min_km'],
            'charge_amount' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[On('distance-slab-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $record = DistanceSlabMaster::findOrFail($id);
        $this->editingId = $record->id;
        $this->name = $record->name;
        $this->code = $record->code;
        $this->min_km = (int) $record->min_km;
        $this->max_km = $record->max_km;
        $this->charge_amount = (string) $record->charge_amount;
        $this->is_active = $record->is_active;
        $this->notes = $record->notes;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'distance_slab_master.update' : 'distance_slab_master.create');

        $data = $this->validate();

        // Workshop convention: capital typing on textual fields.
        $skip = ['is_active', 'charge_amount'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            DistanceSlabMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Distance Slab #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $record = DistanceSlabMaster::create($data);
            Flux::toast(text: 'Distance Slab #'.$record->id.' created.', variant: 'success');
        }

        $this->dispatch('distance-slab-master:saved');
        $this->resetForm();
        Flux::modal('distance-slab-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = null;
        $this->min_km = 0;
        $this->max_km = null;
        $this->charge_amount = '0';
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('distance-slab-master::form');
    }
}
