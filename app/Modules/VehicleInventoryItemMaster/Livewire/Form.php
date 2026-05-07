<?php

namespace App\Modules\VehicleInventoryItemMaster\Livewire;

use App\Modules\VehicleInventoryItemMaster\Models\VehicleInventoryItemMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public ?string $code = null;

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
                Rule::unique('vehicle_inventory_items', 'name')->ignore($this->editingId),
            ],
            'code' => ['nullable', 'string', 'max:20',
                Rule::unique('vehicle_inventory_items', 'code')->ignore($this->editingId),
            ],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[On('vehicle-inventory-item-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $record = VehicleInventoryItemMaster::findOrFail($id);
        $this->editingId = $record->id;
        $this->name = $record->name;
        $this->code = $record->code;
        $this->is_active = $record->is_active;
        $this->notes = $record->notes;
    }

    public function save(): void
    {
        $data = $this->validate();

        // Workshop convention: capital typing on textual fields.
        $skip = ['is_active'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            VehicleInventoryItemMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Vehicle inventory item #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $record = VehicleInventoryItemMaster::create($data);
            Flux::toast(text: 'Vehicle inventory item #'.$record->id.' created.', variant: 'success');
        }

        $this->dispatch('vehicle-inventory-item-master:saved');
        $this->resetForm();
        Flux::modal('vehicle-inventory-item-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = null;
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('vehicle-inventory-item-master::form');
    }
}
