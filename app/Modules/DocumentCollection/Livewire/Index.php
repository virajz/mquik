<?php

namespace App\Modules\DocumentCollection\Livewire;

use App\Concerns\ScopesToRecord;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DocumentCollection\Models\DocumentCollection;
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
#[Title('Document Collection')]
class Index extends Component
{
    use ScopesToRecord;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    /** Defaults to collections still being chased; finished ones are the exception. */
    #[Url(as: 'status')]
    public string $statusFilter = 'open';

    #[Url(as: 'source')]
    public string $requestTypeFilter = 'all';

    #[Url(as: 'dept')]
    public string $deptFilter = 'all';

    #[Url(as: 'createdby')]
    public string $createdByFilter = 'all';

    #[Url(as: 'collectedby')]
    public string $collectedByFilter = 'all';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    /** Whitelist sortable columns — never trust the URL. */
    protected array $sortable = ['id', 'doc_collection_no', 'status', 'created_at', 'requested_at', 'received_at', 'request_type'];

    /** Headings ordered by a related name, not an own column. */
    protected function sortSubqueries(): array
    {
        return [
            'vehicle' => CustomerVehicleMaster::select('registration_no')->whereColumn('customer_vehicles.id', 'document_collections.customer_vehicle_id'),
            'job_card' => JobCard::select('job_card_no')->whereColumn('job_cards.id', 'document_collections.job_card_id'),
            'department' => WorkshopDepartmentMaster::select('name')->whereColumn('workshop_departments.id', 'document_collections.department_id'),
            'created_by' => EmployeeMaster::select('name')->whereColumn('employees.id', 'document_collections.created_by_advisor_id'),
            'collected_by' => EmployeeMaster::select('name')->whereColumn('employees.id', 'document_collections.collected_by_driver_id'),
        ];
    }

    /** @return list<string> */
    public static function openStatuses(): array
    {
        return [DocumentCollection::STATUS_PENDING, DocumentCollection::STATUS_REQUESTED];
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'requestTypeFilter', 'deptFilter', 'createdByFilter', 'collectedByFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    #[Computed]
    public function departments()
    {
        return WorkshopDepartmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingRequestTypeFilter(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if (! in_array($column, $this->sortable, true) && ! array_key_exists($column, $this->sortSubqueries())) {
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
        $this->authorize('document_collection.delete');

        DocumentCollection::findOrFail($id)->delete();
        Flux::toast(text: 'Document collection deleted.', variant: 'success');
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = DocumentCollection::query()
            ->with([
                'customer:id,first_name,last_name,phone',
                'customerVehicle:id,registration_no,model_id',
                'customerVehicle.model:id,name,brand_id',
                'customerVehicle.model.brand:id,name',
                'insuranceCompany:id,name',
                'jobCard:id,job_card_no',
                'department:id,name',
                'advisor:id,name',
                'driver:id,name',
            ])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter === 'open', fn ($q) => $q->whereIn('status', self::openStatuses()))
            ->when(! in_array($this->statusFilter, ['all', 'open'], true), fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->requestTypeFilter !== 'all', fn ($q) => $q->where('request_type', $this->requestTypeFilter))
            ->when($this->deptFilter !== 'all', fn ($q) => $q->where('department_id', (int) $this->deptFilter))
            ->when($this->createdByFilter !== 'all', fn ($q) => $q->where('created_by_advisor_id', (int) $this->createdByFilter))
            ->when($this->collectedByFilter !== 'all', fn ($q) => $q->where('collected_by_driver_id', (int) $this->collectedByFilter))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('requested_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('requested_at', '<=', $this->dateTo))
            ->when(
                isset($this->sortSubqueries()[$this->sortBy]),
                fn ($q) => $q->orderBy($this->sortSubqueries()[$this->sortBy], $this->sortDirection),
                fn ($q) => $q->orderBy($this->sortBy, $this->sortDirection),
            )
            ->tap(fn ($q) => $this->applyRecordScope($q))
            ->paginate(20);

        return view('document-collection::index', [
            'rows' => $rows,
            'statuses' => DocumentCollection::statuses(),
            'requestTypes' => DocumentCollection::requestTypes(),
        ]);
    }
}
