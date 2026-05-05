<?php

namespace App\Modules\VehicleVariantMaster\Livewire;

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

    public ?int $model_id = null;

    public string $name = '';

    public ?string $transmission = null;

    public ?string $engine_cc = null;

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
            'transmission' => ['nullable', 'in:manual,automatic,amt,cvt,dct'],
            'engine_cc' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[Computed]
    public function models()
    {
        return VehicleModelMaster::query()->with('brand:id,name')->where('is_active', true)->orderBy('name')->get();
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
        $this->transmission = $r->transmission;
        $this->engine_cc = $r->engine_cc;
        $this->is_active = $r->is_active;
        $this->notes = $r->notes;
    }

    public function save(): void
    {
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
        $this->transmission = null;
        $this->engine_cc = null;
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('vehicle-variant-master::form');
    }
}
