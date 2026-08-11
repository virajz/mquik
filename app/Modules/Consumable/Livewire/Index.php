<?php

namespace App\Modules\Consumable\Livewire;

use App\Modules\Consumable\Models\Consumable;
use App\Modules\ConsumableCategoryMaster\Models\ConsumableCategoryMaster;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Consumables')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'cat')]
    public string $categoryFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'consumable_no', 'approval_status', 'total_value', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
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
        $this->authorize('consumable.delete');

        Consumable::findOrFail($id)->delete();

        Flux::toast(text: 'Consumable #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'categoryFilter']);
        $this->resetPage();
    }

    #[Computed]
    public function categories()
    {
        return ConsumableCategoryMaster::query()
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = Consumable::query()
            ->with(['category:id,name', 'jobCard:id,job_card_no', 'lossReason:id,name'])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('approval_status', $this->statusFilter))
            ->when($this->categoryFilter !== 'all', fn ($q) => $q->where('consumable_category_id', (int) $this->categoryFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('consumable::index', [
            'rows' => $rows,
            'statuses' => Consumable::approvalStatuses(),
        ]);
    }
}
