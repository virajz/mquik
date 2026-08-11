<?php

namespace App\Modules\StockMismatchApproval\Livewire;

use App\Modules\StockMismatchApproval\Models\StockMismatchApproval;
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
#[Title('Stock Mismatch Approval')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'approval_no', 'approval_status', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
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
        $this->authorize('stock_mismatch_approval.delete');
        StockMismatchApproval::findOrFail($id)->delete();
        Flux::toast(text: 'Approval #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter']);
        $this->resetPage();
    }

    /**
     * @return array{requested:int, under_review:int, approved:int, rejected:int}
     */
    protected function kpis(): array
    {
        return [
            'requested' => StockMismatchApproval::query()->where('approval_status', StockMismatchApproval::STATUS_REQUESTED)->count(),
            'under_review' => StockMismatchApproval::query()->where('approval_status', StockMismatchApproval::STATUS_UNDER_REVIEW)->count(),
            'approved' => StockMismatchApproval::query()->where('approval_status', StockMismatchApproval::STATUS_APPROVED)->count(),
            'rejected' => StockMismatchApproval::query()->where('approval_status', StockMismatchApproval::STATUS_REJECTED)->count(),
        ];
    }

    /**
     * @return Builder<StockMismatchApproval>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return StockMismatchApproval::query()
            ->with(['stockCount:id,count_no', 'requestedBy:id,name', 'requestedTo:id,name'])
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('approval_status', $this->statusFilter));
    }

    /** Stream the Stock Mismatch Approval Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('stock_mismatch_approval.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'stock-mismatch-approval-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $statuses = StockMismatchApproval::approvalStatuses();
        $reasons = StockMismatchApproval::varianceReasons();
        $responses = StockMismatchApproval::managementResponses();

        return response()->streamDownload(function () use ($rows, $statuses, $reasons, $responses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Approval No', 'Stock Count', 'Requested By', 'Requested To', 'Variance Reason', 'Management Response', 'Status', 'Requested At']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->approval_no,
                    $r->stockCount?->count_no,
                    $r->requestedBy?->name,
                    $r->requestedTo?->name,
                    $reasons[$r->variance_reason] ?? '',
                    $responses[$r->management_response] ?? '',
                    $statuses[$r->approval_status] ?? $r->approval_status,
                    $r->requested_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        return view('stock-mismatch-approval::index', [
            'rows' => $rows,
            'statuses' => StockMismatchApproval::approvalStatuses(),
            'kpis' => $this->kpis(),
        ]);
    }
}
