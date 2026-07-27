<?php

namespace App\Modules\OutsideLabourStatusReport\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\OutsideLabourInquiry\Models\OutsideLabourInquiry;
use App\Modules\ServiceSpecialistMaster\Models\ServiceSpecialistMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
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
#[Title('Outside Labour Status Report')]
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

    #[Url(as: 'vendor')]
    public string $vendorFilter = 'all';

    #[Url(as: 'from')]
    public string $fromDate = '';

    #[Url(as: 'to')]
    public string $toDate = '';

    public string $vendorSearch = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'typeFilter', 'vendorFilter', 'fromDate', 'toDate']);
        $this->resetPage();
    }

    /**
     * The report query, shared by the on-screen table and the CSV download.
     *
     * @return Builder<OutsideLabourInquiry>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return OutsideLabourInquiry::query()
            ->with([
                'inquiryType:id,name',
                'vendor:id,name',
                'jobCard:id,job_card_no',
                'priority:id,name',
                'rejectionReason:id,name',
            ])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('inquiry_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('jobCard', fn ($jc) => $jc->whereLike('job_card_no', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter !== 'all', fn ($q) => $q->where('inquiry_type_id', (int) $this->typeFilter))
            ->when($this->vendorFilter !== 'all', fn ($q) => $q->where('vendor_id', (int) $this->vendorFilter))
            ->when($this->fromDate !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->fromDate))
            ->when($this->toDate !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->toDate))
            ->latest('created_at');
    }

    #[Computed]
    public function inquiryTypes()
    {
        return ServiceSpecialistMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function vendors()
    {
        return $this->pickerOptions(
            query: VendorMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'code'],
            term: $this->vendorSearch,
            selected: is_numeric($this->vendorFilter) ? (int) $this->vendorFilter : null,
            columns: ['id', 'name'],
            limit: 30,
        );
    }

    /** @return array<string, string> */
    #[Computed]
    public function statuses(): array
    {
        return OutsideLabourInquiry::statuses();
    }

    /** Stream the filtered report as CSV — exactly what's on screen. */
    public function download(): StreamedResponse
    {
        $this->authorize('outside_labour_status_report.export');

        $rows = $this->baseQuery()->get();
        $filename = 'outside-labour-status-'.Carbon::now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Inquiry No', 'Type', 'Vendor', 'Job Card', 'Priority', 'Status', 'Promised From', 'Promised To', 'Rejection Reason', 'Raised On']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->inquiry_no,
                    $r->inquiryType?->name,
                    $r->vendor?->name,
                    $r->jobCard?->job_card_no,
                    $r->priority?->name,
                    OutsideLabourInquiry::statuses()[$r->status] ?? $r->status,
                    $r->promised_from?->format('Y-m-d H:i'),
                    $r->promised_to?->format('Y-m-d H:i'),
                    $r->rejectionReason?->name,
                    $r->created_at?->format('Y-m-d'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $paginator = $this->baseQuery()->paginate(25);

        return view('outside-labour-status-report::index', [
            'paginator' => $paginator,
        ]);
    }
}
