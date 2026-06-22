<?php

namespace App\Modules\ChallanEntry\Livewire;

use App\Modules\ChallanEntry\Models\Challan;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Challan Entries')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'type')]
    public string $typeFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'challan_no', 'grand_total', 'inventory_status', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
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
        $this->authorize('challan_entry.delete');

        Challan::findOrFail($id)->delete();

        Flux::toast(text: 'Challan #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'typeFilter']);
        $this->resetPage();
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = Challan::query()
            ->with(['vendor:id,name', 'jobCard:id,job_card_no'])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('challan_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('po_reference', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('vendor', fn ($v) => $v->whereLike('name', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('inventory_status', $this->statusFilter))
            ->when($this->typeFilter !== 'all', fn ($q) => $q->where('purchase_type', $this->typeFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('challan-entry::index', [
            'rows' => $rows,
            'statuses' => Challan::inventoryStatuses(),
            'types' => Challan::purchaseTypes(),
        ]);
    }
}
