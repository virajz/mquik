<?php

namespace App\Modules\CounterSalesInvoice\Livewire;

use App\Modules\CounterSalesInvoice\Models\CounterSalesInvoice;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Counter Sales Invoices')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'pay')]
    public string $paymentFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'invoice_no', 'status', 'payment_status', 'grand_total', 'profit_total', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPaymentFilter(): void
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
        $this->authorize('counter_sales_invoice.delete');

        CounterSalesInvoice::findOrFail($id)->delete();

        Flux::toast(text: 'Counter invoice #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'paymentFilter']);
        $this->resetPage();
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = CounterSalesInvoice::query()
            ->with(['customer:id,first_name,last_name', 'courierCompany:id,name'])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('invoice_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('tracking_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('customer', fn ($c) => $c->whereLike('first_name', '%'.$search.'%', caseSensitive: false)
                        ->orWhereLike('last_name', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->paymentFilter !== 'all', fn ($q) => $q->where('payment_status', $this->paymentFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('counter-sales-invoice::index', [
            'rows' => $rows,
            'statuses' => CounterSalesInvoice::statuses(),
            'paymentStatuses' => CounterSalesInvoice::paymentStatuses(),
        ]);
    }
}
