<?php

namespace App\Modules\VendorAdvanceRequest\Livewire;

use App\Modules\VendorAdvanceRequest\Models\VendorAdvanceRequest;
use App\Modules\VendorMaster\Models\VendorMaster;
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
#[Title('Vendor Advance Request')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'vendor')]
    public string $vendorFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'request_no', 'status', 'amount', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingVendorFilter(): void
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
        $this->authorize('vendor_advance_request.delete');
        VendorAdvanceRequest::findOrFail($id)->delete();
        Flux::toast(text: 'Advance request #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'vendorFilter']);
        $this->resetPage();
    }

    #[Computed]
    public function vendors()
    {
        return VendorMaster::query()->where('is_active', true)->orderBy('name')->limit(200)->get(['id', 'name']);
    }

    /**
     * @return array{pending:int, paid:int, rejected:int}
     */
    protected function kpis(): array
    {
        $counts = VendorAdvanceRequest::query()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $get = fn (string $s) => (int) ($counts[$s] ?? 0);

        return [
            'pending' => $get(VendorAdvanceRequest::STATUS_REQUESTED) + $get(VendorAdvanceRequest::STATUS_IN_PROGRESS)
                + $get(VendorAdvanceRequest::STATUS_ON_HOLD) + $get(VendorAdvanceRequest::STATUS_PARTIALLY_PAID),
            'paid' => $get(VendorAdvanceRequest::STATUS_FULLY_PAID),
            'rejected' => $get(VendorAdvanceRequest::STATUS_REJECTED) + $get(VendorAdvanceRequest::STATUS_CANCELLED)
                + $get(VendorAdvanceRequest::STATUS_FAILED),
        ];
    }

    /**
     * @return Builder<VendorAdvanceRequest>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return VendorAdvanceRequest::query()
            ->with(['vendor:id,name', 'jobCard:id,job_card_no'])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('request_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('jobCard', fn ($jc) => $jc->whereLike('job_card_no', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->vendorFilter !== 'all', fn ($q) => $q->where('vendor_id', (int) $this->vendorFilter));
    }

    /** Stream the Payment Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('vendor_advance_request.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'vendor-advance-payment-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $reasons = VendorAdvanceRequest::advanceReasons();
        $statuses = VendorAdvanceRequest::statuses();

        return response()->streamDownload(function () use ($rows, $reasons, $statuses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Request No', 'Vendor', 'Job Card', 'Reason', 'Amount', 'Status', 'Raised On']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->request_no,
                    $r->vendor?->name,
                    $r->jobCard?->job_card_no,
                    $reasons[$r->advance_reason] ?? '',
                    $r->amount !== null ? number_format((float) $r->amount, 2, '.', '') : '',
                    $statuses[$r->status] ?? $r->status,
                    $r->created_at?->format('Y-m-d'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        return view('vendor-advance-request::index', [
            'rows' => $rows,
            'statuses' => VendorAdvanceRequest::statuses(),
            'kpis' => $this->kpis(),
        ]);
    }
}
