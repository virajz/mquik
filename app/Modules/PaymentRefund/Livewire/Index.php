<?php

namespace App\Modules\PaymentRefund\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\PaymentRefund\Models\PaymentRefund;
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
#[Title('Payment Refund')]
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

    protected array $sortable = ['id', 'refund_no', 'status', 'amount', 'created_at'];

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
        $this->authorize('payment_refund.delete');
        PaymentRefund::findOrFail($id)->delete();
        Flux::toast(text: 'Refund #'.$id.' deleted.', variant: 'success');
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
     * @return array{pending:int, received_today:int, value_month:float}
     */
    protected function kpis(): array
    {
        $pending = PaymentRefund::query()
            ->whereIn('status', [PaymentRefund::STATUS_REQUESTED, PaymentRefund::STATUS_ON_HOLD])
            ->count();

        $receivedToday = PaymentRefund::query()
            ->where('status', PaymentRefund::STATUS_REFUNDED)
            ->whereDate('refunded_at', Carbon::today())
            ->count();

        $valueMonth = (float) PaymentRefund::query()
            ->where('status', PaymentRefund::STATUS_REFUNDED)
            ->whereBetween('refunded_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->sum('amount');

        return ['pending' => $pending, 'received_today' => $receivedToday, 'value_month' => $valueMonth];
    }

    /**
     * @return Builder<PaymentRefund>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return PaymentRefund::query()
            ->with(['vendor:id,name'])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('refund_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('reference_no', '%'.$search.'%', caseSensitive: false);
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->vendorFilter !== 'all', fn ($q) => $q->where('vendor_id', (int) $this->vendorFilter));
    }

    /** Stream the Payment Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('payment_refund.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'payment-refund-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $types = PaymentRefund::refundTypes();
        $modes = PaymentRefund::refundModes();
        $statuses = PaymentRefund::statuses();

        return response()->streamDownload(function () use ($rows, $types, $modes, $statuses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Refund No', 'Vendor', 'Type', 'Mode', 'Amount', 'Status', 'Requested', 'Refunded']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->refund_no,
                    $r->vendor?->name,
                    $types[$r->refund_type] ?? '',
                    $modes[$r->refund_mode] ?? '',
                    $r->amount !== null ? number_format((float) $r->amount, 2, '.', '') : '',
                    $statuses[$r->status] ?? $r->status,
                    $r->requested_at?->format('Y-m-d H:i'),
                    $r->refunded_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        return view('payment-refund::index', [
            'rows' => $rows,
            'statuses' => PaymentRefund::statuses(),
            'kpis' => $this->kpis(),
        ]);
    }
}
