<?php

namespace App\Modules\InternalPartOrder\Livewire;

use App\Modules\InternalPartOrder\Models\InternalPartOrder;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Internal Part Orders')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'priority')]
    public string $priorityFilter = 'all';

    #[Url(as: 'type')]
    public string $typeFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'order_no', 'status', 'priority_id', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPriorityFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
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
        $this->authorize('internal_part_order.delete');

        InternalPartOrder::findOrFail($id)->delete();

        Flux::toast(text: 'IPO #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'priorityFilter', 'typeFilter']);
        $this->resetPage();
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = InternalPartOrder::query()
            ->with([
                'priority:id,name',
                'jobCard:id,job_card_no',
                'customer:id,first_name,last_name',
                'requestedBy:id,name',
            ])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->priorityFilter !== 'all', fn ($q) => $q->where('priority_id', (int) $this->priorityFilter))
            ->when($this->typeFilter !== 'all', fn ($q) => $q->where('ipo_type', $this->typeFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('internal-part-order::index', [
            'rows' => $rows,
            'statuses' => InternalPartOrder::statuses(),
            'priorities' => PriorityMaster::forScope(PriorityMaster::APPLIES_PARTS)->pluck('name', 'id'),
            'types' => InternalPartOrder::ipoTypes(),
        ]);
    }
}
