<?php

namespace App\Modules\VehicleBrandMaster\Livewire;

use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public ?string $code = null;

    public ?string $country = null;

    public bool $is_active = true;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255',
                Rule::unique('vehicle_brands', 'name')->ignore($this->editingId),
            ],
            'code' => ['nullable', 'string', 'max:20',
                Rule::unique('vehicle_brands', 'code')->ignore($this->editingId),
            ],
            'country' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[On('vehicle-brand-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $r = VehicleBrandMaster::findOrFail($id);
        $this->editingId = $r->id;
        $this->name = $r->name;
        $this->code = $r->code;
        $this->country = $r->country;
        $this->is_active = $r->is_active;
        $this->notes = $r->notes;
    }

    public function save(): void
    {
        $data = $this->validate();

        $skip = ['is_active'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            VehicleBrandMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Vehicle brand #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $r = VehicleBrandMaster::create($data);
            Flux::toast(text: 'Vehicle brand #'.$r->id.' created.', variant: 'success');
        }

        $this->dispatch('vehicle-brand-master:saved');
        $this->resetForm();
        Flux::modal('vehicle-brand-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = null;
        $this->country = null;
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('vehicle-brand-master::form');
    }
}
