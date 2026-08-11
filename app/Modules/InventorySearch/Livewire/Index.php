<?php

namespace App\Modules\InventorySearch\Livewire;

use App\Concerns\SearchesPickerOptions;
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
    use SearchesPickerOptions;
    use WithPagination;

    /** Search term for the server-backed subGroups picker. */
    public string $subGroupSearch = '';

    /** Search term for the server-backed variants picker. */
    public string $variantSearch = '';

    /** Search term for the server-backed models picker. */
    public string $modelSearch = '';

    /** Search term for the server-backed partsBrands picker. */
    public string $partsBrandSearch = '';

    /** Search term for the server-backed vendors picker. */
    public string $vendorSearch = '';

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

    /** Hide parts with nothing on the shelf — the usual view for a store person. */
    #[Url(as: 'instock')]
    public bool $inStockOnly = false;

    #[Url(as: 'vendor')]
    public string $vendorFilter = 'all';

    public ?int $alternativesForId = null;

    public function updating($name): void
    {
        if (str_ends_with($name, 'Filter') || in_array($name, ['search', 'inStockOnly'], true)) {
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
            'inStockOnly',
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
        return $this->pickerOptions(
            query: VehicleModelMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'brand.name'],
            term: $this->modelSearch,
            selected: is_numeric($this->modelFilter) ? (int) $this->modelFilter : null,
            columns: ['id', 'name', 'brand_id'],
            limit: 30,
        );
    }

    #[Computed]
    public function variants()
    {
        return $this->pickerOptions(
            query: VehicleVariantMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'model.name', 'model.brand.name'],
            term: $this->variantSearch,
            selected: is_numeric($this->variantFilter) ? (int) $this->variantFilter : null,
            columns: ['id', 'name', 'model_id'],
            limit: 30,
        );
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
        return $this->pickerOptions(
            query: InventoryGroupMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'code'],
            term: $this->subGroupSearch,
            selected: is_numeric($this->subGroupFilter) ? (int) $this->subGroupFilter : null,
            columns: ['id', 'name', 'parent_id'],
            limit: 30,
        );
    }

    #[Computed]
    public function partsBrands()
    {
        return $this->pickerOptions(
            query: SpareBrandMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'code'],
            term: $this->partsBrandSearch,
            selected: is_numeric($this->partsBrandFilter) ? (int) $this->partsBrandFilter : null,
            columns: ['id', 'name'],
            limit: 30,
        );
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
        return $this->pickerOptions(
            query: VendorMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'vendor_code'],
            term: $this->vendorSearch,
            selected: is_numeric($this->vendorFilter) ? (int) $this->vendorFilter : null,
            columns: ['id', 'name'],
            limit: 20,
        );
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
                    ->orWhereHas('hsn', fn ($h) => $h->whereLike('code', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->partTypeFilter !== 'all', fn ($q) => $q->where('part_type_id', (int) $this->partTypeFilter))
            ->when($this->groupFilter !== 'all', fn ($q) => $q->where('inventory_group_id', (int) $this->groupFilter))
            ->when($this->subGroupFilter !== 'all', fn ($q) => $q->where('inventory_sub_group_id', (int) $this->subGroupFilter))
            ->when($this->partsBrandFilter !== 'all', fn ($q) => $q->where('spare_brand_id', (int) $this->partsBrandFilter))
            ->when($this->uomFilter !== 'all', fn ($q) => $q->where('uom_id', (int) $this->uomFilter))
            ->when($this->rackFilter !== 'all', fn ($q) => $q->where('rack_id', (int) $this->rackFilter))
            // Filtered in SQL, not against the qty map — doing it after
            // pagination would leave the page counts lying.
            ->when($this->inStockOnly, fn ($q) => $q->whereIn('id', function ($sub) {
                $sub->from('stock_entries')
                    ->select('spare_id')
                    ->groupBy('spare_id')
                    ->havingRaw('sum(qty) <> 0');
            }))
            ->when($this->vendorFilter !== 'all', fn ($q) => $q->whereHas('brand.vendors', fn ($v) => $v->where('vendors.id', (int) $this->vendorFilter)))
            ->when($this->variantFilter !== 'all', fn ($q) => $q->whereHas('vehicleVariants', fn ($v) => $v->where('vehicle_variants.id', (int) $this->variantFilter)))
            ->when($this->variantFilter === 'all' && $this->modelFilter !== 'all', fn ($q) => $q->whereHas('vehicleVariants', fn ($v) => $v->where('model_id', (int) $this->modelFilter)))
            ->when($this->variantFilter === 'all' && $this->modelFilter === 'all' && $this->vehicleBrandFilter !== 'all', fn ($q) => $q->whereHas('vehicleVariants.model', fn ($m) => $m->where('brand_id', (int) $this->vehicleBrandFilter)))
            ->orderBy('name')
            ->paginate(25);

        $qtyMap = StockLedger::currentQtyMap($rows->pluck('id')->all());

        return view('inventory-search::index', [
            'rows' => $rows,
            'qtyMap' => $qtyMap,
        ]);
    }
}
