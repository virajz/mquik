<?php

namespace App\Modules\AmcServiceFollowUp\Livewire;

use App\Modules\AmcServiceFollowUp\Models\AmcServiceFollowUp;
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
#[Title('AMC Service Due / Renewal Follow-Ups')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'type')]
    public string $typeFilter = 'all';

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

    public function updatingTypeFilter(): void
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
        $this->authorize('amc_service_follow_up.delete');
        AmcServiceFollowUp::findOrFail($id)->delete();
        Flux::toast(text: 'Follow-up #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'typeFilter']);
        $this->resetPage();
    }

    /**
     * @return array{due_today:int, upcoming:int, overdue:int, appointments:int, lost:int, recovered:int}
     */
    protected function kpis(): array
    {
        $today = Carbon::today();
        $open = AmcServiceFollowUp::openStatuses();

        return [
            'due_today' => AmcServiceFollowUp::query()->whereIn('status', $open)->whereDate('due_date', $today)->count(),
            'upcoming' => AmcServiceFollowUp::query()->whereIn('status', $open)->whereDate('due_date', '>', $today)->count(),
            'overdue' => AmcServiceFollowUp::query()->whereIn('status', $open)->whereDate('due_date', '<', $today)->count(),
            'appointments' => AmcServiceFollowUp::query()->where('status', AmcServiceFollowUp::STATUS_APPOINTMENT_BOOKED)->count(),
            'lost' => AmcServiceFollowUp::query()->where('customer_retention', 'lost')->count(),
            'recovered' => AmcServiceFollowUp::query()->where('customer_retention', 'recovered')->count(),
        ];
    }

    /**
     * @return Builder<AmcServiceFollowUp>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return AmcServiceFollowUp::query()
            ->with(['customer:id,first_name,last_name', 'customerVehicle:id,registration_no', 'amc:id,amc_no'])
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter !== 'all', fn ($q) => $q->where('follow_up_type', $this->typeFilter));
    }

    /** Stream the AMC Service Due Follow-Up Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('amc_service_follow_up.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'amc-service-due-follow-up-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $types = AmcServiceFollowUp::followUpTypes();
        $statuses = AmcServiceFollowUp::statuses();
        $responses = AmcServiceFollowUp::customerResponses();

        return response()->streamDownload(function () use ($rows, $types, $statuses, $responses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Follow-up No', 'Type', 'AMC', 'Customer', 'Vehicle', 'Due Date', 'Response', 'Status', 'Retention']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->follow_up_no,
                    $types[$r->follow_up_type] ?? '',
                    $r->amc?->amc_no,
                    trim(($r->customer?->first_name ?? '').' '.($r->customer?->last_name ?? '')),
                    $r->customerVehicle?->registration_no,
                    $r->due_date?->format('Y-m-d'),
                    $responses[$r->customer_response] ?? '',
                    $statuses[$r->status] ?? $r->status,
                    ucfirst((string) $r->customer_retention),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        return view('amc-service-follow-up::index', [
            'rows' => $rows,
            'statuses' => AmcServiceFollowUp::statuses(),
            'types' => AmcServiceFollowUp::followUpTypes(),
            'kpis' => $this->kpis(),
            'today' => Carbon::today(),
        ]);
    }
}
