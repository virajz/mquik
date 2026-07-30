<?php

namespace App\Modules\StockCounting\Livewire;

use App\Modules\StockCounting\Models\StockCount;
use App\Modules\StockCounting\Models\StockCountItem;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Stock Counting')]
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

    protected array $sortable = ['id', 'count_no', 'verification_status', 'count_start_date', 'created_at'];

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
        $this->authorize('stock_counting.delete');
        StockCount::findOrFail($id)->delete();
        Flux::toast(text: 'Stock count #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter']);
        $this->resetPage();
    }

    /**
     * @return array{in_progress:int, completed_today:int, total_mismatch_qty:float, pending:int}
     */
    protected function kpis(): array
    {
        $today = Carbon::today();

        return [
            'in_progress' => StockCount::query()->where('verification_status', StockCount::STATUS_IN_PROGRESS)->count(),
            'completed_today' => StockCount::query()
                ->where('verification_status', StockCount::STATUS_COMPLETED)
                ->whereDate('count_end_date', $today)->count(),
            'total_mismatch_qty' => (float) StockCountItem::query()
                ->whereHas('stockCount', fn (Builder $q) => $q->where('verification_status', '!=', StockCount::STATUS_CANCELLED))
                ->sum(DB::raw('ABS(diff_qty)')),
            'pending' => StockCount::query()->where('verification_status', StockCount::STATUS_PENDING)->count(),
        ];
    }

    /**
     * @return Builder<StockCount>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return StockCount::query()
            ->with(['storageLocation:id,name', 'inventoryGroup:id,name', 'teamLeader:id,name'])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->whereLike('count_no', '%'.$search.'%', caseSensitive: false))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('verification_status', $this->statusFilter));
    }

    /** Stream the Stock counting report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('stock_counting.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'stock-counting-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $methods = StockCount::countingMethods();
        $statuses = StockCount::verificationStatuses();

        return response()->streamDownload(function () use ($rows, $methods, $statuses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Count No', 'Start', 'End', 'Method', 'Location', 'Inventory Group', 'Team Leader', 'Items', 'Status']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->count_no,
                    $r->count_start_date?->format('Y-m-d'),
                    $r->count_end_date?->format('Y-m-d'),
                    $methods[$r->counting_method] ?? '',
                    $r->storageLocation?->name,
                    $r->inventoryGroup?->name,
                    $r->teamLeader?->name,
                    $r->items_count,
                    $statuses[$r->verification_status] ?? $r->verification_status,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        return view('stock-counting::index', [
            'rows' => $rows,
            'statuses' => StockCount::verificationStatuses(),
            'methods' => StockCount::countingMethods(),
            'kpis' => $this->kpis(),
        ]);
    }
}
