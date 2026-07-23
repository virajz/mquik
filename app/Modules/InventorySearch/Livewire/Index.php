<?php

namespace App\Modules\InventorySearch\Livewire;

use App\Modules\Inventory\Services\StockLedger;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use App\Modules\PartTypeMaster\Models\PartTypeMaster;
use App\Modules\RackMaster\Models\RackMaster;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\UnitOfMeasureMaster\Models\UnitOfMeasureMaster;
use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Inventory Search')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'vbrand')]
    public string $vehicleBrandFilter = 'all';

    #[Url(as: 'model')]
    public string $modelFilter = 'all';

    #[Url(as: 'variant')]
    public string $variantFilter = 'all';

    #[Url(as: 'ptype')]
    public string $partTypeFilter = 'all';

    #[Url(as: 'grp')]
    public string $groupFilter = 'all';

    #[Url(as: 'subgrp')]
    public string $subGroupFilter = 'all';

    #[Url(as: 'pbrand')]
    public string $partsBrandFilter = 'all';

    #[Url(as: 'uom')]
    public string $uomFilter = 'all';

    #[Url(as: 'rack')]
    public string $rackFilter = 'all';

    #[Url(as: 'vendor')]
    public string $vendorFilter = 'all';

    public ?int $alternativesForId = null;

    public function updating($name): void
    {
        if (str_ends_with($name, 'Filter') || $name === 'search') {
            $this->resetPage();
        }
    }

    public function updatedVehicleBrandFilter(): void
    {
        $this->modelFilter = 'all';
        $this->variantFilter = 'all';
    }

    public function updatedModelFilter(): void
    {
        $this->variantFilter = 'all';
    }

    public function updatedGroupFilter(): void
    {
        $this->subGroupFilter = 'all';
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search', 'vehicleBrandFilter', 'modelFilter', 'variantFilter', 'partTypeFilter',
            'groupFilter', 'subGroupFilter', 'partsBrandFilter', 'uomFilter', 'rackFilter', 'vendorFilter',
        ]);
        $this->resetPage();
    }

    public function showAlternatives(int $spareId): void
    {
        $this->alternativesForId = $spareId;
    }

    #[Computed]
    public function vehicleBrands()
    {
        return VehicleBrandMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function models()
    {
        if ($this->vehicleBrandFilter === 'all') {
            return collect();
        }

        return VehicleModelMaster::query()->where('is_active', true)
            ->where('brand_id', (int) $this->vehicleBrandFilter)
            ->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function variants()
    {
        if ($this->modelFilter === 'all') {
            return collect();
        }

        return VehicleVariantMaster::query()->where('is_active', true)
            ->where('model_id', (int) $this->modelFilter)
            ->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function partTypes()
    {
        return PartTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function inventoryGroups()
    {
        return InventoryGroupMaster::query()->where('is_active', true)->whereNull('parent_id')->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function subGroups()
    {
        if ($this->groupFilter === 'all') {
            return collect();
        }

        return InventoryGroupMaster::query()->where('is_active', true)
            ->where('parent_id', (int) $this->groupFilter)
            ->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function partsBrands()
    {
        return SpareBrandMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function uoms()
    {
        return UnitOfMeasureMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function racks()
    {
        return RackMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function vendors()
    {
        return VendorMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Alternatives = parts in the same sub-group that fit at least one shared vehicle variant.
     */
    #[Computed]
    public function alternativeRows()
    {
        if (! $this->alternativesForId) {
            return collect();
        }

        $spare = SpareMaster::with('vehicleVariants:id')->find($this->alternativesForId);
        if (! $spare) {
            return collect();
        }

        $variantIds = $spare->vehicleVariants->pluck('id')->all();

        $alts = SpareMaster::query()
            ->with(['brand:id,name', 'partType:id,name', 'rack:id,name'])
            ->whereKeyNot($spare->id)
            ->where('is_active', true)
            ->when($spare->inventory_sub_group_id, fn ($q) => $q->where('inventory_sub_group_id', $spare->inventory_sub_group_id))
            ->when($variantIds, fn ($q) => $q->whereHas('vehicleVariants', fn ($v) => $v->whereIn('vehicle_variants.id', $variantIds)))
            ->orderBy('name')
            ->limit(25)
            ->get(['id', 'name', 'spare_code', 'spare_brand_id', 'part_type_id', 'rack_id', 'location', 'min_qty', 'max_qty']);

        $qtyMap = StockLedger::currentQtyMap($alts->pluck('id')->all());

        return $alts->map(function ($s) use ($qtyMap) {
            $qty = $qtyMap[$s->id] ?? 0.0;

            return [
                'name' => $s->name,
                'spare_code' => $s->spare_code,
                'brand' => $s->brand?->name,
                'part_type' => $s->partType?->name,
                'rack' => $s->rack?->name ?? $s->location,
                'qty' => $qty,
                'alert' => StockLedger::alertStatus($qty, (float) $s->min_qty, (float) $s->max_qty),
            ];
        });
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = SpareMaster::query()
            ->with([
                'brand:id,name',
                'partType:id,name',
                'inventoryGroup:id,name',
                'inventorySubGroup:id,name',
                'uom:id,name,code',
                'rack:id,name',
            ])
            ->withCount('vehicleVariants')
            ->where('is_active', true)
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('name', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('spare_code', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('hsn_code', '%'.$search.'%', caseSensitive: false);
            }))
            ->when($this->partTypeFilter !== 'all', fn ($q) => $q->where('part_type_id', (int) $this->partTypeFilter))
            ->when($this->groupFilter !== 'all', fn ($q) => $q->where('inventory_group_id', (int) $this->groupFilter))
            ->when($this->subGroupFilter !== 'all', fn ($q) => $q->where('inventory_sub_group_id', (int) $this->subGroupFilter))
            ->when($this->partsBrandFilter !== 'all', fn ($q) => $q->where('spare_brand_id', (int) $this->partsBrandFilter))
            ->when($this->uomFilter !== 'all', fn ($q) => $q->where('uom_id', (int) $this->uomFilter))
            ->when($this->rackFilter !== 'all', fn ($q) => $q->where('rack_id', (int) $this->rackFilter))
            ->when($this->vendorFilter !== 'all', fn ($q) => $q->whereHas('brand.vendors', fn ($v) => $v->where('vendors.id', (int) $this->vendorFilter)))
            ->when($this->variantFilter !== 'all', fn ($q) => $q->whereHas('vehicleVariants', fn ($v) => $v->where('vehicle_variants.id', (int) $this->variantFilter)))
            ->when($this->variantFilter === 'all' && $this->modelFilter !== 'all', fn ($q) => $q->whereHas('vehicleVariants', fn ($v) => $v->where('model_id', (int) $this->modelFilter)))
            ->when($this->variantFilter === 'all' && $this->modelFilter === 'all' && $this->vehicleBrandFilter !== 'all', fn ($q) => $q->whereHas('vehicleVariants.model', fn ($m) => $m->where('brand_id', (int) $this->vehicleBrandFilter)))
            ->orderBy('name')
            ->paginate(25);

        $qtyMap = StockLedger::currentQtyMap($rows->pluck('id')->all());

        // Optional stock-status filter is applied in-view against the qty map.
        return view('inventory-search::index', [
            'rows' => $rows,
            'qtyMap' => $qtyMap,
        ]);
    }
}
