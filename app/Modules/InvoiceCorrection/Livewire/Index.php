<?php

namespace App\Modules\InvoiceCorrection\Livewire;

use App\Modules\InvoiceCorrection\Models\InvoiceCorrection;
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
#[Title('Invoice Correction')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'type')]
    public string $invoiceTypeFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'correction_no', 'status', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingInvoiceTypeFilter(): void
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
        $this->authorize('invoice_correction.delete');
        InvoiceCorrection::findOrFail($id)->delete();
        Flux::toast(text: 'Correction #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'invoiceTypeFilter']);
        $this->resetPage();
    }

    /**
     * @return array{pending:int, approved:int, rejected:int, corrected:int}
     */
    protected function kpis(): array
    {
        $counts = InvoiceCorrection::query()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $get = fn (string $s) => (int) ($counts[$s] ?? 0);

        return [
            'pending' => $get(InvoiceCorrection::STATUS_REQUESTED) + $get(InvoiceCorrection::STATUS_UNDER_REVIEW) + $get(InvoiceCorrection::STATUS_ON_HOLD),
            'approved' => $get(InvoiceCorrection::STATUS_APPROVED),
            'rejected' => $get(InvoiceCorrection::STATUS_REJECTED),
            'corrected' => $get(InvoiceCorrection::STATUS_CORRECTED),
        ];
    }

    /**
     * @return Builder<InvoiceCorrection>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return InvoiceCorrection::query()
            ->with(['jobCard:id,job_card_no', 'customer:id,first_name,last_name'])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->invoiceTypeFilter !== 'all', fn ($q) => $q->where('invoice_type', $this->invoiceTypeFilter));
    }

    /** Stream the Invoice Correction Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('invoice_correction.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'invoice-correction-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $types = InvoiceCorrection::requestTypes();
        $invoiceTypes = InvoiceCorrection::invoiceTypes();
        $statuses = InvoiceCorrection::statuses();

        return response()->streamDownload(function () use ($rows, $types, $invoiceTypes, $statuses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Correction No', 'Invoice Ref', 'Invoice Type', 'Correction Type', 'Lines', 'Status', 'Requested', 'Corrected']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->correction_no,
                    $r->invoice_reference,
                    $invoiceTypes[$r->invoice_type] ?? '',
                    $types[$r->correction_request_type] ?? '',
                    $r->items_count,
                    $statuses[$r->status] ?? $r->status,
                    $r->requested_at?->format('Y-m-d H:i'),
                    $r->corrected_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        return view('invoice-correction::index', [
            'rows' => $rows,
            'statuses' => InvoiceCorrection::statuses(),
            'invoiceTypes' => InvoiceCorrection::invoiceTypes(),
            'kpis' => $this->kpis(),
        ]);
    }
}
