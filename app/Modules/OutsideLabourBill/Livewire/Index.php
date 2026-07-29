<?php

namespace App\Modules\OutsideLabourBill\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\OutsideLabourBill\Models\OutsideLabourBill;
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
#[Title('Outside Labour Bill Verification')]
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

    protected array $sortable = ['id', 'bill_no', 'status', 'bill_amount', 'created_at'];

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
        $this->authorize('outside_labour_bill.delete');
        OutsideLabourBill::findOrFail($id)->delete();
        Flux::toast(text: 'Bill #'.$id.' deleted.', variant: 'success');
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
     * @return array{pending:int, on_hold:int, rejected:int}
     */
    protected function kpis(): array
    {
        $counts = OutsideLabourBill::query()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $get = fn (string $s) => (int) ($counts[$s] ?? 0);

        return [
            'pending' => $get(OutsideLabourBill::STATUS_REQUESTED) + $get(OutsideLabourBill::STATUS_UNDER_VERIFICATION) + $get(OutsideLabourBill::STATUS_PARTIALLY_VERIFIED),
            'on_hold' => $get(OutsideLabourBill::STATUS_ON_HOLD),
            'rejected' => $get(OutsideLabourBill::STATUS_REJECTED),
        ];
    }

    /**
     * @return Builder<OutsideLabourBill>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return OutsideLabourBill::query()
            ->with(['vendor:id,name', 'order:id,order_no'])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('bill_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('vendor_bill_no', '%'.$search.'%', caseSensitive: false);
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->vendorFilter !== 'all', fn ($q) => $q->where('vendor_id', (int) $this->vendorFilter));
    }

    /** Stream the Outside Labour Status Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('outside_labour_bill.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'outside-labour-bill-status-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $statuses = OutsideLabourBill::statuses();
        $completions = OutsideLabourBill::workCompletionTypes();

        return response()->streamDownload(function () use ($rows, $statuses, $completions) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Bill No', 'Vendor', 'OL Order', 'Vendor Bill No', 'Amount', 'Completion', 'Status', 'Received On']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->bill_no,
                    $r->vendor?->name,
                    $r->order?->order_no,
                    $r->vendor_bill_no,
                    $r->bill_amount !== null ? number_format((float) $r->bill_amount, 2, '.', '') : '',
                    $completions[$r->work_completion_type] ?? '',
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

        return view('outside-labour-bill::index', [
            'rows' => $rows,
            'statuses' => OutsideLabourBill::statuses(),
            'kpis' => $this->kpis(),
        ]);
    }
}
