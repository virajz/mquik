<?php

namespace App\Modules\VehicleVariantMaster\Livewire;

use App\Concerns\CanQuickAddModel;
use App\Concerns\SearchesPickerOptions;
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
    use SearchesPickerOptions;

    /** Search term for the server-backed models picker. */
    public string $modelSearch = '';

    public ?int $editingId = null;

    public ?int $model_id = null;

    public string $name = '';

    public ?int $transmission_type_id = null;

    public ?string $engine_cc = null;

    public ?int $fuel_type_id = null;

    public ?int $year = null;

    public ?int $service_interval_km = null;

    public ?int $service_interval_months = null;

    public bool $is_active = true;

    public ?string $notes = null;

    /** Optional source variant to copy the spare-compatibility list from (create only). */
    public ?int $copy_from_variant_id = null;

    /** Search term for the copy-from variant picker. */
    public string $copyFromSearch = '';

    /**
     * Spares queued to attach to the new variant, copied from the source and
     * prunable before save.
     *
     * @var array<int, array{id:int, name:string, spare_code:?string}>
     */
    public array $clonedSpares = [];

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
            'service_interval_km' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'service_interval_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[Computed]
    public function models()
    {
        return $this->pickerOptions(
            query: VehicleModelMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'brand.name'],
            term: $this->modelSearch,
            selected: $this->model_id,
            columns: ['id', 'name', 'brand_id'],
            limit: 30,
        );
    }

    /** Variants to copy a spare list from — searched by brand, model or variant. */
    #[Computed]
    public function sourceVariants()
    {
        return $this->pickerOptions(
            query: VehicleVariantMaster::query()
                ->with('model.brand:id,name')
                ->where('is_active', true)
                ->when($this->editingId, fn ($q, $id) => $q->whereKeyNot($id))
                ->orderBy('name'),
            searchColumns: ['name', 'model.name', 'model.brand.name'],
            term: $this->copyFromSearch,
            selected: $this->copy_from_variant_id,
            columns: ['id', 'name', 'model_id'],
            limit: 30,
        );
    }

    /** Picking a source loads its spares into a prunable list. */
    public function updatedCopyFromVariantId(): void
    {
        if (! $this->copy_from_variant_id) {
            $this->clonedSpares = [];

            return;
        }

        $source = VehicleVariantMaster::find($this->copy_from_variant_id);

        $this->clonedSpares = $source
            ? $source->spares()->orderBy('name')->get(['spares.id', 'name', 'spare_code'])
                ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'spare_code' => $s->spare_code])
                ->all()
            : [];
    }

    public function removeClonedSpare(int $spareId): void
    {
        $this->clonedSpares = array_values(array_filter(
            $this->clonedSpares,
            fn ($s) => $s['id'] !== $spareId,
        ));
    }

    /** The picked model's interval, shown as the inherited default. */
    #[Computed]
    public function modelInterval(): array
    {
        $model = $this->model_id ? VehicleModelMaster::find($this->model_id) : null;

        return ['km' => $model?->service_interval_km, 'months' => $model?->service_interval_months];
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
        $this->service_interval_km = $r->service_interval_km;
        $this->service_interval_months = $r->service_interval_months;
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

            // Copy the pruned spare-compatibility list onto the new variant.
            if ($this->clonedSpares !== []) {
                $r->spares()->sync(array_column($this->clonedSpares, 'id'));
            }

            $copied = count($this->clonedSpares);
            Flux::toast(
                text: 'Variant #'.$r->id.' created'.($copied ? ' with '.$copied.' spare'.($copied === 1 ? '' : 's').' copied.' : '.'),
                variant: 'success',
            );
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
        $this->service_interval_km = null;
        $this->service_interval_months = null;
        $this->is_active = true;
        $this->notes = null;
        $this->copy_from_variant_id = null;
        $this->copyFromSearch = '';
        $this->clonedSpares = [];
    }

    public function render()
    {
        return view('vehicle-variant-master::form');
    }
}
