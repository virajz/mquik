<?php

namespace App\Modules\QueueManagement\Livewire;

use App\Modules\QueueManagement\Models\ServiceQueue;
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
#[Title('Queue Management')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'view')]
    public string $viewFilter = 'all';

    #[Url(as: 'type')]
    public string $typeFilter = 'all';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'queue_no', 'status', 'created_at', 'expected_completion_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingViewFilter(): void
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

    public function delete(int $id): void
    {
        $this->authorize('queue_management.delete');

        ServiceQueue::findOrFail($id)->delete();

        Flux::toast(text: 'Queue entry #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'viewFilter', 'typeFilter', 'statusFilter']);
        $this->resetPage();
    }

    /**
     * The six dashboard KPIs.
     *
     * @return array{pending:int, completed:int, total:int, avg_wait:int, avg_wash:int, on_time_pct:int}
     */
    protected function kpis(): array
    {
        $all = ServiceQueue::query()->get([
            'status', 'kept_at', 'work_started_at', 'work_ended_at', 'promised_delivery_at', 'expected_completion_at',
        ]);

        $completed = $all->where('status', ServiceQueue::STATUS_COMPLETED);
        $waits = $all->map->waitingMinutes()->filter(fn ($m) => $m !== null);
        $washes = $all->map->serviceMinutes()->filter(fn ($m) => $m !== null);
        $onTime = $completed->filter(fn ($q) => $q->isOnTime())->count();

        return [
            'pending' => $all->whereNotIn('status', [ServiceQueue::STATUS_COMPLETED, ServiceQueue::STATUS_CANCELLED])->count(),
            'completed' => $completed->count(),
            'total' => $all->count(),
            'avg_wait' => $waits->isEmpty() ? 0 : (int) round($waits->avg()),
            'avg_wash' => $washes->isEmpty() ? 0 : (int) round($washes->avg()),
            'on_time_pct' => $completed->isEmpty() ? 0 : (int) round($onTime / $completed->count() * 100),
        ];
    }

    /**
     * @return Builder<ServiceQueue>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return ServiceQueue::query()
            ->with([
                'customerVehicle:id,registration_no,model_id',
                'customerVehicle.model:id,name',
                'labour:id,name',
                'technician:id,name',
            ])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('queue_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('job_description', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('customerVehicle', fn ($v) => $v->whereLike('registration_no', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->viewFilter !== 'all', fn ($q) => $q->where('screen_view', $this->viewFilter))
            ->when($this->typeFilter !== 'all', fn ($q) => $q->where('queue_type', $this->typeFilter))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter));
    }

    /** Stream the queue as CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('queue_management.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'service-queue-'.Carbon::now()->format('Ymd-His').'.csv';
        $types = ServiceQueue::queueTypes();
        $statuses = ServiceQueue::statuses();

        return response()->streamDownload(function () use ($rows, $types, $statuses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Queue No', 'Reg No', 'Model', 'Type', 'Job Desc', 'Technician', 'Status', 'Wait Mins', 'Service Mins', 'TAT Mins', 'On Time', 'Promised', 'Expected']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->queue_no,
                    $r->customerVehicle?->registration_no,
                    $r->customerVehicle?->model?->name,
                    $types[$r->queue_type] ?? '',
                    $r->job_description,
                    $r->technician?->name,
                    $statuses[$r->status] ?? $r->status,
                    $r->waitingMinutes() ?? '',
                    $r->serviceMinutes() ?? '',
                    $r->tatMinutes() ?? '',
                    $r->status === ServiceQueue::STATUS_COMPLETED ? ($r->isOnTime() ? 'Yes' : 'No') : '',
                    $r->promised_delivery_at?->format('Y-m-d H:i'),
                    $r->expected_completion_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()
            ->orderByRaw('is_high_priority desc')
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(25);

        return view('queue-management::index', [
            'rows' => $rows,
            'statuses' => ServiceQueue::statuses(),
            'queueTypes' => ServiceQueue::queueTypes(),
            'screenViews' => ServiceQueue::screenViews(),
            'kpis' => $this->kpis(),
        ]);
    }
}
