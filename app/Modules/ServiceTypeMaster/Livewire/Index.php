<?php

namespace App\Modules\ServiceTypeMaster\Livewire;

use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Flux\Flux;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Service Types')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'dept')]
    public string $departmentFilter = 'all';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'name';

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    protected array $sortable = ['id', 'name', 'code', 'workshop_department_id', 'is_active', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDepartmentFilter(): void
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
        $this->dispatch('service-type-master:edit', id: null);
        Flux::modal('service-type-master-form')->show();
    }

    public function openEdit(int $id): void
    {
        $this->dispatch('service-type-master:edit', id: $id);
        Flux::modal('service-type-master-form')->show();
    }

    #[On('service-type-master:saved')]
    public function refreshAfterSave(): void {}

    public function delete(int $id): void
    {
        try {
            ServiceTypeMaster::findOrFail($id)->delete();
            Flux::toast(text: 'Service type #'.$id.' deleted.', variant: 'success');
        } catch (QueryException) {
            Flux::toast(
                text: 'Cannot delete this service type — it is still referenced by one or more job descriptions or job cards.',
                variant: 'danger',
            );
        }
    }

    public function render()
    {
        $search = $this->search;
        $dept = $this->departmentFilter;
        $status = $this->statusFilter;

        $rows = ServiceTypeMaster::query()
            ->with('workshopDepartment:id,name')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $term = '%'.$search.'%';
                    $query->whereLike('name', $term, caseSensitive: false)
                        ->orWhereLike('code', $term, caseSensitive: false);
                });
            })
            ->when($dept !== 'all', fn ($q) => $q->where('workshop_department_id', $dept))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('service-type-master::index', [
            'rows' => $rows,
            'departments' => WorkshopDepartmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
