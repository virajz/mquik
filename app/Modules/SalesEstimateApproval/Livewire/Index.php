<?php

namespace App\Modules\SalesEstimateApproval\Livewire;

use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\SalesEstimateApproval\Models\SalesEstimateApproval;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Sales Estimate Approval')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'auth')]
    public string $authFilter = 'all';

    #[Url(as: 'company')]
    public string $companyFilter = 'all';

    /** When set, the "approved on" report shows approvals fully approved on this date. */
    #[Url(as: 'approved')]
    public string $approvedOnDate = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'approval_no', 'status', 'created_at', 'approved_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingAuthFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCompanyFilter(): void
    {
        $this->resetPage();
    }

    public function updatingApprovedOnDate(): void
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
        $this->authorize('sales_estimate_approval.delete');

        SalesEstimateApproval::findOrFail($id)->delete();

        Flux::toast(text: 'Estimate approval #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'authFilter', 'companyFilter', 'approvedOnDate']);
        $this->resetPage();
    }

    #[Computed]
    public function companies()
    {
        return InsuranceCompanyMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /** Statuses that still count as awaiting an approver's response. */
    protected function openStatuses(): array
    {
        return [
            SalesEstimateApproval::STATUS_SENT,
            SalesEstimateApproval::STATUS_PARTIALLY_APPROVED,
            SalesEstimateApproval::STATUS_QUERY_RAISED,
            SalesEstimateApproval::STATUS_REVISED_RESENT,
            SalesEstimateApproval::STATUS_NO_RESPONSE,
        ];
    }

    /**
     * The four KPIs the spec asks for.
     *
     * @return array{pending_customer:int, pending_insurance:int, today_approved:int, custom_approved:int}
     */
    protected function kpis(): array
    {
        $open = $this->openStatuses();

        $pendingCustomer = SalesEstimateApproval::query()
            ->whereIn('approval_authorisation', ['customer', 'both'])
            ->whereNull('customer_approved_at')
            ->whereIn('status', $open)
            ->count();

        $pendingInsurance = SalesEstimateApproval::query()
            ->whereIn('approval_authorisation', ['insurance', 'both'])
            ->whereNull('insurance_approved_at')
            ->whereIn('status', $open)
            ->count();

        $todayApproved = SalesEstimateApproval::query()
            ->whereDate('approved_at', Carbon::today())
            ->count();

        $customApproved = $this->approvedOnDate !== ''
            ? SalesEstimateApproval::query()->whereDate('approved_at', $this->approvedOnDate)->count()
            : 0;

        return [
            'pending_customer' => $pendingCustomer,
            'pending_insurance' => $pendingInsurance,
            'today_approved' => $todayApproved,
            'custom_approved' => $customApproved,
        ];
    }

    /**
     * @return Builder<SalesEstimateApproval>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return SalesEstimateApproval::query()
            ->with([
                'jobCard:id,job_card_no',
                'customerVehicle:id,registration_no',
                'insuranceCompany:id,name',
                'salesEstimate:id,estimate_no',
            ])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('approval_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('jobCard', fn ($jc) => $jc->whereLike('job_card_no', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->authFilter !== 'all', fn ($q) => $q->where('approval_authorisation', $this->authFilter))
            ->when($this->companyFilter !== 'all', fn ($q) => $q->where('insurance_company_id', (int) $this->companyFilter))
            ->when($this->approvedOnDate !== '', fn ($q) => $q->whereDate('approved_at', $this->approvedOnDate));
    }

    /** Stream the Estimate Analysis Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('sales_estimate_approval.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'estimate-analysis-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $statuses = SalesEstimateApproval::statuses();
        $auths = SalesEstimateApproval::approvalAuthorisations();
        $types = SalesEstimateApproval::approvalTypes();

        return response()->streamDownload(function () use ($rows, $statuses, $auths, $types) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Approval No', 'Job Card', 'Vehicle', 'Estimate', 'Insurer', 'Type', 'Authorisation', 'Lines', 'Status', 'Approved At']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->approval_no,
                    $r->jobCard?->job_card_no,
                    $r->customerVehicle?->registration_no,
                    $r->salesEstimate?->estimate_no,
                    $r->insuranceCompany?->name,
                    $types[$r->approval_type] ?? '',
                    $auths[$r->approval_authorisation] ?? '',
                    $r->items_count,
                    $statuses[$r->status] ?? $r->status,
                    $r->approved_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('sales-estimate-approval::index', [
            'rows' => $rows,
            'statuses' => SalesEstimateApproval::statuses(),
            'authorisations' => SalesEstimateApproval::approvalAuthorisations(),
            'kpis' => $this->kpis(),
        ]);
    }
}
