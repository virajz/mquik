<?php

namespace App\Modules\RegularPayment\Livewire;

use App\Modules\RegularPayment\Models\RegularPayment;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Regular Payments')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'month')]
    public string $monthFilter = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'payment_no', 'status', 'amount', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingMonthFilter(): void
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
        $this->authorize('regular_payment.delete');

        RegularPayment::findOrFail($id)->delete();

        Flux::toast(text: 'Payment #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'monthFilter']);
        $this->resetPage();
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = RegularPayment::query()
            ->with(['vendor:id,name', 'paymentMode:id,name'])
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->monthFilter !== '', fn ($q) => $q->whereRaw("to_char(created_at, 'YYYY-MM') = ?", [$this->monthFilter]))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('regular-payment::index', [
            'rows' => $rows,
            'statuses' => RegularPayment::statuses(),
        ]);
    }
}
