<?php

namespace App\Modules\AdvancePayment\Livewire;

use App\Modules\AdvancePayment\Models\AdvancePayment;
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
#[Title('Advance Payment Entry')]
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

    protected array $sortable = ['id', 'payment_no', 'payment_status', 'amount', 'created_at'];

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
        $this->authorize('advance_payment.delete');
        AdvancePayment::findOrFail($id)->delete();
        Flux::toast(text: 'Advance payment #'.$id.' deleted.', variant: 'success');
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
     * @return array{posted:int, cancelled:int, reversed:int, total:float}
     */
    protected function kpis(): array
    {
        $counts = AdvancePayment::query()->selectRaw('payment_status, count(*) as aggregate')->groupBy('payment_status')->pluck('aggregate', 'payment_status');
        $get = fn (string $s) => (int) ($counts[$s] ?? 0);

        return [
            'posted' => $get(AdvancePayment::STATUS_POSTED),
            'cancelled' => $get(AdvancePayment::STATUS_CANCELLED),
            'reversed' => $get(AdvancePayment::STATUS_REVERSED),
            'total' => (float) AdvancePayment::query()->where('payment_status', AdvancePayment::STATUS_POSTED)->sum('amount'),
        ];
    }

    /**
     * @return Builder<AdvancePayment>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return AdvancePayment::query()
            ->with(['vendor:id,name', 'jobCard:id,job_card_no'])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('payment_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('reference_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('jobCard', fn ($jc) => $jc->whereLike('job_card_no', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('payment_status', $this->statusFilter))
            ->when($this->vendorFilter !== 'all', fn ($q) => $q->where('vendor_id', (int) $this->vendorFilter));
    }

    /** Stream the Payment Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('advance_payment.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'advance-payment-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $types = AdvancePayment::advancePaymentTypes();
        $statuses = AdvancePayment::paymentStatuses();

        return response()->streamDownload(function () use ($rows, $types, $statuses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Payment No', 'Vendor', 'Job Card', 'Type', 'Amount', 'Reference', 'Status', 'Paid On']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->payment_no,
                    $r->vendor?->name,
                    $r->jobCard?->job_card_no,
                    $types[$r->advance_payment_type] ?? '',
                    number_format((float) $r->amount, 2, '.', ''),
                    $r->reference_no,
                    $statuses[$r->payment_status] ?? $r->payment_status,
                    $r->paid_at?->format('Y-m-d'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        return view('advance-payment::index', [
            'rows' => $rows,
            'statuses' => AdvancePayment::paymentStatuses(),
            'kpis' => $this->kpis(),
        ]);
    }
}
