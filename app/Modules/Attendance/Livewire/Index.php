<?php

namespace App\Modules\Attendance\Livewire;

use App\Modules\Attendance\Models\Attendance;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Attendance')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'emp')]
    public string $employeeFilter = 'all';

    #[Url(as: 'type')]
    public string $typeFilter = 'all';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'punched_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'punched_at', 'type', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingEmployeeFilter(): void
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
        $this->authorize('attendance.create');
        $this->dispatch('attendance:edit', id: null);
        Flux::modal('attendance-form')->show();
    }

    public function openEdit(int $id): void
    {
        $this->authorize('attendance.update');
        $this->dispatch('attendance:edit', id: $id);
        Flux::modal('attendance-form')->show();
    }

    #[On('attendance:saved')]
    public function refreshAfterSave(): void
    {
        // Triggers re-render; pagination cursor preserved.
    }

    public function delete(int $id): void
    {
        $this->authorize('attendance.delete');

        Attendance::findOrFail($id)->delete();
        Flux::toast(text: 'Attendance #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'employeeFilter', 'typeFilter', 'dateFrom', 'dateTo']);
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

        $rows = Attendance::query()
            ->with(['employee:id,name'])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('notes', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('employee', fn ($e) => $e->whereLike('name', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->employeeFilter !== 'all', fn ($q) => $q->where('employee_id', (int) $this->employeeFilter))
            ->when($this->typeFilter !== 'all', fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('punched_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('punched_at', '<=', $this->dateTo))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('attendance::index', [
            'rows' => $rows,
            'types' => Attendance::types(),
        ]);
    }
}
