<?php

namespace App\Modules\RecommendationCategoryMaster\Livewire;

use App\Modules\RecommendationCategoryMaster\Models\RecommendationCategoryMaster;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Recommendation Categories')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'sequence_no';

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    /** Whitelist sortable columns to prevent SQL injection via the URL */
    protected array $sortable = ['id', 'name', 'code', 'is_active', 'sequence_no', 'created_at', 'updated_at'];

    public function updatingSearch(): void
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
        $this->dispatch('recommendation-category-master:edit', id: null);
        Flux::modal('recommendation-category-master-form')->show();
    }

    public function openEdit(int $id): void
    {
        $this->dispatch('recommendation-category-master:edit', id: $id);
        Flux::modal('recommendation-category-master-form')->show();
    }

    #[On('recommendation-category-master:saved')]
    public function refreshAfterSave(): void
    {
        // Triggers re-render; pagination cursor preserved.
    }

    public function delete(int $id): void
    {
        $this->authorize('recommendation_category_master.delete');

        RecommendationCategoryMaster::findOrFail($id)->delete();
        Flux::toast(text: 'Recommendation category #'.$id.' deleted.', variant: 'success');
    }

    public function render()
    {
        $search = $this->search;

        $rows = RecommendationCategoryMaster::query()
            ->with(['parent:id,name'])
            ->when($search !== '', fn ($q) => $q->search($search))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('recommendation-category-master::index', ['rows' => $rows]);
    }
}
