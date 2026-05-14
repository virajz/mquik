<?php

namespace App\Modules\SpareMaster\Livewire;

use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
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
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'brand')]
    public string $brandFilter = 'all';

    #[Url(as: 'category')]
    public string $categoryFilter = 'all';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

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
        return SpareBrandMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = SpareMaster::query()
            ->with(['brand:id,name', 'uom:id,code,name'])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('name', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('spare_code', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('hsn_code', '%'.$search.'%', caseSensitive: false);
            }))
            ->when($this->brandFilter !== 'all', fn ($q) => $q->where('spare_brand_id', (int) $this->brandFilter))
            ->when($this->categoryFilter === 'tyre', fn ($q) => $q->where('is_tyre', true))
            ->when($this->categoryFilter === 'general', fn ($q) => $q->where('is_tyre', false))
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('spare-master::index', ['rows' => $rows]);
    }
}
