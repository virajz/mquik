<?php

namespace App\Modules\IpiReport\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InternalPartsInquiry\Models\InternalPartsInquiry;
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
#[Title('IPI Report')]
class Index extends Component
{
    use SearchesPickerOptions;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'type')]
    public string $typeFilter = 'all';

    #[Url(as: 'req')]
    public string $requestedByFilter = 'all';

    #[Url(as: 'from')]
    public string $fromDate = '';

    #[Url(as: 'to')]
    public string $toDate = '';

    public string $requestedBySearch = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'typeFilter', 'requestedByFilter', 'fromDate', 'toDate']);
        $this->resetPage();
    }

    /**
     * The report query, shared by the on-screen table and the CSV download.
     *
     * @return Builder<InternalPartsInquiry>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return InternalPartsInquiry::query()
            ->with([
                'jobCard:id,job_card_no',
                'requestedBy:id,name',
                'target:id,name',
                'vendor:id,name',
                'priority:id,name',
                'rejectionReason:id,name',
            ])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('ipi_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('jobCard', fn ($jc) => $jc->whereLike('job_card_no', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter !== 'all', fn ($q) => $q->where('inquiry_type', $this->typeFilter))
            ->when($this->requestedByFilter !== 'all', fn ($q) => $q->where('requested_by_employee_id', (int) $this->requestedByFilter))
            ->when($this->fromDate !== '', fn ($q) => $q->whereDate('requested_at', '>=', $this->fromDate))
            ->when($this->toDate !== '', fn ($q) => $q->whereDate('requested_at', '<=', $this->toDate))
            ->latest('requested_at');
    }

    #[Computed]
    public function requesters()
    {
        return $this->pickerOptions(
            query: EmployeeMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'employee_code'],
            term: $this->requestedBySearch,
            selected: is_numeric($this->requestedByFilter) ? (int) $this->requestedByFilter : null,
            columns: ['id', 'name'],
            limit: 30,
        );
    }

    /** @return array<string, string> */
    #[Computed]
    public function statuses(): array
    {
        return InternalPartsInquiry::statuses();
    }

    /** @return array<string, string> */
    #[Computed]
    public function inquiryTypes(): array
    {
        return InternalPartsInquiry::inquiryTypes();
    }

    /** Stream the filtered report as CSV — exactly what's on screen. */
    public function download(): StreamedResponse
    {
        $this->authorize('ipi_report.export');

        $rows = $this->baseQuery()->get();
        $filename = 'ipi-report-'.Carbon::now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['IPI No', 'Type', 'Job Card', 'Requested By', 'Store', 'Vendor', 'Parts', 'Priority', 'Status', 'Rejection Reason', 'Requested At']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->ipi_no,
                    InternalPartsInquiry::inquiryTypes()[$r->inquiry_type] ?? $r->inquiry_type,
                    $r->jobCard?->job_card_no,
                    $r->requestedBy?->name,
                    $r->target?->name,
                    $r->vendor?->name,
                    $r->items_count,
                    $r->priority?->name,
                    InternalPartsInquiry::statuses()[$r->status] ?? $r->status,
                    $r->rejectionReason?->name,
                    $r->requested_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $paginator = $this->baseQuery()->paginate(25);

        return view('ipi-report::index', [
            'paginator' => $paginator,
        ]);
    }
}
