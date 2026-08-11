<?php

namespace App\Modules\ExcessStockApproval\Livewire;

use App\Modules\ExcessStockApproval\Models\ExcessStockApproval;
use App\Modules\ExcessStockApproval\Models\ExcessStockApprovalItem;
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
#[Title('Excess Stock Approval')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'reason')]
    public string $reasonFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'request_no', 'status', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingReasonFilter(): void
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
        $this->authorize('excess_stock_approval.delete');
        ExcessStockApproval::findOrFail($id)->delete();
        Flux::toast(text: 'Request #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'reasonFilter']);
        $this->resetPage();
    }

    /**
     * @return array{pending:int, approved:int, rejected:int, excess_value:float, dead_value:float}
     */
    protected function kpis(): array
    {
        $counts = ExcessStockApproval::query()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $get = fn (string $s) => (int) ($counts[$s] ?? 0);

        $excessValue = (float) ExcessStockApprovalItem::query()
            ->selectRaw('coalesce(sum(quantity * coalesce(rate,0)),0) as total')
            ->value('total');

        $deadValue = (float) ExcessStockApprovalItem::query()
            ->join('excess_stock_approvals', 'excess_stock_approvals.id', '=', 'excess_stock_approval_items.excess_stock_approval_id')
            ->whereIn('excess_stock_approvals.excess_stock_reason', ExcessStockApproval::deadStockReasons())
            ->selectRaw('coalesce(sum(excess_stock_approval_items.quantity * coalesce(excess_stock_approval_items.rate,0)),0) as total')
            ->value('total');

        return [
            'pending' => $get(ExcessStockApproval::STATUS_REQUESTED) + $get(ExcessStockApproval::STATUS_UNDER_REVIEW)
                + $get(ExcessStockApproval::STATUS_ON_HOLD) + $get(ExcessStockApproval::STATUS_VERBAL_CLARIFICATION),
            'approved' => $get(ExcessStockApproval::STATUS_APPROVED),
            'rejected' => $get(ExcessStockApproval::STATUS_REJECTED),
            'excess_value' => $excessValue,
            'dead_value' => $deadValue,
        ];
    }

    /**
     * @return Builder<ExcessStockApproval>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return ExcessStockApproval::query()
            ->with(['goodsReceipt:id,grn_no', 'department:id,name'])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->reasonFilter !== 'all', fn ($q) => $q->where('excess_stock_reason', $this->reasonFilter));
    }

    /** Stream the Excess Stock Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('excess_stock_approval.view');

        $rows = $this->baseQuery()->with('items')->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'excess-stock-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $reasons = ExcessStockApproval::excessStockReasons();
        $statuses = ExcessStockApproval::statuses();

        return response()->streamDownload(function () use ($rows, $reasons, $statuses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Request No', 'Reason', 'GRN', 'Department', 'Lines', 'Excess Value', 'Status', 'Requested On']);

            foreach ($rows as $r) {
                $value = $r->items->sum(fn ($i) => (float) $i->quantity * (float) ($i->rate ?? 0));
                fputcsv($out, [
                    $r->request_no,
                    $reasons[$r->excess_stock_reason] ?? '',
                    $r->goodsReceipt?->grn_no,
                    $r->department?->name,
                    $r->items_count,
                    number_format($value, 2, '.', ''),
                    $statuses[$r->status] ?? $r->status,
                    $r->requested_at?->format('Y-m-d'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        return view('excess-stock-approval::index', [
            'rows' => $rows,
            'statuses' => ExcessStockApproval::statuses(),
            'reasons' => ExcessStockApproval::excessStockReasons(),
            'kpis' => $this->kpis(),
        ]);
    }
}
