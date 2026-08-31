<?php

namespace App\Modules\VehicleInspectionOrder\Livewire;

use App\Concerns\ScopesToRecord;
use App\Modules\BayMaster\Models\BayMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrder;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
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
    use ScopesToRecord;
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

    #[Url(as: 'dept')]
    public string $departmentFilter = 'all';

    #[Url(as: 'stype')]
    public string $serviceTypeFilter = 'all';

    #[Url(as: 'advisor')]
    public string $advisorFilter = 'all';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    /** Which stamp the date range applies to — ordered, started or completed. */
    #[Url(as: 'on')]
    public string $dateField = 'ordered_at';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    /**
     * Every column heading sorts. Related names cannot be ordered by their
     * foreign key — that sorts by row id, not alphabetically — so they get a
     * correlated subquery instead; see `sortExpression()`.
     */
    protected array $sortable = [
        'order_no', 'ordered_at', 'created_at', 'started_at', 'ended_at',
        'status', 'priority_id', 'items_count',
        'job_card_no', 'registration_no', 'vehicle_name',
        'department', 'service_type', 'advisor', 'technician', 'bay',
    ];

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

    public function updatingDepartmentFilter(): void
    {
        $this->resetPage();
    }

    public function updatingServiceTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingAdvisorFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function updatingDateField(): void
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

    /**
     * How a sortable column becomes something the database can order by.
     *
     * Relation columns return a correlated subquery: ordering by `technician_id`
     * would sort by row id, which is not alphabetical and reads as random.
     */
    protected function sortExpression(): mixed
    {
        return match ($this->sortBy) {
            'job_card_no' => JobCard::select('job_card_no')
                ->whereColumn('job_cards.id', 'vehicle_inspection_orders.job_card_id')
                ->limit(1),
            'registration_no' => CustomerVehicleMaster::select('registration_no')
                ->join('job_cards', 'job_cards.customer_vehicle_id', '=', 'customer_vehicles.id')
                ->whereColumn('job_cards.id', 'vehicle_inspection_orders.job_card_id')
                ->limit(1),
            'vehicle_name' => VehicleModelMaster::select('vehicle_models.name')
                ->join('customer_vehicles', 'customer_vehicles.model_id', '=', 'vehicle_models.id')
                ->join('job_cards', 'job_cards.customer_vehicle_id', '=', 'customer_vehicles.id')
                ->whereColumn('job_cards.id', 'vehicle_inspection_orders.job_card_id')
                ->limit(1),
            'department' => WorkshopDepartmentMaster::select('name')
                ->whereColumn('workshop_departments.id', 'vehicle_inspection_orders.department_id')
                ->limit(1),
            'service_type' => ServiceTypeMaster::select('name')
                ->whereColumn('service_types.id', 'vehicle_inspection_orders.service_type_id')
                ->limit(1),
            'advisor' => EmployeeMaster::select('name')
                ->whereColumn('employees.id', 'vehicle_inspection_orders.advisor_id')
                ->limit(1),
            'technician' => EmployeeMaster::select('name')
                ->whereColumn('employees.id', 'vehicle_inspection_orders.technician_id')
                ->limit(1),
            'bay' => BayMaster::select('name')
                ->whereColumn('bays.id', 'vehicle_inspection_orders.bay_id')
                ->limit(1),
            'priority_id' => PriorityMaster::select('name')
                ->whereColumn('priorities.id', 'vehicle_inspection_orders.priority_id')
                ->limit(1),
            default => $this->sortBy,
        };
    }

    public function delete(int $id): void
    {
        $this->authorize('vehicle_inspection_order.delete');

        VehicleInspectionOrder::findOrFail($id)->delete();

        Flux::toast(text: 'Work Order #'.$id.' deleted.', variant: 'success');
    }

    public function clearDateRange(): void
    {
        $this->reset(['dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search', 'statusFilter', 'priorityFilter', 'technicianFilter', 'bayFilter',
            'departmentFilter', 'serviceTypeFilter', 'advisorFilter', 'dateFrom', 'dateTo', 'dateField',
        ]);
        $this->resetPage();
    }

    #[Computed]
    public function technicians()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function departments()
    {
        return WorkshopDepartmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function serviceTypes()
    {
        return ServiceTypeMaster::query()
            ->where('is_active', true)
            ->when($this->departmentFilter !== 'all', fn ($q) => $q->where('workshop_department_id', (int) $this->departmentFilter))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function bays()
    {
        return BayMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /**
     * The four counters the CSV asks for.
     *
     * @return array{pending:int, active:int, completed:int, cancelled:int}
     */
    protected function kpis(): array
    {
        $counts = VehicleInspectionOrder::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'pending' => (int) ($counts[VehicleInspectionOrder::STATUS_ASSIGNMENT_PENDING] ?? 0),
            // "Active" spans everything a technician currently holds.
            'active' => (int) ($counts[VehicleInspectionOrder::STATUS_ASSIGNED] ?? 0)
                + (int) ($counts[VehicleInspectionOrder::STATUS_WIP] ?? 0)
                + (int) ($counts[VehicleInspectionOrder::STATUS_ON_HOLD] ?? 0),
            'completed' => (int) ($counts[VehicleInspectionOrder::STATUS_COMPLETED] ?? 0),
            'cancelled' => (int) ($counts[VehicleInspectionOrder::STATUS_CANCELLED] ?? 0),
        ];
    }

    /** Whitelisted so the range can never be pointed at an arbitrary column. */
    protected function dateColumn(): string
    {
        return in_array($this->dateField, ['ordered_at', 'started_at', 'ended_at', 'created_at'], true)
            ? $this->dateField
            : 'ordered_at';
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
                'priority:id,name',
                'bay:id,name',
                'advisor:id,name',
                'department:id,name',
                'serviceType:id,name',
                'jobCard.customerVehicle.model:id,name',
                'template:id,name',
            ])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->priorityFilter !== 'all', fn ($q) => $q->where('priority_id', (int) $this->priorityFilter))
            ->when($this->technicianFilter !== 'all', fn ($q) => $q->where('technician_id', (int) $this->technicianFilter))
            ->when($this->bayFilter !== 'all', fn ($q) => $q->where('bay_id', (int) $this->bayFilter))
            ->when($this->departmentFilter !== 'all', fn ($q) => $q->where('department_id', (int) $this->departmentFilter))
            ->when($this->serviceTypeFilter !== 'all', fn ($q) => $q->where('service_type_id', (int) $this->serviceTypeFilter))
            ->when($this->advisorFilter !== 'all', fn ($q) => $q->where('advisor_id', (int) $this->advisorFilter))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate($this->dateColumn(), '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate($this->dateColumn(), '<=', $this->dateTo))
            ->orderBy($this->sortExpression(), $this->sortDirection)
            ->tap(fn ($q) => $this->applyRecordScope($q))
            ->paginate(20);

        return view('vehicle-inspection-order::index', [
            'rows' => $rows,
            'statuses' => VehicleInspectionOrder::statuses(),
            'priorities' => PriorityMaster::forScope(PriorityMaster::APPLIES_WORKSHOP)->pluck('name', 'id'),
            'kpis' => $this->kpis(),
            'advisors' => $this->technicians,
        ]);
    }
}
