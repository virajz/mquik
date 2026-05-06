<?php

namespace App\Modules\InspectionTemplateMaster\Livewire;

use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use Flux\Flux;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Inspection Templates')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'at')]
    public string $appliesToFilter = 'all';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'name';

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    protected array $sortable = ['id', 'name', 'code', 'applies_to', 'is_active', 'created_at'];

    public function updatingSearch(): void
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

    public function openCreate(): void
    {
        $this->dispatch('inspection-template-master:edit', id: null);
        Flux::modal('inspection-template-master-form')->show();
    }

    public function openEdit(int $id): void
    {
        $this->dispatch('inspection-template-master:edit', id: $id);
        Flux::modal('inspection-template-master-form')->show();
    }

    #[On('inspection-template-master:saved')]
    public function refreshAfterSave(): void {}

    public function delete(int $id): void
    {
        try {
            InspectionTemplateMaster::findOrFail($id)->delete();
            Flux::toast(text: 'Inspection template #'.$id.' deleted.', variant: 'success');
        } catch (QueryException) {
            Flux::toast(
                text: 'Cannot delete this template — it is still referenced by inspections.',
                variant: 'danger',
            );
        }
    }

    public function render()
    {
        $rows = InspectionTemplateMaster::query()
            ->withCount('items')
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($query) use ($term) {
                    $query->whereLike('name', $term, caseSensitive: false)
                        ->orWhereLike('code', $term, caseSensitive: false);
                });
            })
            ->when($this->appliesToFilter !== 'all', fn ($q) => $q->where('applies_to', $this->appliesToFilter))
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('inspection-template-master::index', [
            'rows' => $rows,
            'appliesToOptions' => InspectionTemplateMaster::appliesToOptions(),
        ]);
    }
}
