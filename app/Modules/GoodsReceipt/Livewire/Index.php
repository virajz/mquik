<?php

namespace App\Modules\GoodsReceipt\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\GoodsReceipt\Models\GoodsReceipt;
use App\Modules\GoodsReceipt\Models\GoodsReceiptItem;
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
#[Title('Goods Receive & Verification')]
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

    protected array $sortable = ['id', 'grn_no', 'status', 'created_at'];

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
        $this->authorize('goods_receipt.delete');
        GoodsReceipt::findOrFail($id)->delete();
        Flux::toast(text: 'GRN #'.$id.' deleted.', variant: 'success');
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
     * "Rejected materials" counts item lines whose physical verification flagged a problem.
     *
     * @return array{today:int, pending:int, rejected_materials:int}
     */
    protected function kpis(): array
    {
        $badOutcomes = ['physical_damage', 'wrong_part', 'manufacturing_defect', 'missing_item', 'expired_material', 'packaging_damage'];

        return [
            'today' => GoodsReceipt::query()->whereDate('created_at', Carbon::today())->count(),
            'pending' => GoodsReceipt::query()->where('status', GoodsReceipt::STATUS_PENDING)->count(),
            'rejected_materials' => GoodsReceiptItem::query()->whereIn('physical_verification', $badOutcomes)->count(),
        ];
    }

    /**
     * @return Builder<GoodsReceipt>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return GoodsReceipt::query()
            ->with(['vendor:id,name', 'purchaseOrder:id,po_no'])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('grn_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('purchaseOrder', fn ($po) => $po->whereLike('po_no', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->vendorFilter !== 'all', fn ($q) => $q->where('vendor_id', (int) $this->vendorFilter));
    }

    /** Stream the Purchase Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('goods_receipt.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'goods-receipt-purchase-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $types = GoodsReceipt::receiptTypes();
        $statuses = GoodsReceipt::statuses();
        $performances = GoodsReceipt::deliveryPerformances();

        return response()->streamDownload(function () use ($rows, $types, $statuses, $performances) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['GRN No', 'Vendor', 'PO', 'Receipt Type', 'Delivery', 'Lines', 'Status', 'Received On']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->grn_no,
                    $r->vendor?->name,
                    $r->purchaseOrder?->po_no,
                    $types[$r->goods_receipt_type] ?? '',
                    $performances[$r->delivery_performance] ?? '',
                    $r->items_count,
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

        return view('goods-receipt::index', [
            'rows' => $rows,
            'statuses' => GoodsReceipt::statuses(),
            'kpis' => $this->kpis(),
        ]);
    }
}
