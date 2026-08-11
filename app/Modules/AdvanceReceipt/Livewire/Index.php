<?php

namespace App\Modules\AdvanceReceipt\Livewire;

use App\Modules\AdvanceReceipt\Models\AdvanceReceipt;
use App\Modules\PaymentModeMaster\Models\PaymentModeMaster;
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
#[Title('Advance Receipt Entry')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'mode')]
    public string $modeFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'receipt_no', 'payment_status', 'amount', 'created_at', 'received_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingModeFilter(): void
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
        $this->authorize('advance_receipt.delete');

        AdvanceReceipt::findOrFail($id)->delete();

        Flux::toast(text: 'Advance receipt #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'modeFilter']);
        $this->resetPage();
    }

    #[Computed]
    public function paymentModes()
    {
        return PaymentModeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /**
     * @return array{count:int, received:float, partial:int, cancelled:int}
     */
    protected function kpis(): array
    {
        $active = AdvanceReceipt::query()->whereNotIn('payment_status', [
            AdvanceReceipt::STATUS_CANCELLED, AdvanceReceipt::STATUS_FAILED, AdvanceReceipt::STATUS_REFUNDED,
        ]);

        return [
            'count' => (clone $active)->count(),
            'received' => (float) (clone $active)->sum('amount'),
            'partial' => AdvanceReceipt::query()->where('payment_status', AdvanceReceipt::STATUS_PARTIALLY_RECEIVED)->count(),
            'cancelled' => AdvanceReceipt::query()->whereIn('payment_status', [
                AdvanceReceipt::STATUS_CANCELLED, AdvanceReceipt::STATUS_FAILED, AdvanceReceipt::STATUS_REFUNDED,
            ])->count(),
        ];
    }

    /**
     * @return Builder<AdvanceReceipt>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return AdvanceReceipt::query()
            ->with([
                'jobCard:id,job_card_no',
                'customer:id,first_name,last_name',
                'paymentMode:id,name',
            ])
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('payment_status', $this->statusFilter))
            ->when($this->modeFilter !== 'all', fn ($q) => $q->where('payment_mode_id', (int) $this->modeFilter));
    }

    /** Stream the Receipt Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('advance_receipt.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'receipt-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $statuses = AdvanceReceipt::paymentStatuses();

        return response()->streamDownload(function () use ($rows, $statuses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Receipt No', 'Job Card', 'Customer', 'Payment Mode', 'Amount', 'Reference', 'Status', 'Received At']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->receipt_no,
                    $r->jobCard?->job_card_no,
                    $r->customer?->name,
                    $r->paymentMode?->name,
                    number_format((float) $r->amount, 2, '.', ''),
                    $r->reference_no ?: $r->cheque_no,
                    $statuses[$r->payment_status] ?? $r->payment_status,
                    $r->received_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('advance-receipt::index', [
            'rows' => $rows,
            'statuses' => AdvanceReceipt::paymentStatuses(),
            'kpis' => $this->kpis(),
        ]);
    }
}
