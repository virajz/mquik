<?php

namespace App\Modules\JobDescriptionMaster\Livewire;

use App\Modules\JobDescriptionMaster\Models\JobDescriptionMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use Flux\Flux;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Job Descriptions')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'cat')]
    public string $categoryFilter = 'all';

    #[Url(as: 'st')]
    public string $serviceTypeFilter = 'all';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'name';

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    protected array $sortable = ['id', 'name', 'code', 'category', 'service_type_id', 'standard_hours', 'is_active', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingServiceTypeFilter(): void
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
        $this->dispatch('job-description-master:edit', id: null);
        Flux::modal('job-description-master-form')->show();
    }

    public function openEdit(int $id): void
    {
        $this->dispatch('job-description-master:edit', id: $id);
        Flux::modal('job-description-master-form')->show();
    }

    #[On('job-description-master:saved')]
    public function refreshAfterSave(): void {}

    public function delete(int $id): void
    {
        try {
            JobDescriptionMaster::findOrFail($id)->delete();
            Flux::toast(text: 'Job description #'.$id.' deleted.', variant: 'success');
        } catch (QueryException) {
            Flux::toast(
                text: 'Cannot delete this job description — it is still referenced by job cards or estimates.',
                variant: 'danger',
            );
        }
    }

    public function render()
    {
        $rows = JobDescriptionMaster::query()
            ->with('serviceType:id,name')
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($query) use ($term) {
                    $query->whereLike('name', $term, caseSensitive: false)
                        ->orWhereLike('code', $term, caseSensitive: false);
                });
            })
            ->when($this->categoryFilter !== 'all', fn ($q) => $q->where('category', $this->categoryFilter))
            ->when($this->serviceTypeFilter !== 'all', fn ($q) => $q->where('service_type_id', $this->serviceTypeFilter))
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('job-description-master::index', [
            'rows' => $rows,
            'serviceTypes' => ServiceTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'categories' => JobDescriptionMaster::categories(),
        ]);
    }
}
