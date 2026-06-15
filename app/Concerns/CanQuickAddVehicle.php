<?php

namespace App\Concerns;

use App\Modules\FuelTypeMaster\Models\FuelTypeMaster;
use App\Modules\TransmissionTypeMaster\Models\TransmissionTypeMaster;
use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;

/**
 * Vehicle (Variant) quick-add wizard for any picker that selects a variant
 * (e.g. CustomerVehicleMaster Edit's `variant_id`).
 *
 * Walks brand → model → variant in a single modal. Brand and Model pickers
 * inside the wizard each support inline create-option, so the user can
 * compose a full new chain without leaving the modal.
 */
trait CanQuickAddVehicle
{
    /** @var array{brand_id: ?int, model_id: ?int, name: string, fuel_type_id: ?int, year: ?int, transmission_type_id: ?int} */
    public array $quickVehicle = [
        'brand_id' => null,
        'model_id' => null,
        'name' => '',
        'fuel_type_id' => null,
        'year' => null,
        'transmission_type_id' => null,
    ];

    public string $quickVehicleBrandSearch = '';

    public string $quickVehicleModelSearch = '';

    abstract protected function quickVehicleTargetProperty(): string;

    #[Computed]
    public function quickVehicleBrands()
    {
        return VehicleBrandMaster::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function quickVehicleFuelTypes()
    {
        return FuelTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function quickVehicleTransmissionTypes()
    {
        return TransmissionTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /** Models scoped to the picked brand, so the picker only shows valid options. */
    #[Computed]
    public function quickVehicleModels()
    {
        if (! $this->quickVehicle['brand_id']) {
            return collect();
        }

        return VehicleModelMaster::query()
            ->where('brand_id', $this->quickVehicle['brand_id'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /** Inline create-option for the Brand picker. */
    public function createQuickVehicleBrand(): void
    {
        $this->authorize('vehicle_brand_master.create');

        $name = strtoupper(trim($this->quickVehicleBrandSearch));
        if ($name === '') {
            return;
        }

        $brand = VehicleBrandMaster::firstOrCreate(['name' => $name], ['is_active' => true]);
        $this->quickVehicle['brand_id'] = $brand->id;
        $this->quickVehicle['model_id'] = null; // brand changed → reset model
        $this->quickVehicleBrandSearch = '';

        Flux::toast(text: 'Brand "'.$brand->name.'" added.', variant: 'success');
    }

    /** Brand changed via the picker (not the create-option) → invalidate model selection. */
    public function updatedQuickVehicleBrandId(): void
    {
        $this->quickVehicle['model_id'] = null;
    }

    /** Inline create-option for the Model picker — needs a brand context first. */
    public function createQuickVehicleModel(): void
    {
        $this->authorize('vehicle_model_master.create');

        if (! $this->quickVehicle['brand_id']) {
            $this->addError('quickVehicle.brand_id', 'Pick a brand before adding a model.');

            return;
        }

        $name = strtoupper(trim($this->quickVehicleModelSearch));
        if ($name === '') {
            return;
        }

        $model = VehicleModelMaster::firstOrCreate(
            ['brand_id' => $this->quickVehicle['brand_id'], 'name' => $name],
            ['is_active' => true],
        );
        $this->quickVehicle['model_id'] = $model->id;
        $this->quickVehicleModelSearch = '';

        Flux::toast(text: 'Model "'.$model->name.'" added.', variant: 'success');
    }

    public function createQuickVehicle(): void
    {
        $this->authorize('vehicle_variant_master.create');

        $validated = $this->validate([
            'quickVehicle.brand_id' => ['required', 'integer', Rule::exists('vehicle_brands', 'id')->where('is_active', true)],
            'quickVehicle.model_id' => ['required', 'integer', Rule::exists('vehicle_models', 'id')->where('is_active', true)],
            'quickVehicle.name' => ['required', 'string', 'max:255'],
            'quickVehicle.fuel_type_id' => ['nullable', 'integer', Rule::exists('fuel_types', 'id')->where('is_active', true)],
            'quickVehicle.year' => ['nullable', 'integer', 'min:1980', 'max:'.((int) date('Y') + 1)],
            'quickVehicle.transmission_type_id' => ['nullable', 'integer', Rule::exists('transmission_types', 'id')->where('is_active', true)],
        ], [], [
            'quickVehicle.brand_id' => 'brand',
            'quickVehicle.model_id' => 'model',
            'quickVehicle.name' => 'variant name',
            'quickVehicle.fuel_type_id' => 'fuel type',
            'quickVehicle.year' => 'year',
            'quickVehicle.transmission_type_id' => 'transmission',
        ])['quickVehicle'];

        $variant = VehicleVariantMaster::firstOrCreate(
            ['model_id' => $validated['model_id'], 'name' => strtoupper(trim($validated['name']))],
            [
                'fuel_type_id' => $validated['fuel_type_id'],
                'year' => $validated['year'],
                'transmission_type_id' => $validated['transmission_type_id'],
                'is_active' => true,
            ],
        );

        $target = $this->quickVehicleTargetProperty();
        $this->{$target} = $variant->id;

        $this->resetQuickVehicle();

        Flux::toast(text: 'Vehicle added.', variant: 'success');
        Flux::modal('vehicle-quick-add')->close();
    }

    public function resetQuickVehicle(): void
    {
        $this->quickVehicle = [
            'brand_id' => null,
            'model_id' => null,
            'name' => '',
            'fuel_type_id' => null,
            'year' => null,
            'transmission_type_id' => null,
        ];
        $this->quickVehicleBrandSearch = '';
        $this->quickVehicleModelSearch = '';
    }
}
