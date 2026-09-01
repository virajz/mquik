<?php

namespace App\Modules\DigitalInspection\Livewire;

use App\Concerns\ScopesToRecord;
use App\Modules\BayMaster\Models\BayMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DigitalInspection\Models\DigitalInspection;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Digital Inspections')]
class Index extends Component
{
    use ScopesToRecord;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    /** Pending means everything still open — the day's work, not the archive. */
    #[Url(as: 'status')]
    public string $statusFilter = 'pending';

    #[Url(as: 'tech')]
    public string $technicianFilter = 'all';

    #[Url(as: 'template')]
    public string $templateFilter = 'all';

    #[Url(as: 'dept')]
    public string $departmentFilter = 'all';

    #[Url(as: 'stype')]
    public string $serviceTypeFilter = 'all';

    #[Url(as: 'advisor')]
    public string $advisorFilter = 'all';

    #[Url(as: 'floor')]
    public string $floorFilter = 'all';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    /** Which stamp the range applies to — raised, started or completed. */
    #[Url(as: 'on')]
    public string $dateField = 'created_at';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    /**
     * Every heading sorts. Related names cannot be ordered by their foreign key
     * — that sorts by row id, which reads as random — so they get a correlated
     * subquery instead; see `sortExpression()`.
     */
    protected array $sortable = [
        'inspection_no', 'status', 'created_at', 'started_at', 'completed_at',
        'tat_seconds', 'items_count',
        'job_card_no', 'registration_no', 'vehicle_name',
        'template', 'department', 'service_type', 'advisor', 'floor_incharge', 'technician', 'bay',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTechnicianFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTemplateFilter(): void
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
        $this->authorize('digital_inspection.delete');

        DigitalInspection::findOrFail($id)->delete();

        Flux::toast(text: 'Inspection #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search', 'statusFilter', 'technicianFilter', 'templateFilter',
            'departmentFilter', 'serviceTypeFilter', 'advisorFilter', 'floorFilter',
            'dateFrom', 'dateTo', 'dateField',
        ]);
        $this->resetPage();
    }

    public function clearDateRange(): void
    {
        $this->reset(['dateFrom', 'dateTo']);
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

    public function updatingFloorFilter(): void
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
            ->orderBy('name')->get(['id', 'name']);
    }

    /** Staff by designation — offering all 64 employees makes the wrong pick easy. */
    protected function staffDesignated(string $needle)
    {
        return EmployeeMaster::query()
            ->where('is_active', true)
            ->whereHas('designation', fn ($q) => $q->whereRaw('upper(name) like ?', ['%'.mb_strtoupper($needle).'%']))
            ->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function advisors()
    {
        return $this->staffDesignated('ADVISOR');
    }

    #[Computed]
    public function floorIncharges()
    {
        return $this->staffDesignated('FLOOR');
    }

    #[Computed]
    public function templates()
    {
        return InspectionTemplateMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'applies_to']);
    }

    /**
     * How a sortable column becomes something the database can order by.
     *
     * Department, service type and the people all live on the job card or on
     * another master, so each is a correlated subquery: ordering by a foreign
     * key sorts by row id, which is not alphabetical and reads as random.
     */
    protected function sortExpression(): mixed
    {
        return match ($this->sortBy) {
            'job_card_no' => JobCard::select('job_card_no')
                ->whereColumn('job_cards.id', 'digital_inspections.job_card_id')->limit(1),
            'registration_no' => CustomerVehicleMaster::select('registration_no')
                ->join('job_cards', 'job_cards.customer_vehicle_id', '=', 'customer_vehicles.id')
                ->whereColumn('job_cards.id', 'digital_inspections.job_card_id')->limit(1),
            'vehicle_name' => VehicleModelMaster::select('vehicle_models.name')
                ->join('customer_vehicles', 'customer_vehicles.model_id', '=', 'vehicle_models.id')
                ->join('job_cards', 'job_cards.customer_vehicle_id', '=', 'customer_vehicles.id')
                ->whereColumn('job_cards.id', 'digital_inspections.job_card_id')->limit(1),
            'department' => WorkshopDepartmentMaster::select('workshop_departments.name')
                ->join('job_cards', 'job_cards.workshop_department_id', '=', 'workshop_departments.id')
                ->whereColumn('job_cards.id', 'digital_inspections.job_card_id')->limit(1),
            'service_type' => ServiceTypeMaster::select('service_types.name')
                ->join('job_cards', 'job_cards.service_type_id', '=', 'service_types.id')
                ->whereColumn('job_cards.id', 'digital_inspections.job_card_id')->limit(1),
            'template' => InspectionTemplateMaster::select('name')
                ->whereColumn('inspection_templates.id', 'digital_inspections.inspection_template_id')->limit(1),
            'advisor' => EmployeeMaster::select('name')
                ->whereColumn('employees.id', 'digital_inspections.advisor_id')->limit(1),
            'floor_incharge' => EmployeeMaster::select('name')
                ->whereColumn('employees.id', 'digital_inspections.floor_incharge_id')->limit(1),
            'technician' => EmployeeMaster::select('name')
                ->whereColumn('employees.id', 'digital_inspections.assigned_technician_id')->limit(1),
            'bay' => BayMaster::select('name')
                ->whereColumn('bays.id', 'digital_inspections.bay_id')->limit(1),
            default => $this->sortBy,
        };
    }

    /** Whitelisted so the range can never be pointed at an arbitrary column. */
    protected function dateColumn(): string
    {
        return in_array($this->dateField, ['created_at', 'started_at', 'completed_at'], true)
            ? $this->dateField
            : 'created_at';
    }

    /**
     * Elapsed seconds between the two stamps.
     *
     * `extract(epoch from …)` is Postgres-only and the test suite runs on
     * SQLite, so each driver gets the expression it understands.
     */
    protected function tatSecondsExpression(): string
    {
        return DB::connection()->getDriverName() === 'pgsql'
            ? 'extract(epoch from (completed_at - started_at))'
            : '(julianday(completed_at) - julianday(started_at)) * 86400';
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = DigitalInspection::query()
            ->with([
                'jobCard:id,job_card_no,customer_id,customer_vehicle_id',
                'jobCard.customer:id,first_name,last_name',
                'jobCard.customerVehicle:id,registration_no',
                'template:id,name,applies_to',
                'technician:id,name',
                'advisor:id,name',
                'floorIncharge:id,name',
                'bay:id,name',
                'jobCard.workshopDepartment:id,name',
                'jobCard.serviceType:id,name',
                'jobCard.customerVehicle.model:id,name',
            ])
            ->withCount('items')
            // Technician TAT, worked out in the database so the column sorts.
            ->selectRaw('*, '.$this->tatSecondsExpression().' as tat_seconds')
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter === 'pending', fn ($q) => $q->whereIn('status', DigitalInspection::pendingStatuses()))
            ->when(! in_array($this->statusFilter, ['all', 'pending'], true),
                fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->technicianFilter !== 'all', fn ($q) => $q->where('assigned_technician_id', (int) $this->technicianFilter))
            ->when($this->templateFilter !== 'all', fn ($q) => $q->where('inspection_template_id', (int) $this->templateFilter))
            ->when($this->advisorFilter !== 'all', fn ($q) => $q->where('advisor_id', (int) $this->advisorFilter))
            ->when($this->floorFilter !== 'all', fn ($q) => $q->where('floor_incharge_id', (int) $this->floorFilter))
            // Department and service type belong to the job card, not the sheet.
            ->when($this->departmentFilter !== 'all', fn ($q) => $q->whereHas('jobCard',
                fn ($jc) => $jc->where('workshop_department_id', (int) $this->departmentFilter)))
            ->when($this->serviceTypeFilter !== 'all', fn ($q) => $q->whereHas('jobCard',
                fn ($jc) => $jc->where('service_type_id', (int) $this->serviceTypeFilter)))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate($this->dateColumn(), '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate($this->dateColumn(), '<=', $this->dateTo))
            ->orderBy($this->sortExpression(), $this->sortDirection)
            ->tap(fn ($q) => $this->applyRecordScope($q))
            ->paginate(20);

        return view('digital-inspection::index', [
            'rows' => $rows,
            // Filter offers the live statuses; the badge falls back to the
            // retired ones so a historic row still renders its own name.
            'statuses' => DigitalInspection::statuses(),
            'allStatuses' => DigitalInspection::allStatuses(),
            'departments' => $this->departments,
            'serviceTypes' => $this->serviceTypes,
        ]);
    }
}
