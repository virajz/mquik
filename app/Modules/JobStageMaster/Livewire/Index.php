<?php

namespace App\Modules\JobStageMaster\Livewire;

use App\Modules\JobStageMaster\Models\JobStageMaster;
use Flux\Flux;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Job Stages')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'name';

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    /** Whitelist sortable columns — never trust the URL */
    protected array $sortable = ['id', 'name', 'code', 'track', 'sort_order', 'is_active', 'created_at'];

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

    public function openCreate(): void
    {
        $this->dispatch('job-stage-master:edit', id: null);
        Flux::modal('job-stage-master-form')->show();
    }

    public function openEdit(int $id): void
    {
        $this->dispatch('job-stage-master:edit', id: $id);
        Flux::modal('job-stage-master-form')->show();
    }

    #[On('job-stage-master:saved')]
    public function refreshAfterSave(): void
    {
        // Triggers re-render; pagination cursor preserved.
    }

    public function delete(int $id): void
    {
        $this->authorize('job_stage_master.delete');

        try {
            JobStageMaster::findOrFail($id)->delete();
            Flux::toast(text: 'Job stage #'.$id.' deleted.', variant: 'success');
        } catch (QueryException) {
            // FK restrict — stage is in use by job cards
            Flux::toast(
                text: 'Cannot delete this type — it is still in use by one or more records.',
                variant: 'danger',
            );
        }
    }

    public function render()
    {
        $search = $this->search;
        $status = $this->statusFilter;

        $rows = JobStageMaster::query()
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('job-stage-master::index', ['rows' => $rows]);
    }
}
