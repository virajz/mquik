<?php

namespace App\Modules\SmartSalary\Livewire;

use App\Modules\SmartSalary\Models\SmartSalary;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Smart Salary — KPIs')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'cat')]
    public string $categoryFilter = 'all';

    #[Url(as: 'active')]
    public string $activeFilter = 'all';   // all | yes | no

    #[Url(as: 'sort')]
    public string $sortBy = 'sort_order';

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    protected array $sortable = ['id', 'key', 'name', 'category', 'weight', 'sort_order', 'is_active', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingActiveFilter(): void
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
        $this->authorize('smart_salary.create');
        $this->dispatch('smart-salary:edit', id: null);
        Flux::modal('smart-salary-form')->show();
    }

    public function openEdit(int $id): void
    {
        $this->authorize('smart_salary.update');
        $this->dispatch('smart-salary:edit', id: $id);
        Flux::modal('smart-salary-form')->show();
    }

    #[On('smart-salary:saved')]
    public function refreshAfterSave(): void
    {
        // re-render
    }

    public function delete(int $id): void
    {
        $this->authorize('smart_salary.delete');

        SmartSalary::findOrFail($id)->delete();
        Flux::toast(text: 'KPI #'.$id.' removed.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'categoryFilter', 'activeFilter']);
        $this->resetPage();
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = SmartSalary::query()
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('name', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('key', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('description', '%'.$search.'%', caseSensitive: false);
            }))
            ->when($this->categoryFilter !== 'all', fn ($q) => $q->where('category', $this->categoryFilter))
            ->when($this->activeFilter !== 'all', fn ($q) => $q->where('is_active', $this->activeFilter === 'yes'))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('smart-salary::index', [
            'rows' => $rows,
            'categories' => SmartSalary::categories(),
        ]);
    }
}
