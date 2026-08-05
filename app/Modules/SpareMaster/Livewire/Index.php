<?php

namespace App\Modules\SpareMaster\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Spares')]
class Index extends Component
{
    use SearchesPickerOptions;
    use WithPagination;

    /** Search term for the server-backed brands picker. */
    public string $brandSearch = '';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'brand')]
    public string $brandFilter = 'all';

    #[Url(as: 'category')]
    public string $categoryFilter = 'all';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    /** Filter to spares that fit a given vehicle model, or one exact variant. */
    #[Url(as: 'model')]
    public string $modelFilter = 'all';

    #[Url(as: 'variant')]
    public string $variantFilter = 'all';

    #[Url(as: 'dept')]
    public string $departmentFilter = 'all';

    #[Url(as: 'group')]
    public string $groupFilter = 'all';

    /** Search terms for the server-backed model / variant pickers. */
    public string $modelSearch = '';

    public string $variantSearch = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'id';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    /** Whitelist sortable columns to prevent SQL injection via the URL */
    protected array $sortable = ['id', 'name', 'spare_code', 'rate_before_tax', 'is_active', 'created_at', 'updated_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingBrandFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDepartmentFilter(): void
    {
        $this->resetPage();
    }

    public function updatingGroupFilter(): void
    {
        $this->resetPage();
    }

    public function updatingVariantFilter(): void
    {
        $this->resetPage();
    }

    /** Choosing a different model invalidates the variant picked under it. */
    public function updatingModelFilter(): void
    {
        $this->resetPage();
        $this->variantFilter = 'all';
        $this->variantSearch = '';
        unset($this->variants);
    }

    public function sort(string $column): void
    {
        if (! in_array($column, $this->sortable, true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function delete(int $id): void
    {
        $this->authorize('spare_master.delete');

        SpareMaster::findOrFail($id)->delete();

        Flux::toast(text: 'Spare #'.$id.' deleted.', variant: 'success');
    }

    #[Computed]
    public function brands()
    {
        return $this->pickerOptions(
            query: SpareBrandMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'code'],
            term: $this->brandSearch,
            selected: is_numeric($this->brandFilter) ? (int) $this->brandFilter : null,
            columns: ['id', 'name'],
            limit: 30,
        );
    }

    /**
     * Vehicle models, searched server-side — the catalogue is ~394 models under
     * 50 brands, so the picker shows brand context.
     */
    #[Computed]
    public function models()
    {
        return $this->pickerOptions(
            query: VehicleModelMaster::query()->with('brand:id,name')->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'brand.name'],
            term: $this->modelSearch,
            selected: is_numeric($this->modelFilter) ? (int) $this->modelFilter : null,
            columns: ['id', 'name', 'brand_id'],
            limit: 30,
        );
    }

    /** Variants of the picked model — only offered once a model is chosen. */
    #[Computed]
    public function variants()
    {
        if (! is_numeric($this->modelFilter)) {
            return collect();
        }

        return $this->pickerOptions(
            query: VehicleVariantMaster::query()
                ->where('is_active', true)
                ->where('model_id', (int) $this->modelFilter)
                ->orderBy('name'),
            searchColumns: ['name'],
            term: $this->variantSearch,
            selected: is_numeric($this->variantFilter) ? (int) $this->variantFilter : null,
            columns: ['id', 'name', 'year'],
            limit: 30,
        );
    }

    #[Computed]
    public function departments()
    {
        return WorkshopDepartmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function inventoryGroups()
    {
        return InventoryGroupMaster::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search', 'brandFilter', 'categoryFilter', 'statusFilter',
            'modelFilter', 'variantFilter', 'departmentFilter', 'groupFilter',
            'brandSearch', 'modelSearch', 'variantSearch',
        ]);
        $this->resetPage();
    }

    /** True while any filter is narrowing the list — drives the Clear button. */
    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || collect([
                $this->brandFilter, $this->categoryFilter, $this->statusFilter,
                $this->modelFilter, $this->variantFilter, $this->departmentFilter, $this->groupFilter,
            ])->contains(fn ($v) => $v !== 'all');
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = SpareMaster::query()
            ->with(['brand:id,name', 'uom:id,code,name', 'hsn:id,code', 'workshopDepartment:id,name'])
            // Shared multi-token scope: every token must land SOMEWHERE across
            // name / part no / description, so "absorber laura" finds a shock
            // absorber whose description lists LAURA among its vehicles. HSN
            // lives on a relation, so it is OR'd in alongside.
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->search($search)
                ->orWhereHas('hsn', fn ($h) => $h->whereLike('code', '%'.$search.'%', caseSensitive: false))))
            ->when($this->brandFilter !== 'all', fn ($q) => $q->where('spare_brand_id', (int) $this->brandFilter))
            ->when($this->categoryFilter === 'tyre', fn ($q) => $q->where('spare_type', SpareMaster::TYPE_TYRE))
            ->when($this->categoryFilter === 'general', fn ($q) => $q->where('spare_type', '!=', SpareMaster::TYPE_TYRE))
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($this->departmentFilter !== 'all', fn ($q) => $q->where('workshop_department_id', (int) $this->departmentFilter))
            // Sub-groups hang off the picked parent, so match either level.
            ->when($this->groupFilter !== 'all', fn ($q) => $q->where(fn ($w) => $w
                ->where('inventory_group_id', (int) $this->groupFilter)
                ->orWhere('inventory_sub_group_id', (int) $this->groupFilter)))
            // One exact variant beats the whole model when both are set.
            ->when($this->variantFilter !== 'all', fn ($q) => $q->whereHas(
                'vehicleVariants',
                fn ($v) => $v->where('vehicle_variants.id', (int) $this->variantFilter),
            ))
            ->when($this->variantFilter === 'all' && $this->modelFilter !== 'all', fn ($q) => $q->whereHas(
                'vehicleVariants',
                fn ($v) => $v->where('vehicle_variants.model_id', (int) $this->modelFilter),
            ))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('spare-master::index', ['rows' => $rows]);
    }
}
