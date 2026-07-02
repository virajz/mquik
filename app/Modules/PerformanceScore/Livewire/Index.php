<?php

namespace App\Modules\PerformanceScore\Livewire;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\PerformanceScore\Models\PerformanceScore;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Performance Scores')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'emp')]
    public string $employeeFilter = 'all';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'achievement_percent', 'incentive_amount', 'status', 'created_at'];

    public function updatingEmployeeFilter(): void
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
        $this->authorize('performance_score.delete');

        PerformanceScore::findOrFail($id)->delete();

        Flux::toast(text: 'Performance score #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['employeeFilter', 'statusFilter']);
        $this->resetPage();
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function render()
    {
        $rows = PerformanceScore::query()
            ->with(['employee:id,name,employee_code', 'performanceSlab:id,name'])
            ->withCount('lines')
            ->when($this->employeeFilter !== 'all', fn ($q) => $q->where('employee_id', (int) $this->employeeFilter))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('performance-score::index', [
            'rows' => $rows,
            'statuses' => PerformanceScore::statuses(),
            'months' => PerformanceScore::months(),
        ]);
    }
}
