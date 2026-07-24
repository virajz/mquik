<?php

namespace App\Modules\EmployeeMaster\Livewire;

use App\Modules\DepartmentMaster\Models\DepartmentMaster;
use App\Modules\DesignationMaster\Models\DesignationMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use Flux\Flux;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Employees')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'dept')]
    public string $deptFilter = 'all';

    #[Url(as: 'des')]
    public string $designationFilter = 'all';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'name';

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    protected array $sortable = ['id', 'employee_code', 'name', 'designation_id', 'department_id', 'joining_date', 'is_active'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDeptFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDesignationFilter(): void
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
        $this->authorize('employee_master.delete');

        try {
            EmployeeMaster::findOrFail($id)->delete();
            Flux::toast(text: 'Employee #'.$id.' deleted.', variant: 'success');
        } catch (QueryException) {
            Flux::toast(text: 'Cannot delete this employee — they have related records.', variant: 'danger');
        }
    }

    public function render()
    {
        $rows = EmployeeMaster::query()
            ->with(['department:id,name', 'designation:id,name'])
            ->when($this->search !== '', fn ($q) => $q->search($this->search))
            ->when($this->deptFilter !== 'all', fn ($q) => $q->where('department_id', $this->deptFilter))
            ->when($this->designationFilter !== 'all', fn ($q) => $q->where('designation_id', $this->designationFilter))
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('employee-master::index', [
            'rows' => $rows,
            'departments' => DepartmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'designations' => DesignationMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
