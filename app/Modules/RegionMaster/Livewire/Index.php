<?php

namespace App\Modules\RegionMaster\Livewire;

use App\Modules\RegionMaster\Models\RegionMaster;
use Flux\Flux;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Regions')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'kind')]
    public string $kindFilter = 'all';

    #[Url(as: 'parent')]
    public string $parentFilter = 'all';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'name';

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    /** Whitelist sortable columns — never trust the URL */
    protected array $sortable = ['id', 'kind', 'name', 'code', 'is_active', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingKindFilter(): void
    {
        $this->parentFilter = 'all'; // reset dependent filter
        $this->resetPage();
    }

    public function updatingParentFilter(): void
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

    public function openCreate(): void
    {
        $this->dispatch('region-master:edit', id: null);
        Flux::modal('region-master-form')->show();
    }

    public function openEdit(int $id): void
    {
        $this->dispatch('region-master:edit', id: $id);
        Flux::modal('region-master-form')->show();
    }

    #[On('region-master:saved')]
    public function refreshAfterSave(): void
    {
        // Triggers re-render; pagination cursor preserved.
    }

    public function delete(int $id): void
    {
        try {
            RegionMaster::findOrFail($id)->delete();
            Flux::toast(text: 'Region #'.$id.' deleted.', variant: 'success');
        } catch (QueryException) {
            Flux::toast(
                text: 'Cannot delete this region — it is still in use.',
                variant: 'danger',
            );
        }
    }

    /**
     * Parent options for the parent filter — depends on the kind filter's parent kind.
     */
    #[Computed]
    public function parentFilterOptions()
    {
        $parentKind = match ($this->kindFilter) {
            'city' => 'state',
            'area' => 'city',
            'pincode' => 'area',
            default => null,
        };

        if ($parentKind === null) {
            return collect();
        }

        return RegionMaster::query()
            ->where('kind', $parentKind)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function render()
    {
        $search = $this->search;
        $status = $this->statusFilter;

        $rows = RegionMaster::query()
            ->with('parent:id,name')
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->kindFilter !== 'all', fn ($q) => $q->where('kind', $this->kindFilter))
            ->when($this->parentFilter !== 'all', fn ($q) => $q->where('parent_id', $this->parentFilter))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('region-master::index', ['rows' => $rows]);
    }
}
