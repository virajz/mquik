<?php

namespace App\Modules\AuditLogMaster\Livewire;

use App\Models\AuditLog;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Audit Log')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'user')]
    public string $userFilter = 'all';

    #[Url(as: 'event')]
    public string $eventFilter = 'all';

    #[Url(as: 'type')]
    public string $modelTypeFilter = 'all';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    /** Whitelist sortable columns — never trust the URL */
    protected array $sortable = ['id', 'created_at', 'event', 'model_type'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingUserFilter(): void
    {
        $this->resetPage();
    }

    public function updatingEventFilter(): void
    {
        $this->resetPage();
    }

    public function updatingModelTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
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
            $this->sortDirection = $column === 'created_at' ? 'desc' : 'asc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'userFilter', 'eventFilter', 'modelTypeFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = AuditLog::query()
            ->with('user:id,name,email')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $term = '%'.$search.'%';
                    $query->whereLike('user_name', $term, caseSensitive: false)
                        ->orWhereLike('model_label', $term, caseSensitive: false);
                });
            })
            ->when($this->userFilter !== 'all' && $this->userFilter !== '', fn ($q) => $q->where('user_id', $this->userFilter))
            ->when($this->eventFilter !== 'all' && $this->eventFilter !== '', fn ($q) => $q->where('event', $this->eventFilter))
            ->when($this->modelTypeFilter !== 'all' && $this->modelTypeFilter !== '', fn ($q) => $q->where('model_type', $this->modelTypeFilter))
            ->when($this->dateFrom !== '', fn ($q) => $q->where('created_at', '>=', $this->dateFrom.' 00:00:00'))
            ->when($this->dateTo !== '', fn ($q) => $q->where('created_at', '<=', $this->dateTo.' 23:59:59'))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(25);

        $modelTypes = AuditLog::query()
            ->distinct()
            ->orderBy('model_type')
            ->pluck('model_type')
            ->all();

        $userOptions = User::query()
            ->whereIn('id', AuditLog::query()->distinct()->pluck('user_id')->filter())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('audit-log-master::index', [
            'rows' => $rows,
            'modelTypes' => $modelTypes,
            'userOptions' => $userOptions,
        ]);
    }
}
