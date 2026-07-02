<?php

namespace App\Modules\ReceiptRefund\Livewire;

use App\Modules\ReceiptRefund\Models\ReceiptRefund;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Receipt Refunds')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'refund_no', 'refund_status', 'amount', 'created_at'];

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

    public function delete(int $id): void
    {
        $this->authorize('receipt_refund.delete');

        ReceiptRefund::findOrFail($id)->delete();

        Flux::toast(text: 'Refund #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter']);
        $this->resetPage();
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = ReceiptRefund::query()
            ->with(['customer:id,first_name,last_name', 'refundType:id,name'])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('refund_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('reference_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('customer', fn ($c) => $c->whereLike('first_name', '%'.$search.'%', caseSensitive: false)
                        ->orWhereLike('last_name', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('refund_status', $this->statusFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('receipt-refund::index', [
            'rows' => $rows,
            'refundStatuses' => ReceiptRefund::refundStatuses(),
        ]);
    }
}
