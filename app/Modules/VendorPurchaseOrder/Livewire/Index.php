<?php

namespace App\Modules\VendorPurchaseOrder\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorPurchaseOrder\Models\VendorPurchaseOrder;
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
#[Title('Vendor Purchase Orders')]
class Index extends Component
{
    use SearchesPickerOptions;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'ack')]
    public string $ackFilter = 'all';

    #[Url(as: 'vendor')]
    public string $vendorFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    public string $vendorSearch = '';

    protected array $sortable = ['id', 'po_no', 'status', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingAckFilter(): void
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
        $this->authorize('vendor_purchase_order.delete');
        VendorPurchaseOrder::findOrFail($id)->delete();
        Flux::toast(text: 'PO #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'ackFilter', 'vendorFilter']);
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
     * Dashboard counters. "Delayed" = still open past its expected delivery date.
     *
     * @return array{pending:int, delayed:int, cancelled:int}
     */
    protected function kpis(): array
    {
        $counts = VendorPurchaseOrder::query()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $get = fn (string $s) => (int) ($counts[$s] ?? 0);

        $delayed = VendorPurchaseOrder::query()
            ->whereNotIn('status', [VendorPurchaseOrder::STATUS_DELIVERED, VendorPurchaseOrder::STATUS_CANCELLED])
            ->whereNotNull('expected_delivery_date')
            ->whereDate('expected_delivery_date', '<', Carbon::today())
            ->count();

        return [
            'pending' => $get(VendorPurchaseOrder::STATUS_PENDING) + $get(VendorPurchaseOrder::STATUS_ACKNOWLEDGED),
            'delayed' => $delayed,
            'cancelled' => $get(VendorPurchaseOrder::STATUS_CANCELLED),
        ];
    }

    /**
     * @return Builder<VendorPurchaseOrder>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return VendorPurchaseOrder::query()
            ->with(['vendor:id,name', 'priority:id,name', 'jobCard:id,job_card_no'])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('po_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('consignment_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('jobCard', fn ($jc) => $jc->whereLike('job_card_no', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->ackFilter !== 'all', fn ($q) => $q->where('acknowledgement_status', $this->ackFilter))
            ->when($this->vendorFilter !== 'all', fn ($q) => $q->where('vendor_id', (int) $this->vendorFilter));
    }

    /** Stream the Purchase Order (MSQ) Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('vendor_purchase_order.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'purchase-order-msq-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $types = VendorPurchaseOrder::poTypes();
        $statuses = VendorPurchaseOrder::statuses();
        $acks = VendorPurchaseOrder::acknowledgementStatuses();

        return response()->streamDownload(function () use ($rows, $types, $statuses, $acks) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['PO No', 'Vendor', 'Job Card', 'Type', 'Lines', 'Status', 'Acknowledgement', 'Courier', 'Consignment', 'Expected Delivery', 'Raised On']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->po_no,
                    $r->vendor?->name,
                    $r->jobCard?->job_card_no,
                    $types[$r->po_type] ?? '',
                    $r->items_count,
                    $statuses[$r->status] ?? $r->status,
                    $acks[$r->acknowledgement_status] ?? $r->acknowledgement_status,
                    $r->courier_company,
                    $r->consignment_no,
                    $r->expected_delivery_date?->format('Y-m-d'),
                    $r->created_at?->format('Y-m-d'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        return view('vendor-purchase-order::index', [
            'rows' => $rows,
            'statuses' => VendorPurchaseOrder::statuses(),
            'acks' => VendorPurchaseOrder::acknowledgementStatuses(),
            'poTypes' => VendorPurchaseOrder::poTypes(),
            'kpis' => $this->kpis(),
        ]);
    }
}
