<?php

namespace App\Modules\VehicleModelMaster\Livewire;

use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public ?int $brand_id = null;

    public string $name = '';

    public ?string $segment = null;

    public ?string $fuel_type = null;

    public bool $is_active = true;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'brand_id' => ['required', 'integer', 'exists:vehicle_brands,id'],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('vehicle_models', 'name')
                    ->where(fn ($q) => $q->where('brand_id', $this->brand_id))
                    ->ignore($this->editingId),
            ],
            'segment' => ['nullable', 'in:hatchback,sedan,suv,muv,pickup,commercial'],
            'fuel_type' => ['nullable', 'in:petrol,diesel,cng,electric,hybrid'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[Computed]
    public function brands()
    {
        return VehicleBrandMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[On('vehicle-model-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();
        if ($id === null) {
            return;
        }

        $r = VehicleModelMaster::findOrFail($id);
        $this->editingId = $r->id;
        $this->brand_id = $r->brand_id;
        $this->name = $r->name;
        $this->segment = $r->segment;
        $this->fuel_type = $r->fuel_type;
        $this->is_active = $r->is_active;
        $this->notes = $r->notes;
    }

    public function save(): void
    {
        $data = $this->validate();

        // Uppercase only the textual fields, leave enums + booleans + brand_id alone
        foreach (['name', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        if ($this->editingId) {
            VehicleModelMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Model #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $r = VehicleModelMaster::create($data);
            Flux::toast(text: 'Model #'.$r->id.' created.', variant: 'success');
        }

        $this->dispatch('vehicle-model-master:saved');
        $this->resetForm();
        Flux::modal('vehicle-model-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->brand_id = null;
        $this->name = '';
        $this->segment = null;
        $this->fuel_type = null;
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('vehicle-model-master::form');
    }
}
