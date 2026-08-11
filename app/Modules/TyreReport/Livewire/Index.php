<?php

namespace App\Modules\TyreReport\Livewire;

use App\Modules\TyreReport\Models\TyreReport;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Tyre Reports')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'reported_on';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    /** Whitelist sortable columns to keep the URL from injecting SQL. */
    protected array $sortable = ['id', 'report_no', 'reported_on', 'created_at'];

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

    public function delete(int $id): void
    {
        $this->authorize('tyre_report.delete');

        TyreReport::findOrFail($id)->delete();

        Flux::toast(text: 'Tyre Report #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = TyreReport::query()
            ->with([
                'customer:id,first_name,last_name,phone',
                'customerVehicle:id,registration_no',
                'inspectedBy:id,name',
            ])
            ->withCount(['lines as replace_count' => fn ($q) => $q->where('condition', TyreReport::CONDITION_REPLACE)])
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('reported_on', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('reported_on', '<=', $this->dateTo))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('tyre-report::index', ['rows' => $rows]);
    }
}
