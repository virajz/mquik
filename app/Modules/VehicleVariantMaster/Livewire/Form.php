<?php

namespace App\Modules\VehicleVariantMaster\Livewire;

use App\Concerns\CanQuickAddModel;
use App\Modules\FuelTypeMaster\Models\FuelTypeMaster;
use App\Modules\TransmissionTypeMaster\Models\TransmissionTypeMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    use CanQuickAddModel;

    public ?int $editingId = null;

    public ?int $model_id = null;

    public string $name = '';

    public ?int $transmission_type_id = null;

    public ?string $engine_cc = null;

    public ?int $fuel_type_id = null;

    public ?int $year = null;

    public bool $is_active = true;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'model_id' => ['required', 'integer', 'exists:vehicle_models,id'],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('vehicle_variants', 'name')
                    ->where(fn ($q) => $q->where('model_id', $this->model_id))
                    ->ignore($this->editingId),
            ],
            'transmission_type_id' => ['nullable', 'integer', Rule::exists('transmission_types', 'id')->where('is_active', true)],
            'engine_cc' => ['nullable', 'string', 'max:20'],
            'fuel_type_id' => ['nullable', 'integer', Rule::exists('fuel_types', 'id')->where('is_active', true)],
            'year' => ['nullable', 'integer', 'min:1980', 'max:'.(date('Y') + 1)],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[Computed]
    public function models()
    {
        return VehicleModelMaster::query()->with('brand:id,name')->where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function fuelTypes()
    {
        return FuelTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function transmissionTypes()
    {
        return TransmissionTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[On('vehicle-variant-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();
        if ($id === null) {
            return;
        }
        $r = VehicleVariantMaster::findOrFail($id);
        $this->editingId = $r->id;
        $this->model_id = $r->model_id;
        $this->name = $r->name;
        $this->transmission_type_id = $r->transmission_type_id;
        $this->engine_cc = $r->engine_cc;
        $this->fuel_type_id = $r->fuel_type_id;
        $this->year = $r->year;
        $this->is_active = $r->is_active;
        $this->notes = $r->notes;
    }

    /** Quick-add Model wizard writes the new id back to the model picker. */
    protected function quickModelTargetProperty(): string
    {
        return 'model_id';
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'vehicle_variant_master.update' : 'vehicle_variant_master.create');

        $data = $this->validate();
        if (isset($data['name'])) {
            $data['name'] = strtoupper($data['name']);
        }
        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }

        if ($this->editingId) {
            VehicleVariantMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Variant #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $r = VehicleVariantMaster::create($data);
            Flux::toast(text: 'Variant #'.$r->id.' created.', variant: 'success');
        }

        $this->dispatch('vehicle-variant-master:saved');
        $this->resetForm();
        Flux::modal('vehicle-variant-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->model_id = null;
        $this->name = '';
        $this->transmission_type_id = null;
        $this->engine_cc = null;
        $this->fuel_type_id = null;
        $this->year = null;
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('vehicle-variant-master::form');
    }
}
