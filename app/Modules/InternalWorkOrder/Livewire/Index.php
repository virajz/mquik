<?php

namespace App\Modules\InternalWorkOrder\Livewire;

use App\Modules\InternalWorkOrder\Models\InternalWorkOrder;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Internal Work Order')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'priority')]
    public string $priorityFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'iwo_no', 'status', 'priority', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPriorityFilter(): void
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
        $this->authorize('internal_work_order.delete');
        InternalWorkOrder::findOrFail($id)->delete();
        Flux::toast(text: 'IWO #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'priorityFilter']);
        $this->resetPage();
    }

    /**
     * @return array{total:int, open:int, assigned:int, overdue:int, resolved_today:int, avg_tat_hours:int}
     */
    protected function kpis(): array
    {
        $open = InternalWorkOrder::openStatuses();
        $now = Carbon::now();

        $resolved = InternalWorkOrder::query()
            ->where('status', InternalWorkOrder::STATUS_RESOLVED)
            ->whereNotNull('complaint_at')
            ->whereNotNull('resolved_at')
            ->get(['complaint_at', 'resolved_at']);

        $avgTat = $resolved->isEmpty()
            ? 0
            : (int) round($resolved->avg(fn ($r) => abs($r->complaint_at->diffInMinutes($r->resolved_at))) / 60);

        return [
            'total' => InternalWorkOrder::query()->count(),
            'open' => InternalWorkOrder::query()->whereIn('status', $open)->count(),
            'assigned' => InternalWorkOrder::query()->whereIn('status', $open)->whereNotNull('assigned_to_id')->count(),
            'overdue' => InternalWorkOrder::query()->whereIn('status', $open)->whereNotNull('due_at')->where('due_at', '<', $now)->count(),
            'resolved_today' => InternalWorkOrder::query()->whereDate('resolved_at', $now->toDateString())->count(),
            'avg_tat_hours' => $avgTat,
        ];
    }

    /**
     * @return Builder<InternalWorkOrder>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return InternalWorkOrder::query()
            ->with(['requestedBy:id,name', 'assignedTo:id,name'])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('iwo_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('title', '%'.$search.'%', caseSensitive: false);
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->priorityFilter !== 'all', fn ($q) => $q->where('priority', $this->priorityFilter));
    }

    /** Stream the IWO Register Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('internal_work_order.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'iwo-register-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $types = InternalWorkOrder::types();
        $categories = InternalWorkOrder::categories();
        $priorities = InternalWorkOrder::priorities();
        $statuses = InternalWorkOrder::statuses();

        return response()->streamDownload(function () use ($rows, $types, $categories, $priorities, $statuses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['IWO No', 'Type', 'Category', 'Priority', 'Title', 'Requested By', 'Assigned To', 'Status', 'Complaint At', 'Resolved At']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->iwo_no,
                    $types[$r->iwo_type] ?? '',
                    $categories[$r->iwo_category] ?? '',
                    $priorities[$r->priority] ?? '',
                    $r->title,
                    $r->requestedBy?->name,
                    $r->assignedTo?->name,
                    $statuses[$r->status] ?? $r->status,
                    $r->complaint_at?->format('Y-m-d H:i'),
                    $r->resolved_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        return view('internal-work-order::index', [
            'rows' => $rows,
            'statuses' => InternalWorkOrder::statuses(),
            'priorities' => InternalWorkOrder::priorities(),
            'kpis' => $this->kpis(),
        ]);
    }
}
