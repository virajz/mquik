<?php

namespace App\Modules\GatePassApproval\Livewire;

use App\Modules\GatePassApproval\Models\GatePassApproval;
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
#[Title('Gate Pass Approval')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'authority')]
    public string $authorityFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'approval_no', 'status', 'outstanding_amount', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingAuthorityFilter(): void
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
        $this->authorize('gate_pass_approval.delete');
        GatePassApproval::findOrFail($id)->delete();
        Flux::toast(text: 'Gate pass approval #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'authorityFilter']);
        $this->resetPage();
    }

    /**
     * @return array{pending:int, delivered_outstanding:int, outstanding_value:float}
     */
    protected function kpis(): array
    {
        $approved = GatePassApproval::approvedStatuses();

        return [
            'pending' => GatePassApproval::query()
                ->whereIn('status', [GatePassApproval::STATUS_REQUESTED, GatePassApproval::STATUS_UNDER_REVIEW, GatePassApproval::STATUS_ON_HOLD])
                ->count(),
            'delivered_outstanding' => GatePassApproval::query()->whereIn('status', $approved)->count(),
            'outstanding_value' => (float) GatePassApproval::query()->whereIn('status', $approved)->sum('outstanding_amount'),
        ];
    }

    /**
     * @return Builder<GatePassApproval>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return GatePassApproval::query()
            ->with(['customer:id,first_name,last_name', 'jobCard:id,job_card_no', 'customerVehicle:id,registration_no'])
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->authorityFilter !== 'all', fn ($q) => $q->where('approval_authority', $this->authorityFilter));
    }

    /** Stream the Gate Pass Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('gate_pass_approval.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'gate-pass-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $types = GatePassApproval::creditTypes();
        $statuses = GatePassApproval::statuses();
        $authorities = GatePassApproval::approvalAuthorities();

        return response()->streamDownload(function () use ($rows, $types, $statuses, $authorities) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Approval No', 'Customer', 'Vehicle', 'Job Card', 'Credit Type', 'Invoice', 'Receipt', 'Outstanding', 'Authority', 'Status', 'Requested']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->approval_no,
                    trim(($r->customer?->first_name ?? '').' '.($r->customer?->last_name ?? '')),
                    $r->customerVehicle?->registration_no,
                    $r->jobCard?->job_card_no,
                    $types[$r->credit_type] ?? '',
                    $r->invoice_amount !== null ? number_format((float) $r->invoice_amount, 2, '.', '') : '',
                    $r->receipt_amount !== null ? number_format((float) $r->receipt_amount, 2, '.', '') : '',
                    $r->outstanding_amount !== null ? number_format((float) $r->outstanding_amount, 2, '.', '') : '',
                    $authorities[$r->approval_authority] ?? '',
                    $statuses[$r->status] ?? $r->status,
                    $r->requested_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        return view('gate-pass-approval::index', [
            'rows' => $rows,
            'statuses' => GatePassApproval::statuses(),
            'authorities' => GatePassApproval::approvalAuthorities(),
            'kpis' => $this->kpis(),
        ]);
    }
}
