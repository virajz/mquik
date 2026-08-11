<?php

namespace App\Modules\ServiceDueFollowUp\Livewire;

use App\Modules\ServiceDueFollowUp\Models\ServiceDueFollowUp;
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
#[Title('Service Due Follow-Ups')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'retention')]
    public string $retentionFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'due_date';

    #[Url(as: 'dir')]
    public string $sortDirection = 'asc';

    protected array $sortable = ['id', 'follow_up_no', 'status', 'due_date', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingRetentionFilter(): void
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
        $this->authorize('service_due_follow_up.delete');
        ServiceDueFollowUp::findOrFail($id)->delete();
        Flux::toast(text: 'Follow-up #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'retentionFilter']);
        $this->resetPage();
    }

    /**
     * @return array{due_today:int, upcoming:int, overdue:int, appointments:int, lost:int, recovered:int}
     */
    protected function kpis(): array
    {
        $today = Carbon::today();
        $open = ServiceDueFollowUp::openStatuses();

        return [
            'due_today' => ServiceDueFollowUp::query()->whereIn('status', $open)->whereDate('due_date', $today)->count(),
            'upcoming' => ServiceDueFollowUp::query()->whereIn('status', $open)->whereDate('due_date', '>', $today)->count(),
            'overdue' => ServiceDueFollowUp::query()->whereIn('status', $open)->whereDate('due_date', '<', $today)->count(),
            'appointments' => ServiceDueFollowUp::query()->where('status', ServiceDueFollowUp::STATUS_APPOINTMENT_BOOKED)->count(),
            'lost' => ServiceDueFollowUp::query()->where('customer_retention', 'lost')->count(),
            'recovered' => ServiceDueFollowUp::query()->where('customer_retention', 'recovered')->count(),
        ];
    }

    /**
     * @return Builder<ServiceDueFollowUp>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return ServiceDueFollowUp::query()
            ->with(['customer:id,first_name,last_name', 'customerVehicle:id,registration_no', 'followUpBy:id,name'])
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->retentionFilter !== 'all', fn ($q) => $q->where('customer_retention', $this->retentionFilter));
    }

    /** Stream the Service Due Follow-Up Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('service_due_follow_up.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'service-due-follow-up-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $statuses = ServiceDueFollowUp::statuses();
        $responses = ServiceDueFollowUp::customerResponses();
        $retentions = ServiceDueFollowUp::retentions();

        return response()->streamDownload(function () use ($rows, $statuses, $responses, $retentions) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Follow-up No', 'Customer', 'Vehicle', 'Due Date', 'Attempt', 'Response', 'Status', 'Retention', 'Follow-up By']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->follow_up_no,
                    trim(($r->customer?->first_name ?? '').' '.($r->customer?->last_name ?? '')),
                    $r->customerVehicle?->registration_no,
                    $r->due_date?->format('Y-m-d'),
                    $r->follow_up_attempt,
                    $responses[$r->customer_response] ?? '',
                    $statuses[$r->status] ?? $r->status,
                    $retentions[$r->customer_retention] ?? '',
                    $r->followUpBy?->name,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        return view('service-due-follow-up::index', [
            'rows' => $rows,
            'statuses' => ServiceDueFollowUp::statuses(),
            'retentions' => ServiceDueFollowUp::retentions(),
            'kpis' => $this->kpis(),
            'today' => Carbon::today(),
        ]);
    }
}
