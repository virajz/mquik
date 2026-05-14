<?php

namespace App\Modules\JobCard\Livewire;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Job Cards')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'advisor')]
    public string $advisorFilter = 'all';

    #[Url(as: 'tech')]
    public string $technicianFilter = 'all';

    #[Url(as: 'dept')]
    public string $deptFilter = 'all';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'opened_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'job_card_no', 'opened_at', 'promised_at', 'status', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingAdvisorFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTechnicianFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDeptFilter(): void
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
        $this->authorize('job_card.delete');

        JobCard::findOrFail($id)->delete();

        Flux::toast(text: 'Job Card #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'advisorFilter', 'technicianFilter', 'deptFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function departments()
    {
        return WorkshopDepartmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = JobCard::query()
            ->with([
                'customer:id,first_name,last_name,phone',
                'customerVehicle:id,registration_no,model_id',
                'customerVehicle.model:id,name,brand_id',
                'customerVehicle.model.brand:id,name',
                'workshopDepartment:id,name',
                'advisor:id,name',
                'technician:id,name',
            ])
            ->withCount(['complaints', 'inventoryItems'])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('job_card_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('customer', fn ($c) => $c->whereLike('first_name', '%'.$search.'%', caseSensitive: false)
                        ->orWhereLike('phone', '%'.$search.'%', caseSensitive: false))
                    ->orWhereHas('customerVehicle', fn ($v) => $v->whereLike('registration_no', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->advisorFilter !== 'all', fn ($q) => $q->where('assigned_advisor_id', (int) $this->advisorFilter))
            ->when($this->technicianFilter !== 'all', fn ($q) => $q->where('assigned_technician_id', (int) $this->technicianFilter))
            ->when($this->deptFilter !== 'all', fn ($q) => $q->where('workshop_department_id', (int) $this->deptFilter))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('opened_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('opened_at', '<=', $this->dateTo))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('job-card::index', [
            'rows' => $rows,
            'statuses' => JobCard::statuses(),
        ]);
    }
}
