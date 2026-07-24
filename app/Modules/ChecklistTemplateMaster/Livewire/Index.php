<?php

namespace App\Modules\ChecklistTemplateMaster\Livewire;

use App\Modules\ChecklistGroupMaster\Models\ChecklistGroupMaster;
use App\Modules\ChecklistTemplateMaster\Models\ChecklistTemplateMaster;
use Flux\Flux;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Checklist Templates')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'grp')]
    public string $groupFilter = 'all';

    #[Url(as: 'at')]
    public string $appliesToFilter = 'all';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'name';

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    protected array $sortable = ['id', 'name', 'code', 'checklist_group_id', 'applies_to', 'is_active', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingGroupFilter(): void
    {
        $this->resetPage();
    }

    public function updatingAppliesToFilter(): void
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
        $this->authorize('checklist_template_master.delete');

        try {
            ChecklistTemplateMaster::findOrFail($id)->delete();
            Flux::toast(text: 'Checklist template #'.$id.' deleted.', variant: 'success');
        } catch (QueryException) {
            Flux::toast(
                text: 'Cannot delete this template — it is still referenced.',
                variant: 'danger',
            );
        }
    }

    public function render()
    {
        $rows = ChecklistTemplateMaster::query()
            ->with('group:id,name')
            ->when($this->search !== '', fn ($q) => $q->search($this->search))
            ->when($this->groupFilter !== 'all', fn ($q) => $q->where('checklist_group_id', $this->groupFilter))
            ->when($this->appliesToFilter !== 'all', fn ($q) => $q->where('applies_to', $this->appliesToFilter))
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('checklist-template-master::index', [
            'rows' => $rows,
            'appliesToOptions' => ChecklistTemplateMaster::appliesToOptions(),
            'groupOptions' => ChecklistGroupMaster::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
