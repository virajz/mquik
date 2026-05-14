<?php

namespace App\Modules\LateMemo\Livewire;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\LateMemo\Models\LateMemo;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Late Memos')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'emp')]
    public string $employeeFilter = 'all';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'memo_date';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'memo_date', 'late_by_minutes', 'status', 'created_at'];

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
        $this->authorize('late_memo.create');
        $this->dispatch('late-memo:edit', id: null);
        Flux::modal('late-memo-form')->show();
    }

    public function openEdit(int $id): void
    {
        $this->authorize('late_memo.update');
        $this->dispatch('late-memo:edit', id: $id);
        Flux::modal('late-memo-form')->show();
    }

    #[On('late-memo:saved')]
    public function refreshAfterSave(): void
    {
        // re-render
    }

    public function delete(int $id): void
    {
        $this->authorize('late_memo.delete');

        LateMemo::findOrFail($id)->delete();
        Flux::toast(text: 'Late memo #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'employeeFilter', 'statusFilter']);
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

        $rows = LateMemo::query()
            ->with(['employee:id,name'])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('reason', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('employee', fn ($e) => $e->whereLike('name', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->employeeFilter !== 'all', fn ($q) => $q->where('employee_id', (int) $this->employeeFilter))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('late-memo::index', [
            'rows' => $rows,
            'statuses' => LateMemo::statuses(),
        ]);
    }
}
