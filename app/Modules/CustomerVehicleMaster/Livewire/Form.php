<?php

namespace App\Modules\CustomerVehicleMaster\Livewire;

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\VehicleColorMaster\Models\VehicleColorMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public ?int $customer_id = null;

    public ?int $model_id = null;

    public ?int $variant_id = null;

    public ?int $color_id = null;

    public string $registration_no = '';

    public ?int $year_of_manufacture = null;

    public ?string $vin = null;

    public ?string $engine_no = null;

    public ?int $odometer_km = null;

    public ?string $insurance_expiry = null;

    public ?string $puc_expiry = null;

    public bool $is_active = true;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'model_id' => ['required', 'integer', 'exists:vehicle_models,id'],
            'variant_id' => ['nullable', 'integer', 'exists:vehicle_variants,id'],
            'color_id' => ['nullable', 'integer', 'exists:vehicle_colors,id'],
            'registration_no' => [
                'required', 'string', 'max:20',
                Rule::unique('customer_vehicles', 'registration_no')->ignore($this->editingId),
            ],
            'year_of_manufacture' => ['nullable', 'integer', 'min:1980', 'max:'.(int) date('Y') + 1],
            'vin' => [
                'nullable', 'string', 'size:17',
                Rule::unique('customer_vehicles', 'vin')->ignore($this->editingId),
            ],
            'engine_no' => ['nullable', 'string', 'max:30'],
            'odometer_km' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'insurance_expiry' => ['nullable', 'date'],
            'puc_expiry' => ['nullable', 'date'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[Computed]
    public function customers()
    {
        return CustomerMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'phone']);
    }

    #[Computed]
    public function models()
    {
        return VehicleModelMaster::query()->with('brand:id,name')->where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function variants()
    {
        if (! $this->model_id) {
            return collect();
        }

        return VehicleVariantMaster::query()
            ->where('model_id', $this->model_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function colors()
    {
        return VehicleColorMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'hex_code']);
    }

    public function updatedModelId(): void
    {
        // Reset variant when model changes — old variant would belong to a different model
        $this->variant_id = null;
    }

    #[On('customer-vehicle-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();
        if ($id === null) {
            return;
        }

        $r = CustomerVehicleMaster::findOrFail($id);
        $this->editingId = $r->id;
        $this->customer_id = $r->customer_id;
        $this->model_id = $r->model_id;
        $this->variant_id = $r->variant_id;
        $this->color_id = $r->color_id;
        $this->registration_no = $r->registration_no;
        $this->year_of_manufacture = $r->year_of_manufacture;
        $this->vin = $r->vin;
        $this->engine_no = $r->engine_no;
        $this->odometer_km = $r->odometer_km;
        $this->insurance_expiry = $r->insurance_expiry?->format('Y-m-d');
        $this->puc_expiry = $r->puc_expiry?->format('Y-m-d');
        $this->is_active = $r->is_active;
        $this->notes = $r->notes;
    }

    public function save(): void
    {
        $data = $this->validate();

        // Uppercase the textual fields
        foreach (['registration_no', 'vin', 'engine_no', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        if ($this->editingId) {
            CustomerVehicleMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Vehicle #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $r = CustomerVehicleMaster::create($data);
            Flux::toast(text: 'Vehicle #'.$r->id.' created.', variant: 'success');
        }

        $this->dispatch('customer-vehicle-master:saved');
        $this->resetForm();
        Flux::modal('customer-vehicle-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->customer_id = null;
        $this->model_id = null;
        $this->variant_id = null;
        $this->color_id = null;
        $this->registration_no = '';
        $this->year_of_manufacture = null;
        $this->vin = null;
        $this->engine_no = null;
        $this->odometer_km = null;
        $this->insurance_expiry = null;
        $this->puc_expiry = null;
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('customer-vehicle-master::form');
    }
}
