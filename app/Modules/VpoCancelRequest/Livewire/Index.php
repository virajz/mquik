<?php

namespace App\Modules\VpoCancelRequest\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VpoCancelRequest\Models\VpoCancelRequest;
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
#[Title('VPO Cancel Requests')]
class Index extends Component
{
    use SearchesPickerOptions;
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

    public string $vendorSearch = '';

    protected array $sortable = ['id', 'request_no', 'status', 'created_at'];

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
        $this->authorize('vpo_cancel_request.delete');
        VpoCancelRequest::findOrFail($id)->delete();
        Flux::toast(text: 'Cancel request #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'vendorFilter']);
        $this->resetPage();
    }

    #[Computed]
    public function vendors()
    {
        return $this->pickerOptions(
            query: VendorMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'vendor_code'],
            term: $this->vendorSearch,
            selected: is_numeric($this->vendorFilter) ? (int) $this->vendorFilter : null,
            columns: ['id', 'name'],
            limit: 30,
        );
    }

    /**
     * @return array{open:int, pending_response:int, refund_pending:int}
     */
    protected function kpis(): array
    {
        $counts = VpoCancelRequest::query()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $get = fn (string $s) => (int) ($counts[$s] ?? 0);

        $open = $get(VpoCancelRequest::STATUS_REQUESTED) + $get(VpoCancelRequest::STATUS_REVIEWING) + $get(VpoCancelRequest::STATUS_PARTIALLY_ACCEPTED);

        return [
            'open' => $open,
            'pending_response' => $get(VpoCancelRequest::STATUS_REQUESTED) + $get(VpoCancelRequest::STATUS_REVIEWING),
            'refund_pending' => VpoCancelRequest::query()->whereIn('advance_payment_status', ['refund_requested', 'advance_paid'])->count(),
        ];
    }

    /**
     * @return Builder<VpoCancelRequest>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return VpoCancelRequest::query()
            ->with(['vendor:id,name', 'purchaseOrder:id,po_no'])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('request_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('purchaseOrder', fn ($po) => $po->whereLike('po_no', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->vendorFilter !== 'all', fn ($q) => $q->where('vendor_id', (int) $this->vendorFilter));
    }

    /** Stream the Purchase Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('vpo_cancel_request.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'vpo-cancel-purchase-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $types = VpoCancelRequest::requestTypes();
        $reasons = VpoCancelRequest::cancellationReasons();
        $statuses = VpoCancelRequest::statuses();
        $advances = VpoCancelRequest::advancePaymentStatuses();

        return response()->streamDownload(function () use ($rows, $types, $reasons, $statuses, $advances) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Request No', 'Vendor', 'PO', 'Type', 'Reason', 'Lines', 'Status', 'Advance / Refund', 'Raised On']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->request_no,
                    $r->vendor?->name,
                    $r->purchaseOrder?->po_no,
                    $types[$r->cancellation_request_type] ?? '',
                    $reasons[$r->cancellation_reason] ?? '',
                    $r->items_count,
                    $statuses[$r->status] ?? $r->status,
                    $advances[$r->advance_payment_status] ?? '',
                    $r->created_at?->format('Y-m-d'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        return view('vpo-cancel-request::index', [
            'rows' => $rows,
            'statuses' => VpoCancelRequest::statuses(),
            'kpis' => $this->kpis(),
        ]);
    }
}
