<?php

namespace App\Modules\AdvanceReceiptRequest\Livewire;

use App\Modules\AdvanceReceiptRequest\Models\AdvanceReceiptRequest;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Advance Receipt Request')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'purpose')]
    public string $purposeFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'request_no', 'payment_status', 'amount', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPurposeFilter(): void
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
        $this->authorize('advance_receipt_request.delete');

        AdvanceReceiptRequest::findOrFail($id)->delete();

        Flux::toast(text: 'Advance request #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'purposeFilter']);
        $this->resetPage();
    }

    /**
     * @return array{requested:int, partially_paid:int, fully_paid:int, closed:int}
     */
    protected function kpis(): array
    {
        $counts = AdvanceReceiptRequest::query()
            ->selectRaw('payment_status, count(*) as aggregate')
            ->groupBy('payment_status')
            ->pluck('aggregate', 'payment_status');

        $get = fn (string $s) => (int) ($counts[$s] ?? 0);

        return [
            'requested' => $get(AdvanceReceiptRequest::STATUS_REQUESTED),
            'partially_paid' => $get(AdvanceReceiptRequest::STATUS_PARTIALLY_PAID),
            'fully_paid' => $get(AdvanceReceiptRequest::STATUS_FULLY_PAID),
            'closed' => $get(AdvanceReceiptRequest::STATUS_CANCELLED)
                + $get(AdvanceReceiptRequest::STATUS_FAILED)
                + $get(AdvanceReceiptRequest::STATUS_REJECTED)
                + $get(AdvanceReceiptRequest::STATUS_REFUNDED),
        ];
    }

    /**
     * @return Builder<AdvanceReceiptRequest>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return AdvanceReceiptRequest::query()
            ->with([
                'jobCard:id,job_card_no',
                'customer:id,first_name,last_name',
                'salesEstimate:id,estimate_no',
            ])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('request_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('jobCard', fn ($jc) => $jc->whereLike('job_card_no', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('payment_status', $this->statusFilter))
            ->when($this->purposeFilter !== 'all', fn ($q) => $q->where('advance_purpose', $this->purposeFilter));
    }

    /** Stream the Advance Receipt Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('advance_receipt_request.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'advance-receipt-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $purposes = AdvanceReceiptRequest::advancePurposes();
        $statuses = AdvanceReceiptRequest::paymentStatuses();

        return response()->streamDownload(function () use ($rows, $purposes, $statuses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Request No', 'Job Card', 'Customer', 'Estimate', 'Purpose', 'Amount', 'Status', 'Raised On']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->request_no,
                    $r->jobCard?->job_card_no,
                    $r->customer?->name,
                    $r->salesEstimate?->estimate_no,
                    $purposes[$r->advance_purpose] ?? '',
                    $r->amount !== null ? number_format((float) $r->amount, 2, '.', '') : '',
                    $statuses[$r->payment_status] ?? $r->payment_status,
                    $r->created_at?->format('Y-m-d'),
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

        return view('advance-receipt-request::index', [
            'rows' => $rows,
            'statuses' => AdvanceReceiptRequest::paymentStatuses(),
            'purposes' => AdvanceReceiptRequest::advancePurposes(),
            'kpis' => $this->kpis(),
        ]);
    }
}
