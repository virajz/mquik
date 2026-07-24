<?php

namespace App\Modules\VehicleModelMaster\Livewire;

use App\Concerns\HasQuickCreate;
use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleSegmentMaster\Models\VehicleSegmentMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    use HasQuickCreate;

    public ?int $editingId = null;

    public ?int $brand_id = null;

    public string $brandSearch = '';

    public string $name = '';

    public ?int $vehicle_segment_id = null;

    public ?int $service_interval_km = null;

    public ?int $service_interval_months = null;

    public string $vehicleSegmentSearch = '';

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
            'vehicle_segment_id' => ['nullable', 'integer', Rule::exists('vehicle_segments', 'id')->where('is_active', true)],
            'service_interval_km' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'service_interval_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[Computed]
    public function brands()
    {
        return VehicleBrandMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function segments()
    {
        return VehicleSegmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
        $this->vehicle_segment_id = $r->vehicle_segment_id;
        $this->service_interval_km = $r->service_interval_km;
        $this->service_interval_months = $r->service_interval_months;
        $this->is_active = $r->is_active;
        $this->notes = $r->notes;
    }

    public function createBrand(): void
    {
        $this->quickCreate(
            modelClass: VehicleBrandMaster::class,
            targetProperty: 'brand_id',
            searchProperty: 'brandSearch',
            permission: 'vehicle_brand_master.create',
            label: 'Vehicle brand',
        );
    }

    public function createSegment(): void
    {
        $this->quickCreate(
            modelClass: VehicleSegmentMaster::class,
            targetProperty: 'vehicle_segment_id',
            searchProperty: 'vehicleSegmentSearch',
            permission: 'vehicle_segment_master.create',
            label: 'Vehicle segment',
        );
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'vehicle_model_master.update' : 'vehicle_model_master.create');

        $data = $this->validate();

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
        $this->vehicle_segment_id = null;
        $this->service_interval_km = null;
        $this->service_interval_months = null;
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('vehicle-model-master::form');
    }
}
