<?php

namespace App\Modules\PurchaseReport\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiry;
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
#[Title('Purchase Report')]
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
     * @return Builder<VendorPurchaseInquiry>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return VendorPurchaseInquiry::query()
            ->with([
                'vendor:id,name',
                'jobCard:id,job_card_no',
                'priority:id,name',
            ])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('vpi_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('jobCard', fn ($jc) => $jc->whereLike('job_card_no', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter !== 'all', fn ($q) => $q->where('inquiry_type', $this->typeFilter))
            ->when($this->vendorFilter !== 'all', fn ($q) => $q->where('vendor_id', (int) $this->vendorFilter))
            ->when($this->fromDate !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->fromDate))
            ->when($this->toDate !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->toDate))
            ->latest('created_at');
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
        return VendorPurchaseInquiry::statuses();
    }

    /** @return array<string, string> */
    #[Computed]
    public function inquiryTypes(): array
    {
        return VendorPurchaseInquiry::inquiryTypes();
    }

    /** @return array<string, string> */
    #[Computed]
    public function paymentTerms(): array
    {
        return VendorPurchaseInquiry::paymentTerms();
    }

    /** Stream the filtered report as CSV — exactly what's on screen. */
    public function download(): StreamedResponse
    {
        $this->authorize('purchase_report.export');

        $rows = $this->baseQuery()->get();
        $filename = 'purchase-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $paymentTerms = VendorPurchaseInquiry::paymentTerms();

        return response()->streamDownload(function () use ($rows, $paymentTerms) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['RFQ No', 'Vendor', 'Type', 'Job Card', 'Parts', 'Payment Terms', 'Priority', 'Status', 'Raised On']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->vpi_no,
                    $r->vendor?->name,
                    VendorPurchaseInquiry::inquiryTypes()[$r->inquiry_type] ?? $r->inquiry_type,
                    $r->jobCard?->job_card_no,
                    $r->items_count,
                    $paymentTerms[$r->payment_term] ?? '',
                    $r->priority?->name,
                    VendorPurchaseInquiry::statuses()[$r->status] ?? $r->status,
                    $r->created_at?->format('Y-m-d'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $paginator = $this->baseQuery()->paginate(25);

        return view('purchase-report::index', [
            'paginator' => $paginator,
        ]);
    }
}
