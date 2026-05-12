<?php

namespace App\Concerns;

use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleSegmentMaster\Models\VehicleSegmentMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;

/**
 * Vehicle Model quick-add wizard for any picker that selects a vehicle model
 * (e.g. VehicleVariantMaster Form's `model_id`).
 *
 * Brand picking inside the wizard supports inline create-option for new brands.
 * Same for the Segment picker.
 */
trait CanQuickAddModel
{
    /** @var array{brand_id: ?int, name: string, vehicle_segment_id: ?int} */
    public array $quickModel = [
        'brand_id' => null,
        'name' => '',
        'vehicle_segment_id' => null,
    ];

    public string $quickModelBrandSearch = '';

    public string $quickModelSegmentSearch = '';

    abstract protected function quickModelTargetProperty(): string;

    #[Computed]
    public function quickModelBrands()
    {
        return VehicleBrandMaster::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function quickModelSegments()
    {
        return VehicleSegmentMaster::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /** Inline create-option for the Brand picker inside the wizard. */
    public function createQuickModelBrand(): void
    {
        $this->authorize('vehicle_brand_master.create');

        $name = strtoupper(trim($this->quickModelBrandSearch));
        if ($name === '') {
            return;
        }

        $brand = VehicleBrandMaster::firstOrCreate(['name' => $name], ['is_active' => true]);
        $this->quickModel['brand_id'] = $brand->id;
        $this->quickModelBrandSearch = '';

        Flux::toast(text: 'Brand "'.$brand->name.'" added.', variant: 'success');
    }

    /** Inline create-option for the Segment picker inside the wizard. */
    public function createQuickModelSegment(): void
    {
        $this->authorize('vehicle_segment_master.create');

        $name = strtoupper(trim($this->quickModelSegmentSearch));
        if ($name === '') {
            return;
        }

        $segment = VehicleSegmentMaster::firstOrCreate(['name' => $name], ['is_active' => true]);
        $this->quickModel['vehicle_segment_id'] = $segment->id;
        $this->quickModelSegmentSearch = '';

        Flux::toast(text: 'Segment "'.$segment->name.'" added.', variant: 'success');
    }

    public function createQuickModel(): void
    {
        $this->authorize('vehicle_model_master.create');

        $validated = $this->validate([
            'quickModel.brand_id' => ['required', 'integer', Rule::exists('vehicle_brands', 'id')->where('is_active', true)],
            'quickModel.name' => ['required', 'string', 'max:255'],
            'quickModel.vehicle_segment_id' => ['nullable', 'integer', Rule::exists('vehicle_segments', 'id')->where('is_active', true)],
        ], [], [
            'quickModel.brand_id' => 'brand',
            'quickModel.name' => 'model name',
            'quickModel.vehicle_segment_id' => 'segment',
        ])['quickModel'];

        $model = VehicleModelMaster::firstOrCreate(
            ['brand_id' => $validated['brand_id'], 'name' => strtoupper(trim($validated['name']))],
            ['vehicle_segment_id' => $validated['vehicle_segment_id'], 'is_active' => true],
        );

        $target = $this->quickModelTargetProperty();
        $this->{$target} = $model->id;

        $this->resetQuickModel();

        Flux::toast(text: 'Model "'.$model->name.'" added.', variant: 'success');
        Flux::modal('vehicle-model-quick-add')->close();
    }

    public function resetQuickModel(): void
    {
        $this->quickModel = ['brand_id' => null, 'name' => '', 'vehicle_segment_id' => null];
        $this->quickModelBrandSearch = '';
        $this->quickModelSegmentSearch = '';
    }
}
