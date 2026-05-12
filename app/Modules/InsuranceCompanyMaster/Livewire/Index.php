<?php

namespace App\Modules\InsuranceCompanyMaster\Livewire;

use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Insurance Companies')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'id';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    /** Whitelist sortable columns — never trust the URL */
    protected array $sortable = ['id', 'name', 'gstin', 'default_pass_percent', 'is_active', 'created_at'];

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
        $this->dispatch('insurance-company-master:edit', id: null);
        Flux::modal('insurance-company-master-form')->show();
    }

    public function openEdit(int $id): void
    {
        $this->dispatch('insurance-company-master:edit', id: $id);
        Flux::modal('insurance-company-master-form')->show();
    }

    #[On('insurance-company-master:saved')]
    public function refreshAfterSave(): void
    {
        // Triggers re-render; pagination cursor preserved.
    }

    public function delete(int $id): void
    {
        InsuranceCompanyMaster::findOrFail($id)->delete();
        Flux::toast(text: 'Insurance company #'.$id.' deleted.', variant: 'success');
    }

    public function render()
    {
        $search = $this->search;

        $rows = InsuranceCompanyMaster::query()
            ->when($search !== '', fn ($q) => $q->search($search))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('insurance-company-master::index', ['rows' => $rows]);
    }
}
