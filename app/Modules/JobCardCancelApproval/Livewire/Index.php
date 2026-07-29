<?php

namespace App\Modules\JobCardCancelApproval\Livewire;

use App\Modules\JobCardCancelApproval\Models\JobCardCancelApproval;
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
#[Title('Job Card Cancel Approval')]
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
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'approval_no', 'status', 'created_at', 'decided_at'];

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
        $this->authorize('job_card_cancel_approval.delete');

        JobCardCancelApproval::findOrFail($id)->delete();

        Flux::toast(text: 'Cancel approval #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'typeFilter']);
        $this->resetPage();
    }

    /**
     * @return array{pending:int, under_review:int, approved:int, rejected:int}
     */
    protected function kpis(): array
    {
        $counts = JobCardCancelApproval::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $get = fn (string $s) => (int) ($counts[$s] ?? 0);

        return [
            'pending' => $get(JobCardCancelApproval::STATUS_PENDING) + $get(JobCardCancelApproval::STATUS_REQUESTED),
            'under_review' => $get(JobCardCancelApproval::STATUS_UNDER_REVIEW),
            'approved' => $get(JobCardCancelApproval::STATUS_APPROVED),
            'rejected' => $get(JobCardCancelApproval::STATUS_REJECTED) + $get(JobCardCancelApproval::STATUS_CANCELLED),
        ];
    }

    /**
     * @return Builder<JobCardCancelApproval>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return JobCardCancelApproval::query()
            ->with([
                'jobCard:id,job_card_no',
                'cancelReason:id,name',
                'employee:id,name',
            ])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('approval_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('jobCard', fn ($jc) => $jc->whereLike('job_card_no', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter !== 'all', fn ($q) => $q->where('cancellation_type', $this->typeFilter));
    }

    /** Stream the Job Card Cancel Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('job_card_cancel_approval.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'job-card-cancel-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $types = JobCardCancelApproval::cancellationTypes();
        $statuses = JobCardCancelApproval::statuses();
        $refunds = JobCardCancelApproval::refundStatuses();

        return response()->streamDownload(function () use ($rows, $types, $statuses, $refunds) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Approval No', 'Job Card', 'Cancellation Type', 'Reason', 'Raised By', 'Refund', 'Status', 'Decided At']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->approval_no,
                    $r->jobCard?->job_card_no,
                    $types[$r->cancellation_type] ?? '',
                    $r->cancelReason?->name,
                    $r->employee?->name,
                    $refunds[$r->refund_status] ?? '',
                    $statuses[$r->status] ?? $r->status,
                    $r->decided_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('job-card-cancel-approval::index', [
            'rows' => $rows,
            'statuses' => JobCardCancelApproval::statuses(),
            'cancellationTypes' => JobCardCancelApproval::cancellationTypes(),
            'kpis' => $this->kpis(),
        ]);
    }
}
