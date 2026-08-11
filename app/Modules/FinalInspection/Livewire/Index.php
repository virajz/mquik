<?php

namespace App\Modules\FinalInspection\Livewire;

use App\Modules\FinalInspection\Models\FinalInspection;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Final Inspections')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'inspection_no', 'status', 'created_at'];

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

    public function delete(int $id): void
    {
        $this->authorize('final_inspection.delete');

        FinalInspection::findOrFail($id)->delete();

        Flux::toast(text: 'Final inspection #'.$id.' deleted.', variant: 'success');
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = FinalInspection::query()
            ->with(['jobCard:id,job_card_no', 'inspector:id,name', 'template:id,name'])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('final-inspection::index', [
            'rows' => $rows,
            'statuses' => FinalInspection::statuses(),
        ]);
    }
}
