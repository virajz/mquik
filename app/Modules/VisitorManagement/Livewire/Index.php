<?php

namespace App\Modules\VisitorManagement\Livewire;

use App\Modules\VisitorManagement\Models\VisitorVisit;
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
#[Title('Visitor Management (VMS)')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'token_no', 'status', 'arrival_at', 'created_at'];

    public function updatingSearch(): void
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
        $this->authorize('visitor_management.delete');
        VisitorVisit::findOrFail($id)->delete();
        Flux::toast(text: 'Visit #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter']);
        $this->resetPage();
    }

    /**
     * @return array{tokens_today:int, served_today:int, no_shows:int, avg_wait_minutes:int}
     */
    protected function kpis(): array
    {
        $today = Carbon::today();

        $waitRows = VisitorVisit::query()
            ->whereNotNull('arrival_at')
            ->whereNotNull('consultation_started_at')
            ->get(['arrival_at', 'consultation_started_at']);

        $avgWait = $waitRows->isEmpty()
            ? 0
            : (int) round($waitRows->avg(fn ($r) => abs($r->arrival_at->diffInMinutes($r->consultation_started_at))));

        return [
            'tokens_today' => VisitorVisit::query()->whereDate('created_at', $today)->count(),
            'served_today' => VisitorVisit::query()->whereDate('job_card_created_at', $today)->count(),
            'no_shows' => VisitorVisit::query()->where('status', VisitorVisit::STATUS_CANCELLED)->whereNotNull('no_show_reason')->count(),
            'avg_wait_minutes' => $avgWait,
        ];
    }

    /**
     * @return Builder<VisitorVisit>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return VisitorVisit::query()
            ->with(['customer:id,first_name,last_name', 'customerVehicle:id,registration_no', 'assignedTo:id,name'])
            ->when($search !== '', fn ($q) => $q->whereLike('token_no', '%'.$search.'%', caseSensitive: false))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter));
    }

    /** Stream the Visitor Management Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('visitor_management.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'visitor-management-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $purposes = VisitorVisit::visitPurposes();
        $modes = VisitorVisit::arrivalModes();
        $statuses = VisitorVisit::statuses();

        return response()->streamDownload(function () use ($rows, $purposes, $modes, $statuses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Token No', 'Customer', 'Vehicle', 'Purpose', 'Arrival Mode', 'Advisor', 'Arrival At', 'Status']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->token_no,
                    trim(($r->customer?->first_name ?? '').' '.($r->customer?->last_name ?? '')),
                    $r->customerVehicle?->registration_no,
                    $purposes[$r->visit_purpose] ?? '',
                    $modes[$r->arrival_mode] ?? '',
                    $r->assignedTo?->name,
                    $r->arrival_at?->format('Y-m-d H:i'),
                    $statuses[$r->status] ?? $r->status,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        return view('visitor-management::index', [
            'rows' => $rows,
            'statuses' => VisitorVisit::statuses(),
            'kpis' => $this->kpis(),
            'today' => Carbon::today(),
        ]);
    }
}
