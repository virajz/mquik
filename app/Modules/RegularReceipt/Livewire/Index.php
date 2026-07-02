<?php

namespace App\Modules\RegularReceipt\Livewire;

use App\Modules\RegularReceipt\Models\RegularReceipt;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Regular Receipts')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'cheque')]
    public string $chequeFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'receipt_no', 'status', 'amount', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingChequeFilter(): void
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
        $this->authorize('regular_receipt.delete');

        RegularReceipt::findOrFail($id)->delete();

        Flux::toast(text: 'Receipt #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'chequeFilter']);
        $this->resetPage();
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = RegularReceipt::query()
            ->with(['customer:id,first_name,last_name', 'paymentMode:id,name'])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('receipt_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('reference_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('cheque_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('customer', fn ($c) => $c->whereLike('first_name', '%'.$search.'%', caseSensitive: false)
                        ->orWhereLike('last_name', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->chequeFilter !== 'all', fn ($q) => $q->where('cheque_status', $this->chequeFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('regular-receipt::index', [
            'rows' => $rows,
            'statuses' => RegularReceipt::statuses(),
            'chequeStatuses' => RegularReceipt::chequeStatuses(),
        ]);
    }
}
