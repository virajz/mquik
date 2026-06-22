<?php

namespace App\Modules\EstimateRevisionReasonMaster\Livewire;

use App\Modules\EstimateRevisionReasonMaster\Models\EstimateRevisionReasonMaster;
use App\Support\RecordReferences;
use Flux\Flux;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Estimate Revision Reasons')]
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

    /** Whitelist sortable columns â never trust the URL */
    protected array $sortable = ['id', 'name', 'code', 'is_active', 'created_at'];

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
        $this->dispatch('estimate-revision-reason-master:edit', id: null);
        Flux::modal('estimate-revision-reason-master-form')->show();
    }

    public function openEdit(int $id): void
    {
        $this->dispatch('estimate-revision-reason-master:edit', id: $id);
        Flux::modal('estimate-revision-reason-master-form')->show();
    }

    #[On('estimate-revision-reason-master:saved')]
    public function refreshAfterSave(): void
    {
        // Triggers re-render; pagination cursor preserved.
    }

    public function delete(int $id): void
    {
        $this->authorize('estimate_revision_reason_master.delete');

        try {
            $record = EstimateRevisionReasonMaster::findOrFail($id);
            $record->delete();
            Flux::toast(text: 'Estimate Revision Reason #'.$id.' deleted.', variant: 'success');
        } catch (QueryException) {
            $refs = RecordReferences::summary($record);
            Flux::toast(
                text: $refs
                    ? 'Cannot delete — in use by '.$refs.'. Remove those first.'
                    : 'Cannot delete — it is still in use.',
                variant: 'danger',
            );
        }
    }

    public function render()
    {
        $search = $this->search;
        $status = $this->statusFilter;

        $rows = EstimateRevisionReasonMaster::query()
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('estimate-revision-reason-master::index', ['rows' => $rows]);
    }
}
