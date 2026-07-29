<?php

namespace App\Modules\ConsumableApproval\Livewire;

use App\Modules\ConsumableApproval\Models\ConsumableApproval;
use App\Modules\ConsumableApproval\Models\ConsumableApprovalItem;
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
#[Title('Consumable Approval')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'cat')]
    public string $categoryFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'request_no', 'status', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
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
        $this->authorize('consumable_approval.delete');
        ConsumableApproval::findOrFail($id)->delete();
        Flux::toast(text: 'Request #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'categoryFilter']);
        $this->resetPage();
    }

    /**
     * @return array{pending:int, approved:int, rejected:int, expired:int, damaged:int, paint:float, va:float, monthly:float}
     */
    protected function kpis(): array
    {
        $counts = ConsumableApproval::query()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $get = fn (string $s) => (int) ($counts[$s] ?? 0);

        // Sum(qty * rate) per consumable category.
        $catConsumption = fn (string $cat) => (float) ConsumableApprovalItem::query()
            ->join('consumable_approvals', 'consumable_approvals.id', '=', 'consumable_approval_items.consumable_approval_id')
            ->where('consumable_approvals.consumable_category', $cat)
            ->selectRaw('coalesce(sum(consumable_approval_items.quantity * coalesce(consumable_approval_items.rate,0)),0) as total')
            ->value('total');

        $monthly = (float) ConsumableApprovalItem::query()
            ->join('consumable_approvals', 'consumable_approvals.id', '=', 'consumable_approval_items.consumable_approval_id')
            ->whereBetween('consumable_approvals.created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->selectRaw('coalesce(sum(consumable_approval_items.quantity * coalesce(consumable_approval_items.rate,0)),0) as total')
            ->value('total');

        return [
            'pending' => $get(ConsumableApproval::STATUS_REQUESTED) + $get(ConsumableApproval::STATUS_UNDER_REVIEW) + $get(ConsumableApproval::STATUS_ON_HOLD),
            'approved' => $get(ConsumableApproval::STATUS_APPROVED),
            'rejected' => $get(ConsumableApproval::STATUS_REJECTED),
            'expired' => ConsumableApprovalItem::query()->where('loss_damage_type', 'date_expired')->count(),
            'damaged' => ConsumableApprovalItem::query()->whereIn('loss_damage_type', ConsumableApproval::damagedLossTypes())->count(),
            'paint' => $catConsumption('paint'),
            'va' => $catConsumption('va'),
            'monthly' => $monthly,
        ];
    }

    /**
     * @return Builder<ConsumableApproval>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return ConsumableApproval::query()
            ->with(['jobCard:id,job_card_no', 'department:id,name'])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('request_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('jobCard', fn ($jc) => $jc->whereLike('job_card_no', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->categoryFilter !== 'all', fn ($q) => $q->where('consumable_category', $this->categoryFilter));
    }

    /** Stream the Consumable Approval Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('consumable_approval.view');

        $rows = $this->baseQuery()->with('items')->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'consumable-approval-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $cats = ConsumableApproval::consumableCategories();
        $statuses = ConsumableApproval::statuses();

        return response()->streamDownload(function () use ($rows, $cats, $statuses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Request No', 'Job Card', 'Category', 'Department', 'Lines', 'Loss Value', 'Status', 'Requested On']);

            foreach ($rows as $r) {
                $value = $r->items->sum(fn ($i) => (float) $i->quantity * (float) ($i->rate ?? 0));
                fputcsv($out, [
                    $r->request_no,
                    $r->jobCard?->job_card_no,
                    $cats[$r->consumable_category] ?? '',
                    $r->department?->name,
                    $r->items_count,
                    number_format($value, 2, '.', ''),
                    $statuses[$r->status] ?? $r->status,
                    $r->created_at?->format('Y-m-d'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        // Department-wise consumption value.
        $byDepartment = ConsumableApprovalItem::query()
            ->join('consumable_approvals', 'consumable_approvals.id', '=', 'consumable_approval_items.consumable_approval_id')
            ->leftJoin('workshop_departments', 'workshop_departments.id', '=', 'consumable_approvals.workshop_department_id')
            ->selectRaw("coalesce(workshop_departments.name, 'Unassigned') as department, coalesce(sum(consumable_approval_items.quantity * coalesce(consumable_approval_items.rate,0)),0) as total")
            ->groupBy('workshop_departments.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return view('consumable-approval::index', [
            'rows' => $rows,
            'statuses' => ConsumableApproval::statuses(),
            'categories' => ConsumableApproval::consumableCategories(),
            'kpis' => $this->kpis(),
            'byDepartment' => $byDepartment,
        ]);
    }
}
