<?php

namespace App\Modules\PurchaseEntry\Livewire;

use App\Modules\PurchaseEntry\Models\PurchaseEntry;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Purchase Entries')]
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

    protected array $sortable = ['id', 'purchase_no', 'invoice_no', 'grand_total', 'created_at'];

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
        $this->authorize('purchase_entry.delete');

        PurchaseEntry::findOrFail($id)->delete();

        Flux::toast(text: 'Purchase #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'typeFilter']);
        $this->resetPage();
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = PurchaseEntry::query()
            ->with(['vendor:id,name', 'challan:id,challan_no'])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('inventory_status', $this->statusFilter))
            ->when($this->typeFilter !== 'all', fn ($q) => $q->where('purchase_type', $this->typeFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('purchase-entry::index', [
            'rows' => $rows,
            'statuses' => PurchaseEntry::inventoryStatuses(),
            'types' => PurchaseEntry::purchaseTypes(),
            'invoiceTypes' => PurchaseEntry::invoiceTypes(),
        ]);
    }
}
