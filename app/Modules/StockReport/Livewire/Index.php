<?php

namespace App\Modules\StockReport\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\Inventory\Services\StockLedger;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use App\Modules\PartTypeMaster\Models\PartTypeMaster;
use App\Modules\RackMaster\Models\RackMaster;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Stock Report')]
class Index extends Component
{
    use SearchesPickerOptions;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'group')]
    public string $groupFilter = 'all';

    #[Url(as: 'brand')]
    public string $brandFilter = 'all';

    #[Url(as: 'rack')]
    public string $rackFilter = 'all';

    #[Url(as: 'type')]
    public string $partTypeFilter = 'all';

    /** Only spares at or below their reorder (min) level. */
    #[Url(as: 'low')]
    public bool $belowReorderOnly = false;

    public string $brandSearch = '';

    public string $groupSearch = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'groupFilter', 'brandFilter', 'rackFilter', 'partTypeFilter', 'belowReorderOnly']);
        $this->resetPage();
    }

    /**
     * The report query, shared by the on-screen table and the CSV download so
     * the export is always exactly what the user is looking at.
     *
     * @return Builder<SpareMaster>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return SpareMaster::query()
            ->with(['brand:id,name', 'inventoryGroup:id,name', 'rack:id,name', 'uom:id,code,name'])
            ->where('is_active', true)
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('name', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('spare_code', '%'.$search.'%', caseSensitive: false);
            }))
            ->when($this->groupFilter !== 'all', fn ($q) => $q->where('inventory_group_id', (int) $this->groupFilter))
            ->when($this->brandFilter !== 'all', fn ($q) => $q->where('spare_brand_id', (int) $this->brandFilter))
            ->when($this->rackFilter !== 'all', fn ($q) => $q->where('rack_id', (int) $this->rackFilter))
            ->when($this->partTypeFilter !== 'all', fn ($q) => $q->where('part_type_id', (int) $this->partTypeFilter))
            ->orderBy('name');
    }

    /**
     * Decorate a spare collection with computed stock figures.
     *
     * @param  Collection<int, SpareMaster>  $spares
     * @return Collection<int, array<string, mixed>>
     */
    protected function decorate(Collection $spares): Collection
    {
        $qtyMap = StockLedger::currentQtyMap($spares->pluck('id')->all());

        return $spares->map(function (SpareMaster $s) use ($qtyMap) {
            $qty = (float) ($qtyMap[$s->id] ?? 0);
            $rate = (float) $s->rate_before_tax;

            return [
                'spare' => $s,
                'qty' => $qty,
                'rate' => $rate,
                'value' => round($qty * $rate, 2),
                'status' => StockLedger::alertStatus($qty, (float) $s->min_qty, (float) $s->max_qty),
            ];
        })->when($this->belowReorderOnly, fn ($rows) => $rows->whereIn('status', ['below_min', 'zero', 'negative'])->values());
    }

    #[Computed]
    public function brands()
    {
        return $this->pickerOptions(
            query: SpareBrandMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'code'],
            term: $this->brandSearch,
            selected: is_numeric($this->brandFilter) ? (int) $this->brandFilter : null,
            columns: ['id', 'name'],
            limit: 30,
        );
    }

    #[Computed]
    public function groups()
    {
        return $this->pickerOptions(
            query: InventoryGroupMaster::query()->with('parent:id,name')->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'code'],
            term: $this->groupSearch,
            selected: is_numeric($this->groupFilter) ? (int) $this->groupFilter : null,
            columns: ['id', 'name', 'parent_id'],
            limit: 30,
        );
    }

    #[Computed]
    public function racks()
    {
        return RackMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function partTypes()
    {
        return PartTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /** Total stock value across the whole filtered set (not just this page). */
    #[Computed]
    public function totalValue(): float
    {
        $all = $this->baseQuery()->get(['id', 'rate_before_tax', 'min_qty', 'max_qty']);

        return round($this->decorate($all)->sum('value'), 2);
    }

    /** Stream the filtered report as CSV — exactly what's on screen. */
    public function download(): StreamedResponse
    {
        $this->authorize('stock_report.export');

        $rows = $this->decorate($this->baseQuery()->get());
        $filename = 'stock-report-'.Carbon::now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Part No', 'Spare', 'Brand', 'Group', 'Godown', 'UoM', 'On Hand', 'Rate', 'Value', 'Min', 'Max', 'Status']);

            foreach ($rows as $r) {
                /** @var SpareMaster $s */
                $s = $r['spare'];
                fputcsv($out, [
                    $s->spare_code,
                    $s->name,
                    $s->brand?->name,
                    $s->inventoryGroup?->name,
                    $s->location,
                    $s->uom?->code ?? $s->uom?->name,
                    $r['qty'],
                    number_format($r['rate'], 2, '.', ''),
                    number_format($r['value'], 2, '.', ''),
                    $s->min_qty,
                    $s->max_qty,
                    strtoupper($r['status']),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        // Stock qty is computed from the ledger, not a column, so the
        // below-reorder filter can't run in the database. When it's on we decorate
        // the whole filtered set, keep the low rows, and paginate in memory;
        // otherwise the cheap DB paginator drives the page.
        if ($this->belowReorderOnly) {
            $all = $this->decorate($this->baseQuery()->get());
            $paginator = new LengthAwarePaginator(
                $all->forPage($this->getPage(), 25)->values(),
                $all->count(),
                25,
                $this->getPage(),
                ['path' => LengthAwarePaginator::resolveCurrentPath()],
            );
            $rows = collect($paginator->items());
        } else {
            $paginator = $this->baseQuery()->paginate(25);
            $rows = $this->decorate(collect($paginator->items()));
        }

        return view('stock-report::index', [
            'rows' => $rows,
            'paginator' => $paginator,
        ]);
    }
}
