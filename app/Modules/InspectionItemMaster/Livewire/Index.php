<?php

namespace App\Modules\InspectionItemMaster\Livewire;

use App\Modules\InspectionItemGroupMaster\Models\InspectionItemGroupMaster;
use App\Modules\InspectionItemMaster\Models\InspectionItemMaster;
use Flux\Flux;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Inspection Items')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'grp')]
    public string $groupFilter = 'all';

    #[Url(as: 'ct')]
    public string $checkTypeFilter = 'all';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'name';

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    protected array $sortable = ['id', 'name', 'code', 'inspection_item_group_id', 'check_type', 'is_active', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingGroupFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCheckTypeFilter(): void
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

    public function openCreate(): void
    {
        $this->dispatch('inspection-item-master:edit', id: null);
        Flux::modal('inspection-item-master-form')->show();
    }

    public function openEdit(int $id): void
    {
        $this->dispatch('inspection-item-master:edit', id: $id);
        Flux::modal('inspection-item-master-form')->show();
    }

    #[On('inspection-item-master:saved')]
    public function refreshAfterSave(): void {}

    public function delete(int $id): void
    {
        try {
            InspectionItemMaster::findOrFail($id)->delete();
            Flux::toast(text: 'Inspection item #'.$id.' deleted.', variant: 'success');
        } catch (QueryException) {
            Flux::toast(
                text: 'Cannot delete this item — it is still used by one or more inspection templates.',
                variant: 'danger',
            );
        }
    }

    public function render()
    {
        $rows = InspectionItemMaster::query()
            ->with('group:id,name')
            ->when($this->search !== '', fn ($q) => $q->search($this->search))
            ->when($this->groupFilter !== 'all', fn ($q) => $q->where('inspection_item_group_id', $this->groupFilter))
            ->when($this->checkTypeFilter !== 'all', fn ($q) => $q->where('check_type', $this->checkTypeFilter))
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('inspection-item-master::index', [
            'rows' => $rows,
            'groups' => InspectionItemGroupMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'checkTypes' => InspectionItemMaster::checkTypes(),
        ]);
    }
}
