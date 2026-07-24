<?php

namespace App\Modules\ServicePackageMaster\Livewire;

use App\Modules\ServicePackageMaster\Models\ServicePackageMaster;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Service Packages')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'kind')]
    public string $kindFilter = 'all';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'id';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    /** Whitelist sortable columns to prevent SQL injection via the URL */
    protected array $sortable = ['id', 'name', 'code', 'total_price', 'is_active', 'is_amc', 'created_at', 'updated_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingKindFilter(): void
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
        $this->authorize('service_package_master.delete');

        ServicePackageMaster::findOrFail($id)->delete();

        Flux::toast(text: 'Package #'.$id.' deleted.', variant: 'success');
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = ServicePackageMaster::query()
            ->withCount('services')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('name', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('code', '%'.$search.'%', caseSensitive: false);
            }))
            ->when($this->kindFilter === 'amc', fn ($q) => $q->where('is_amc', true))
            ->when($this->kindFilter === 'combo', fn ($q) => $q->where('is_amc', false))
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('service-package-master::index', ['rows' => $rows]);
    }
}
