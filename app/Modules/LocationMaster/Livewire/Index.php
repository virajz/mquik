<?php

namespace App\Modules\LocationMaster\Livewire;

use App\Modules\LocationMaster\Models\LocationMaster;
use Flux\Flux;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Locations')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'ho')]
    public string $headOfficeFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'name';

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    /** Whitelist sortable columns — never trust the URL */
    protected array $sortable = ['id', 'name', 'code', 'is_active', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingHeadOfficeFilter(): void
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
        $this->dispatch('location-master:edit', id: null);
        Flux::modal('location-master-form')->show();
    }

    public function openEdit(int $id): void
    {
        $this->dispatch('location-master:edit', id: $id);
        Flux::modal('location-master-form')->show();
    }

    #[On('location-master:saved')]
    public function refreshAfterSave(): void
    {
        // Triggers re-render; pagination cursor preserved.
    }

    public function delete(int $id): void
    {
        try {
            LocationMaster::findOrFail($id)->delete();
            Flux::toast(text: 'Location #'.$id.' deleted.', variant: 'success');
        } catch (QueryException) {
            Flux::toast(
                text: 'Cannot delete this location — it is still in use by job cards, inventory, or invoices.',
                variant: 'danger',
            );
        }
    }

    public function render()
    {
        $search = $this->search;
        $status = $this->statusFilter;
        $headOffice = $this->headOfficeFilter;

        $rows = LocationMaster::query()
            ->with(['city:id,name', 'state:id,name'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $term = '%'.$search.'%';
                    $query->whereLike('name', $term, caseSensitive: false)
                        ->orWhereLike('code', $term, caseSensitive: false)
                        ->orWhereLike('gstin', $term, caseSensitive: false);
                });
            })
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($headOffice === 'head_office', fn ($q) => $q->where('is_head_office', true))
            ->when($headOffice === 'branch', fn ($q) => $q->where('is_head_office', false))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('location-master::index', ['rows' => $rows]);
    }
}
