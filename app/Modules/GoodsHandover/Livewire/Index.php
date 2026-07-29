<?php

namespace App\Modules\GoodsHandover\Livewire;

use App\Modules\GoodsHandover\Models\GoodsHandover;
use App\Modules\GoodsHandover\Models\GoodsHandoverItem;
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
#[Title('Goods Handover / Parts Return')]
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

    protected array $sortable = ['id', 'handover_no', 'status', 'created_at'];

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
        $this->authorize('goods_handover.delete');
        GoodsHandover::findOrFail($id)->delete();
        Flux::toast(text: 'Handover #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter']);
        $this->resetPage();
    }

    /**
     * @return array{issued_today:int, returns:int, technicians:int}
     */
    protected function kpis(): array
    {
        return [
            'issued_today' => GoodsHandover::query()->whereDate('created_at', Carbon::today())->count(),
            'returns' => GoodsHandover::query()->whereNotNull('material_return_status')->count(),
            'technicians' => (int) GoodsHandover::query()->whereNotNull('received_by_id')->distinct('received_by_id')->count('received_by_id'),
        ];
    }

    /**
     * @return Builder<GoodsHandover>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return GoodsHandover::query()
            ->with(['receivedBy:id,name', 'jobCard:id,job_card_no', 'goodsReceipt:id,grn_no'])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('handover_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('jobCard', fn ($jc) => $jc->whereLike('job_card_no', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter));
    }

    /** Stream the Purchase Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('goods_handover.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'goods-handover-purchase-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $statuses = GoodsHandover::statuses();
        $returns = GoodsHandover::materialReturnStatuses();

        return response()->streamDownload(function () use ($rows, $statuses, $returns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Handover No', 'Technician', 'Job Card', 'GRN', 'Lines', 'Return Status', 'Status', 'Issued On']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->handover_no,
                    $r->receivedBy?->name,
                    $r->jobCard?->job_card_no,
                    $r->goodsReceipt?->grn_no,
                    $r->items_count,
                    $returns[$r->material_return_status] ?? '',
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

        // Technician-wise consumption — issued qty per receiving technician.
        $consumption = GoodsHandoverItem::query()
            ->join('goods_handovers', 'goods_handovers.id', '=', 'goods_handover_items.goods_handover_id')
            ->join('employees', 'employees.id', '=', 'goods_handovers.received_by_id')
            ->selectRaw('employees.name as technician, sum(goods_handover_items.quantity) as issued, sum(coalesce(goods_handover_items.returned_quantity,0)) as returned')
            ->groupBy('employees.name')
            ->orderByDesc('issued')
            ->limit(8)
            ->get();

        return view('goods-handover::index', [
            'rows' => $rows,
            'statuses' => GoodsHandover::statuses(),
            'kpis' => $this->kpis(),
            'consumption' => $consumption,
        ]);
    }
}
