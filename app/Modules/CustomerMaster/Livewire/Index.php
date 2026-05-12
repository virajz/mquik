<?php

namespace App\Modules\CustomerMaster\Livewire;

use App\Modules\BusinessTypeMaster\Models\BusinessTypeMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use Flux\Flux;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Customers')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'type')]
    public string $typeFilter = 'all';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'id';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    /** Whitelist sortable columns — never trust the URL */
    protected array $sortable = ['id', 'name', 'business_type_id', 'phone', 'is_active', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
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
        $this->dispatch('customer-master:edit', id: null);
        Flux::modal('customer-master-form')->show();
    }

    public function openEdit(int $id): void
    {
        $this->dispatch('customer-master:edit', id: $id);
        Flux::modal('customer-master-form')->show();
    }

    #[On('customer-master:saved')]
    public function refreshAfterSave(): void
    {
        // Re-render — pagination cursor preserved.
    }

    public function delete(int $id): void
    {
        try {
            CustomerMaster::findOrFail($id)->delete();
            Flux::toast(text: 'Customer #'.$id.' deleted.', variant: 'success');
        } catch (QueryException) {
            Flux::toast(
                text: 'Cannot delete this customer — they have related records (job cards, invoices, etc.).',
                variant: 'danger',
            );
        }
    }

    public function render()
    {
        $search = $this->search;
        $type = $this->typeFilter;
        $status = $this->statusFilter;

        $rows = CustomerMaster::query()
            ->with('businessType:id,name')
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($type !== 'all', fn ($q) => $q->where('business_type_id', $type))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('customer-master::index', [
            'rows' => $rows,
            'businessTypes' => BusinessTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
