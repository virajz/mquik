<?php

namespace App\Modules\VehicleColorMaster\Livewire;

use App\Modules\VehicleColorMaster\Models\VehicleColorMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public ?string $hex_code = null;

    public bool $is_active = true;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('vehicle_colors', 'name')->ignore($this->editingId)],
            'hex_code' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[On('vehicle-color-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();
        if ($id === null) {
            return;
        }
        $r = VehicleColorMaster::findOrFail($id);
        $this->editingId = $r->id;
        $this->name = $r->name;
        $this->hex_code = $r->hex_code;
        $this->is_active = $r->is_active;
        $this->notes = $r->notes;
    }

    public function save(): void
    {
        $data = $this->validate();
        // hex_code stays as-is (case sensitive); name + notes uppercased
        foreach (['name', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }
        // Normalize hex to uppercase letters
        if (isset($data['hex_code'])) {
            $data['hex_code'] = strtoupper($data['hex_code']);
        }

        if ($this->editingId) {
            VehicleColorMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Color #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $r = VehicleColorMaster::create($data);
            Flux::toast(text: 'Color #'.$r->id.' created.', variant: 'success');
        }

        $this->dispatch('vehicle-color-master:saved');
        $this->resetForm();
        Flux::modal('vehicle-color-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->hex_code = null;
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('vehicle-color-master::form');
    }
}
