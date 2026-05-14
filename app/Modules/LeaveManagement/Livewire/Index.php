<?php

namespace App\Modules\LeaveManagement\Livewire;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\LeaveManagement\Models\LeaveManagement;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Leave Management')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'emp')]
    public string $employeeFilter = 'all';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'type')]
    public string $typeFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'from_date';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'from_date', 'to_date', 'status', 'created_at'];

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

    public function openCreate(): void
    {
        $this->authorize('leave_management.create');
        $this->dispatch('leave-management:edit', id: null);
        Flux::modal('leave-management-form')->show();
    }

    public function openEdit(int $id): void
    {
        $this->authorize('leave_management.update');
        $this->dispatch('leave-management:edit', id: $id);
        Flux::modal('leave-management-form')->show();
    }

    #[On('leave-management:saved')]
    public function refreshAfterSave(): void
    {
        // re-render
    }

    public function delete(int $id): void
    {
        $this->authorize('leave_management.delete');

        LeaveManagement::findOrFail($id)->delete();
        Flux::toast(text: 'Leave request #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'employeeFilter', 'statusFilter', 'typeFilter']);
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

        $rows = LeaveManagement::query()
            ->with(['employee:id,name', 'approver:id,name'])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('reason', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('employee', fn ($e) => $e->whereLike('name', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->employeeFilter !== 'all', fn ($q) => $q->where('employee_id', (int) $this->employeeFilter))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter !== 'all', fn ($q) => $q->where('leave_type', $this->typeFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('leave-management::index', [
            'rows' => $rows,
            'statuses' => LeaveManagement::statuses(),
            'leaveTypes' => LeaveManagement::leaveTypes(),
        ]);
    }
}
