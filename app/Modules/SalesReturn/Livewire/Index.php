<?php

namespace App\Modules\SalesReturn\Livewire;

use App\Modules\SalesReturn\Models\SalesReturn;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Sales Returns')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'type')]
    public string $typeFilter = 'all';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'refund')]
    public string $refundFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'return_no', 'return_type', 'status', 'refund_status', 'grand_total', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingRefundFilter(): void
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
        $this->authorize('sales_return.delete');

        SalesReturn::findOrFail($id)->delete();

        Flux::toast(text: 'Sales return #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'typeFilter', 'statusFilter', 'refundFilter']);
        $this->resetPage();
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = SalesReturn::query()
            ->with(['customer:id,first_name,last_name', 'returnReason:id,name'])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->typeFilter !== 'all', fn ($q) => $q->where('return_type', $this->typeFilter))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->refundFilter !== 'all', fn ($q) => $q->where('refund_status', $this->refundFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('sales-return::index', [
            'rows' => $rows,
            'returnTypes' => SalesReturn::returnTypes(),
            'statuses' => SalesReturn::statuses(),
            'refundStatuses' => SalesReturn::refundStatuses(),
        ]);
    }
}
