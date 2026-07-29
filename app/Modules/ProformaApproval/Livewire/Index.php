<?php

namespace App\Modules\ProformaApproval\Livewire;

use App\Modules\ProformaApproval\Models\ProformaApproval;
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
#[Title('Proforma Approval')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'stage')]
    public string $stageFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'approval_no', 'status', 'amount', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStageFilter(): void
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
        $this->authorize('proforma_approval.delete');
        ProformaApproval::findOrFail($id)->delete();
        Flux::toast(text: 'Proforma approval #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'stageFilter']);
        $this->resetPage();
    }

    /**
     * @return array{store:int, advisor:int, admin:int}
     */
    protected function kpis(): array
    {
        $counts = ProformaApproval::query()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $get = fn (string $s) => (int) ($counts[$s] ?? 0);

        return [
            'store' => $get(ProformaApproval::STATUS_STORE_PENDING),
            'advisor' => $get(ProformaApproval::STATUS_ADVISOR_PENDING),
            'admin' => $get(ProformaApproval::STATUS_ADMIN_PENDING),
        ];
    }

    /**
     * @return Builder<ProformaApproval>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return ProformaApproval::query()
            ->with(['jobCard:id,job_card_no', 'customer:id,first_name,last_name'])
            ->withCount('checkpoints')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('approval_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('jobCard', fn ($jc) => $jc->whereLike('job_card_no', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->stageFilter !== 'all', fn ($q) => $q->where('approval_stage', $this->stageFilter));
    }

    /** Stream the Proforma Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('proforma_approval.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'proforma-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $stages = ProformaApproval::stages();
        $statuses = ProformaApproval::statuses();

        return response()->streamDownload(function () use ($rows, $stages, $statuses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Approval No', 'Job Card', 'Stage', 'Amount', 'Status', 'Requested', 'Admin Approved', 'Converted']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->approval_no,
                    $r->jobCard?->job_card_no,
                    $stages[$r->approval_stage] ?? '',
                    $r->amount !== null ? number_format((float) $r->amount, 2, '.', '') : '',
                    $statuses[$r->status] ?? $r->status,
                    $r->requested_at?->format('Y-m-d H:i'),
                    $r->admin_approved_at?->format('Y-m-d H:i'),
                    $r->converted_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        return view('proforma-approval::index', [
            'rows' => $rows,
            'statuses' => ProformaApproval::statuses(),
            'stages' => ProformaApproval::stages(),
            'kpis' => $this->kpis(),
        ]);
    }
}
