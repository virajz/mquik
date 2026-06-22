<?php

namespace App\Modules\VehicleInspectionOrder\Livewire;

use App\Modules\BayMaster\Models\BayMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrder;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Vehicle Inspection Orders')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'priority')]
    public string $priorityFilter = 'all';

    #[Url(as: 'tech')]
    public string $technicianFilter = 'all';

    #[Url(as: 'bay')]
    public string $bayFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'order_no', 'status', 'work_priority', 'created_at', 'started_at', 'ended_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPriorityFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTechnicianFilter(): void
    {
        $this->resetPage();
    }

    public function updatingBayFilter(): void
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
        $this->authorize('vehicle_inspection_order.delete');

        VehicleInspectionOrder::findOrFail($id)->delete();

        Flux::toast(text: 'Work Order #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'priorityFilter', 'technicianFilter', 'bayFilter']);
        $this->resetPage();
    }

    #[Computed]
    public function technicians()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function bays()
    {
        return BayMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = VehicleInspectionOrder::query()
            ->with([
                'jobCard:id,job_card_no,customer_id,customer_vehicle_id',
                'jobCard.customer:id,first_name,last_name',
                'jobCard.customerVehicle:id,registration_no',
                'technician:id,name',
                'bay:id,name',
                'template:id,name',
            ])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('order_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('jobCard', fn ($jc) => $jc->whereLike('job_card_no', '%'.$search.'%', caseSensitive: false))
                    ->orWhereHas('jobCard.customerVehicle', fn ($v) => $v->whereLike('registration_no', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->priorityFilter !== 'all', fn ($q) => $q->where('work_priority', $this->priorityFilter))
            ->when($this->technicianFilter !== 'all', fn ($q) => $q->where('technician_id', (int) $this->technicianFilter))
            ->when($this->bayFilter !== 'all', fn ($q) => $q->where('bay_id', (int) $this->bayFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('vehicle-inspection-order::index', [
            'rows' => $rows,
            'statuses' => VehicleInspectionOrder::statuses(),
            'priorities' => VehicleInspectionOrder::priorities(),
        ]);
    }
}
