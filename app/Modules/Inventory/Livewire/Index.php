<?php

namespace App\Modules\Inventory\Livewire;

use App\Modules\Inventory\Services\StockLedger;
use App\Modules\SpareMaster\Models\SpareMaster;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Inventory')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'qty')]
    public string $qtyFilter = 'all';  // all | positive | zero | negative | below_min | above_max

    #[Url(as: 'group')]
    public string $groupFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'name';

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    protected array $sortable = ['name', 'spare_code', 'location', 'min_qty', 'max_qty'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingQtyFilter(): void
    {
        $this->resetPage();
    }

    public function updatingGroupFilter(): void
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

    public function clearFilters(): void
    {
        $this->reset(['search', 'qtyFilter', 'groupFilter']);
        $this->resetPage();
    }

    public function render()
    {
        $search = trim($this->search);

        $query = SpareMaster::query()
            ->with(['brand', 'inventoryGroup', 'uom'])
            ->where('is_active', true)
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('name', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('spare_code', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('location', '%'.$search.'%', caseSensitive: false);
            }))
            ->when($this->groupFilter !== 'all', fn ($q) => $q->where('inventory_group_id', $this->groupFilter))
            ->orderBy($this->sortBy, $this->sortDirection);

        $spares = $query->paginate(25);

        // Bulk-load current qty for the page (avoids N+1)
        $qtyMap = StockLedger::currentQtyMap($spares->pluck('id')->all());

        // Annotate each spare with live qty + alert status, then apply qty filter
        $rows = $spares->through(function (SpareMaster $spare) use ($qtyMap) {
            $spare->current_qty = $qtyMap[$spare->id] ?? 0.0;
            $spare->alert = StockLedger::alertStatus(
                $spare->current_qty,
                (float) $spare->min_qty,
                (float) $spare->max_qty,
            );

            return $spare;
        });

        // Client-side qty filter (applied after qty annotation)
        // For server-side filtering on qty we'd need a sub-query — paginate first,
        // then filter and re-paginate is complex; acceptable for inventory search UX.
        $filteredRows = match ($this->qtyFilter) {
            'positive' => $rows->filter(fn ($s) => $s->current_qty > 0),
            'zero' => $rows->filter(fn ($s) => $s->current_qty == 0),
            'negative' => $rows->filter(fn ($s) => $s->current_qty < 0),
            'below_min' => $rows->filter(fn ($s) => $s->alert === 'below_min'),
            'above_max' => $rows->filter(fn ($s) => $s->alert === 'above_max'),
            default => $rows,
        };

        return view('inventory::index', [
            'rows' => $spares,
            'filteredRows' => $filteredRows,
            'qtyMap' => $qtyMap,
        ]);
    }
}
