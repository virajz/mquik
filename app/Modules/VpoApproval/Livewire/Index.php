<?php

namespace App\Modules\VpoApproval\Livewire;

use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VpoApproval\Models\VpoApproval;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('VPO Approval')]
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

    protected array $sortable = ['id', 'approval_no', 'status', 'created_at', 'approved_at'];

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
        $this->authorize('vpo_approval.delete');
        VpoApproval::findOrFail($id)->delete();
        Flux::toast(text: 'PO approval #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'typeFilter']);
        $this->resetPage();
    }

    #[Computed]
    public function vendors()
    {
        return VendorMaster::query()->where('is_active', true)->orderBy('name')->limit(200)->get(['id', 'name']);
    }

    /**
     * @return array{pending:int, partially:int, approved:int, rejected:int}
     */
    protected function kpis(): array
    {
        $counts = VpoApproval::query()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $get = fn (string $s) => (int) ($counts[$s] ?? 0);

        return [
            'pending' => $get(VpoApproval::STATUS_SENT) + $get(VpoApproval::STATUS_UNDER_REVIEW),
            'partially' => $get(VpoApproval::STATUS_PARTIALLY_APPROVED),
            'approved' => $get(VpoApproval::STATUS_FULLY_APPROVED) + $get(VpoApproval::STATUS_REAPPROVED),
            'rejected' => $get(VpoApproval::STATUS_REJECTED),
        ];
    }

    /**
     * @return Builder<VpoApproval>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return VpoApproval::query()
            ->with(['vendor:id,name', 'inquiry:id,vpi_no', 'jobCard:id,job_card_no'])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('approval_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('jobCard', fn ($jc) => $jc->whereLike('job_card_no', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter !== 'all', fn ($q) => $q->where('po_approval_type', $this->typeFilter));
    }

    /** Stream the Purchase Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('vpo_approval.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'vpo-approval-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $types = VpoApproval::poApprovalTypes();
        $statuses = VpoApproval::statuses();

        return response()->streamDownload(function () use ($rows, $types, $statuses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Approval No', 'Type', 'Vendor', 'VPI', 'Job Card', 'Lines', 'Status', 'Approved At']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->approval_no,
                    $types[$r->po_approval_type] ?? '',
                    $r->vendor?->name,
                    $r->inquiry?->vpi_no,
                    $r->jobCard?->job_card_no,
                    $r->items_count,
                    $statuses[$r->status] ?? $r->status,
                    $r->approved_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        return view('vpo-approval::index', [
            'rows' => $rows,
            'statuses' => VpoApproval::statuses(),
            'poTypes' => VpoApproval::poApprovalTypes(),
            'kpis' => $this->kpis(),
        ]);
    }
}
