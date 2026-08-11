<?php

namespace App\Modules\TechnicianFinding\Livewire;

use App\Modules\TechnicianFinding\Models\TechnicianFinding;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Technician Findings')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'type')]
    public string $typeFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'finding_no', 'status', 'finding_type', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
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
        $this->authorize('technician_finding.delete');

        TechnicianFinding::findOrFail($id)->delete();

        Flux::toast(text: 'Finding #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'typeFilter']);
        $this->resetPage();
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = TechnicianFinding::query()
            ->with([
                'jobCard:id,job_card_no',
                'order:id,order_no',
                'spare:id,name',
                'labour:id,name',
                'reportedBy:id,name',
            ])
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter !== 'all', fn ($q) => $q->where('finding_type', $this->typeFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('technician-finding::index', [
            'rows' => $rows,
            'statuses' => TechnicianFinding::statuses(),
            'types' => TechnicianFinding::types(),
        ]);
    }
}
