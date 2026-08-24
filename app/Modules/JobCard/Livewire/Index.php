<?php

namespace App\Modules\JobCard\Livewire;

use App\Concerns\ScopesToRecord;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\JobCardCancelReasonMaster\Models\JobCardCancelReasonMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
    use ScopesToRecord;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    /** Defaults to the cards still on the floor — closed history is the exception, not the view. */
    #[Url(as: 'status')]
    public string $statusFilter = 'pending';

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

    public ?int $cancellingId = null;

    public ?int $cancel_reason_id = null;

    public ?string $cancellation_notes = null;

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

    public function openCancelModal(int $id): void
    {
        $this->authorize('job_card.cancel');

        $this->cancellingId = $id;
        $this->cancel_reason_id = null;
        $this->cancellation_notes = null;
        $this->resetErrorBag();

        Flux::modal('job-card-cancel')->show();
    }

    public function confirmCancel(): void
    {
        $this->authorize('job_card.cancel');

        $data = $this->validate([
            'cancellingId' => ['required', 'integer', 'exists:job_cards,id'],
            'cancel_reason_id' => ['required', 'integer', Rule::exists('job_card_cancel_reasons', 'id')->where('is_active', true)],
            'cancellation_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $jc = JobCard::findOrFail($data['cancellingId']);

        if (in_array($jc->status, [JobCard::STATUS_CANCELLED, JobCard::STATUS_CLOSED, JobCard::STATUS_COMPLETED], true)) {
            Flux::toast(text: 'Cannot cancel a '.$jc->status.' job card.', variant: 'warning');
            Flux::modal('job-card-cancel')->close();

            return;
        }

        DB::transaction(function () use ($jc, $data) {
            $jc->update([
                'status' => JobCard::STATUS_CANCELLED,
                'cancel_reason_id' => $data['cancel_reason_id'],
                'cancelled_at' => now(),
                'cancellation_notes' => filled($data['cancellation_notes'] ?? null) ? strtoupper($data['cancellation_notes']) : null,
            ]);
        });

        Flux::toast(text: 'Job Card '.$jc->job_card_no.' cancelled.', variant: 'success');

        $this->cancellingId = null;
        $this->cancel_reason_id = null;
        $this->cancellation_notes = null;

        Flux::modal('job-card-cancel')->close();
    }

    #[Computed]
    public function cancelReasons()
    {
        return JobCardCancelReasonMaster::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
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
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter === 'pending', fn ($q) => $q->whereIn('status', JobCard::pendingStatuses()))
            ->when(! in_array($this->statusFilter, ['all', 'pending'], true), fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->advisorFilter !== 'all', fn ($q) => $q->where('assigned_advisor_id', (int) $this->advisorFilter))
            ->when($this->technicianFilter !== 'all', fn ($q) => $q->where('assigned_technician_id', (int) $this->technicianFilter))
            ->when($this->deptFilter !== 'all', fn ($q) => $q->where('workshop_department_id', (int) $this->deptFilter))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('opened_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('opened_at', '<=', $this->dateTo))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->tap(fn ($q) => $this->applyRecordScope($q))
            ->paginate(20);

        return view('job-card::index', [
            'rows' => $rows,
            'statuses' => JobCard::statuses(),
        ]);
    }
}
