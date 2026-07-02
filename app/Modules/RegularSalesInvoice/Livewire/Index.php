<?php

namespace App\Modules\RegularSalesInvoice\Livewire;

use App\Modules\RegularSalesInvoice\Models\RegularSalesInvoice;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Regular Sales Invoices')]
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
        $this->authorize('regular_sales_invoice.delete');

        RegularSalesInvoice::findOrFail($id)->delete();

        Flux::toast(text: 'Invoice #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'paymentFilter']);
        $this->resetPage();
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = RegularSalesInvoice::query()
            ->with([
                'customer:id,first_name,last_name',
                'customerVehicle:id,registration_no',
                'insuranceCompany:id,name',
            ])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('invoice_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('policy_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('customerVehicle', fn ($v) => $v->whereLike('registration_no', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->paymentFilter !== 'all', fn ($q) => $q->where('payment_status', $this->paymentFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('regular-sales-invoice::index', [
            'rows' => $rows,
            'statuses' => RegularSalesInvoice::statuses(),
            'paymentStatuses' => RegularSalesInvoice::paymentStatuses(),
        ]);
    }
}
