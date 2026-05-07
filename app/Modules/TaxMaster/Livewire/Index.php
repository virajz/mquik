<?php

namespace App\Modules\TaxMaster\Livewire;

use App\Modules\TaxMaster\Models\TaxMaster;
use Flux\Flux;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Taxes')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'name';

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    /** Whitelist sortable columns — never trust the URL */
    protected array $sortable = ['id', 'name', 'code', 'hsn_sac', 'gst_percent', 'is_active', 'created_at'];

    public function updatingSearch(): void
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
        $this->dispatch('tax-master:edit', id: null);
        Flux::modal('tax-master-form')->show();
    }

    public function openEdit(int $id): void
    {
        $this->dispatch('tax-master:edit', id: $id);
        Flux::modal('tax-master-form')->show();
    }

    #[On('tax-master:saved')]
    public function refreshAfterSave(): void
    {
        // Triggers re-render; pagination cursor preserved.
    }

    public function delete(int $id): void
    {
        try {
            TaxMaster::findOrFail($id)->delete();
            Flux::toast(text: 'Tax #'.$id.' deleted.', variant: 'success');
        } catch (QueryException) {
            Flux::toast(
                text: 'Cannot delete this tax — it is still in use by spares, services, or invoices.',
                variant: 'danger',
            );
        }
    }

    public function render()
    {
        $search = $this->search;
        $status = $this->statusFilter;

        $rows = TaxMaster::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $term = '%'.$search.'%';
                    $query->whereLike('name', $term, caseSensitive: false)
                        ->orWhereLike('code', $term, caseSensitive: false)
                        ->orWhereLike('hsn_sac', $term, caseSensitive: false);
                });
            })
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('tax-master::index', ['rows' => $rows]);
    }
}
