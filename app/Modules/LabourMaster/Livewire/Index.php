<?php

namespace App\Modules\LabourMaster\Livewire;

use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\VehicleSegmentMaster\Models\VehicleSegmentMaster;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Labour')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'segment')]
    public string $segmentFilter = 'all';

    #[Url(as: 'osl')]
    public string $oslFilter = 'all';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'id';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    /** Whitelist sortable columns to prevent SQL injection via the URL */
    protected array $sortable = ['id', 'name', 'labour_code', 'rate_before_tax', 'is_active', 'is_osl', 'created_at', 'updated_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSegmentFilter(): void
    {
        $this->resetPage();
    }

    public function updatingOslFilter(): void
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
        $this->dispatch('labour-master:edit', id: null);
        Flux::modal('labour-master-form')->show();
    }

    public function openEdit(int $id): void
    {
        $this->dispatch('labour-master:edit', id: $id);
        Flux::modal('labour-master-form')->show();
    }

    #[On('labour-master:saved')]
    public function refreshAfterSave(): void
    {
        // Triggers re-render; pagination cursor preserved.
    }

    public function delete(int $id): void
    {
        $this->authorize('labour_master.delete');

        LabourMaster::findOrFail($id)->delete();

        Flux::toast(text: 'Labour #'.$id.' deleted.', variant: 'success');
    }

    #[Computed]
    public function segments()
    {
        return VehicleSegmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = LabourMaster::query()
            ->with(['vehicleSegment:id,name', 'workshopDepartment:id,name'])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('name', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('labour_code', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('hsn_sac_code', '%'.$search.'%', caseSensitive: false);
            }))
            ->when($this->segmentFilter !== 'all', fn ($q) => $q->where('vehicle_segment_id', (int) $this->segmentFilter))
            ->when($this->oslFilter === 'osl', fn ($q) => $q->where('is_osl', true))
            ->when($this->oslFilter === 'in-house', fn ($q) => $q->where('is_osl', false))
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('labour-master::index', ['rows' => $rows]);
    }
}
