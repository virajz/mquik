<?php

namespace App\Modules\Payroll\Livewire;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\Payroll\Models\Payroll;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Payroll')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'emp')]
    public string $employeeFilter = 'all';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'year')]
    public string $yearFilter = '';

    #[Url(as: 'month')]
    public string $monthFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'period_year';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'period_year', 'period_month', 'gross_amount', 'net_amount', 'status', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingEmployeeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingYearFilter(): void
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

    public function openCreate(): void
    {
        $this->authorize('payroll.create');
        $this->dispatch('payroll:edit', id: null);
        Flux::modal('payroll-form')->show();
    }

    public function openEdit(int $id): void
    {
        $this->authorize('payroll.update');
        $this->dispatch('payroll:edit', id: $id);
        Flux::modal('payroll-form')->show();
    }

    #[On('payroll:saved')]
    public function refreshAfterSave(): void
    {
        // re-render
    }

    public function delete(int $id): void
    {
        $this->authorize('payroll.delete');

        Payroll::findOrFail($id)->delete();
        Flux::toast(text: 'Payroll #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'employeeFilter', 'statusFilter', 'yearFilter', 'monthFilter']);
        $this->resetPage();
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = Payroll::query()
            ->with(['employee:id,name'])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('notes', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('employee', fn ($e) => $e->whereLike('name', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->employeeFilter !== 'all', fn ($q) => $q->where('employee_id', (int) $this->employeeFilter))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->yearFilter !== '', fn ($q) => $q->where('period_year', (int) $this->yearFilter))
            ->when($this->monthFilter !== 'all', fn ($q) => $q->where('period_month', (int) $this->monthFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->orderBy('period_month', 'desc')
            ->paginate(20);

        return view('payroll::index', [
            'rows' => $rows,
            'statuses' => Payroll::statuses(),
            'months' => Payroll::months(),
        ]);
    }
}
