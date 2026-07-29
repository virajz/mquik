<?php

namespace App\Modules\IpoCancelApproval\Livewire;

use App\Modules\IpoCancelApproval\Models\IpoCancelApproval;
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
#[Title('IPO Cancel Approval')]
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

    protected array $sortable = ['id', 'cancel_no', 'status', 'created_at', 'decided_at'];

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
        $this->authorize('ipo_cancel_approval.delete');

        IpoCancelApproval::findOrFail($id)->delete();

        Flux::toast(text: 'IPO cancel request #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter']);
        $this->resetPage();
    }

    /**
     * @return array{under_review:int, approved:int, rejected:int, closed:int}
     */
    protected function kpis(): array
    {
        $counts = IpoCancelApproval::query()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $get = fn (string $s) => (int) ($counts[$s] ?? 0);

        return [
            'under_review' => $get(IpoCancelApproval::STATUS_UNDER_REVIEW) + $get(IpoCancelApproval::STATUS_NEEDS_CLARIFICATION),
            'approved' => $get(IpoCancelApproval::STATUS_APPROVED),
            'rejected' => $get(IpoCancelApproval::STATUS_REJECTED),
            'closed' => $get(IpoCancelApproval::STATUS_CANCELLED) + $get(IpoCancelApproval::STATUS_REVERSED),
        ];
    }

    /**
     * @return Builder<IpoCancelApproval>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return IpoCancelApproval::query()
            ->with(['jobCard:id,job_card_no', 'internalPartOrder:id,order_no', 'spare:id,name', 'employee:id,name'])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('cancel_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('jobCard', fn ($jc) => $jc->whereLike('job_card_no', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter));
    }

    /** Stream the IPO Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('ipo_cancel_approval.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'ipo-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $reasons = IpoCancelApproval::cancellationReasons();
        $statuses = IpoCancelApproval::statuses();

        return response()->streamDownload(function () use ($rows, $reasons, $statuses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Cancel No', 'Job Card', 'IPO', 'Spare', 'Reason', 'Qty', 'Status', 'Decided At']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->cancel_no,
                    $r->jobCard?->job_card_no,
                    $r->internalPartOrder?->order_no,
                    $r->spare?->name,
                    $reasons[$r->cancellation_reason] ?? '',
                    $r->quantity,
                    $statuses[$r->status] ?? $r->status,
                    $r->decided_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        return view('ipo-cancel-approval::index', [
            'rows' => $rows,
            'statuses' => IpoCancelApproval::statuses(),
            'reasons' => IpoCancelApproval::cancellationReasons(),
            'kpis' => $this->kpis(),
        ]);
    }
}
