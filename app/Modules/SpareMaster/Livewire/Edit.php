<?php

namespace App\Modules\SpareMaster\Livewire;

use App\Concerns\HasQuickCreate;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TaxMaster\Models\TaxMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Spare')]
class Edit extends Component
{
    use HasQuickCreate;

    public ?int $editingId = null;

    public string $name = '';

    public ?string $spare_code = null;

    public ?string $description = null;

    public ?string $hsn_code = null;

    public ?int $spare_brand_id = null;

    public string $spareBrandSearch = '';

    public ?int $tax_id = null;

    public ?int $inventory_group_id = null;

    public ?int $inventory_sub_group_id = null;

    public ?int $workshop_department_id = null;

    public ?int $uom_id = null;

    public string $uomSearch = '';

    public float $rate_before_tax = 0;

    public float $min_qty = 0;

    public float $max_qty = 0;

    public ?string $barcode_type = null;

    public ?string $location = null;

    public bool $is_tyre = false;

    public ?string $tyre_dimension = null;

    public ?string $rim_size = null;

    public ?string $load_speed_index = null;

    public ?string $tread_pattern = null;

    public ?string $remark = null;

    public bool $is_active = true;

    /** @var list<int> */
    public array $variant_ids = [];

    public function mount(?SpareMaster $spare = null): void
    {
        if ($spare && $spare->exists) {
            $this->load($spare);
        }
    }

    protected function load(SpareMaster $spare): void
    {
        $spare->load('vehicleVariants:id');

        $this->editingId = $spare->id;
        foreach (['name', 'spare_code', 'description', 'hsn_code', 'barcode_type', 'location', 'tyre_dimension', 'rim_size', 'load_speed_index', 'tread_pattern', 'remark'] as $k) {
            $this->{$k} = $spare->{$k};
        }
        $this->spare_brand_id = $spare->spare_brand_id;
        $this->tax_id = $spare->tax_id;
        $this->inventory_group_id = $spare->inventory_group_id;
        $this->inventory_sub_group_id = $spare->inventory_sub_group_id;
        $this->workshop_department_id = $spare->workshop_department_id;
        $this->uom_id = $spare->uom_id;
        $this->rate_before_tax = (float) $spare->rate_before_tax;
        $this->min_qty = (float) $spare->min_qty;
        $this->max_qty = (float) $spare->max_qty;
        $this->is_tyre = (bool) $spare->is_tyre;
        $this->is_active = (bool) $spare->is_active;
        $this->variant_ids = $spare->vehicleVariants->pluck('id')->all();
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'spare_code' => ['nullable', 'string', 'max:64', Rule::unique('spares', 'spare_code')->ignore($this->editingId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'hsn_code' => ['nullable', 'string', 'max:16'],
            'spare_brand_id' => ['nullable', 'integer', Rule::exists('spare_brands', 'id')->where('is_active', true)],
            'tax_id' => ['nullable', 'integer', Rule::exists('taxes', 'id')->where('is_active', true)],
            'inventory_group_id' => ['nullable', 'integer', Rule::exists('inventory_groups', 'id')->where('is_active', true)->whereNull('parent_id')],
            'inventory_sub_group_id' => ['nullable', 'integer', Rule::exists('inventory_groups', 'id')->where('is_active', true)],
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')->where('is_active', true)],
            'uom_id' => ['nullable', 'integer', Rule::exists('units_of_measure', 'id')->where('is_active', true)],
            'rate_before_tax' => ['numeric', 'min:0', 'max:9999999.99'],
            'min_qty' => ['numeric', 'min:0', 'max:9999999.99'],
            'max_qty' => ['numeric', 'min:0', 'max:9999999.99', 'gte:min_qty'],
            'barcode_type' => ['nullable', 'string', 'in:EAN-13,CODE-128,QR'],
            'location' => ['nullable', 'string', 'max:64'],
            'is_tyre' => ['boolean'],
            'tyre_dimension' => ['nullable', 'string', 'max:32', 'required_if:is_tyre,true'],
            'rim_size' => ['nullable', 'string', 'max:16', 'required_if:is_tyre,true'],
            'load_speed_index' => ['nullable', 'string', 'max:16'],
            'tread_pattern' => ['nullable', 'string', 'max:32'],
            'remark' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'variant_ids' => ['array'],
            'variant_ids.*' => ['integer', Rule::exists('vehicle_variants', 'id')->where('is_active', true)],
        ];
    }

    public function updatedInventoryGroupId(): void
    {
        // Reset sub-group when parent changes — stale child would fail validation anyway.
        $this->inventory_sub_group_id = null;
    }

    public function updatedIsTyre(bool $value): void
    {
        // Clear tyre fields when toggled off so they don't ghost-save.
        if (! $value) {
            $this->tyre_dimension = null;
            $this->rim_size = null;
            $this->load_speed_index = null;
            $this->tread_pattern = null;
        }
    }

    public function createSpareBrand(): void
    {
        $this->quickCreate(
            modelClass: SpareBrandMaster::class,
            targetProperty: 'spare_brand_id',
            searchProperty: 'spareBrandSearch',
            permission: 'spare_brand_master.create',
            label: 'Parts brand',
        );
    }

    public function createUom(): void
    {
        $this->quickCreate(
            modelClass: UnitOfMeasureMaster::class,
            targetProperty: 'uom_id',
            searchProperty: 'uomSearch',
            permission: 'unit_of_measure_master.create',
            label: 'Unit of measure',
        );
    }

    #[Computed]
    public function brands()
    {
        return SpareBrandMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function taxes()
    {
        return TaxMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'gst_percent', 'cess_percent']);
    }

    #[Computed]
    public function parentInventoryGroups()
    {
        return InventoryGroupMaster::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function inventorySubGroups()
    {
        if (! $this->inventory_group_id) {
            return collect();
        }

        return InventoryGroupMaster::query()
            ->where('is_active', true)
            ->where('parent_id', $this->inventory_group_id)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function workshopDepartments()
    {
        return WorkshopDepartmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function uoms()
    {
        return UnitOfMeasureMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
    }

    #[Computed]
    public function variants()
    {
        return VehicleVariantMaster::query()
            ->with(['model.brand'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'model_id'])
            ->map(fn ($v) => [
                'id' => $v->id,
                'label' => trim(($v->model?->brand?->name ?? '').' '.($v->model?->name ?? '').' '.$v->name),
            ]);
    }

    /**
     * Live tax computation for the rate-with-tax hint shown next to the input.
     */
    #[Computed]
    public function rateInclTax(): float
    {
        $tax = $this->tax_id ? TaxMaster::find($this->tax_id) : null;
        $pct = (float) (($tax?->gst_percent ?? 0) + ($tax?->cess_percent ?? 0));

        return round($this->rate_before_tax * (1 + $pct / 100), 2);
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'spare_master.update' : 'spare_master.create');

        $data = $this->validate();
        $variants = $data['variant_ids'] ?? [];
        unset($data['variant_ids']);

        $skip = ['rate_before_tax', 'min_qty', 'max_qty', 'is_active', 'is_tyre', 'spare_brand_id', 'tax_id', 'inventory_group_id', 'inventory_sub_group_id', 'workshop_department_id', 'uom_id'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        $isCreate = $this->editingId === null;

        $spare = DB::transaction(function () use ($data, $variants) {
            if ($this->editingId) {
                $s = SpareMaster::findOrFail($this->editingId);
                $s->update($data);
            } else {
                $s = SpareMaster::create($data);
                $this->editingId = $s->id;
            }

            $s->vehicleVariants()->sync(array_map('intval', $variants));

            return $s;
        });

        Flux::toast(
            text: 'Spare #'.$spare->id.($isCreate ? ' created.' : ' updated.'),
            variant: 'success',
        );

        return redirect()->route('spare-master.index');
    }

    public function render()
    {
        return view('spare-master::edit');
    }
}
