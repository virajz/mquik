<?php

namespace App\Modules\OutsideLabourProgress\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\OutsideLabourOrder\Models\OutsideLabourOrder;
use App\Modules\VendorMaster\Models\VendorMaster;
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
#[Title('Outside Labour Progress')]
class Index extends Component
{
    use SearchesPickerOptions;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'vendor')]
    public string $vendorFilter = 'all';

    #[Url(as: 'from')]
    public string $fromDate = '';

    #[Url(as: 'to')]
    public string $toDate = '';

    public string $vendorSearch = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'vendorFilter', 'fromDate', 'toDate']);
        $this->resetPage();
    }

    #[Computed]
    public function vendors()
    {
        return $this->pickerOptions(
            query: VendorMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'code'],
            term: $this->vendorSearch,
            selected: is_numeric($this->vendorFilter) ? (int) $this->vendorFilter : null,
            columns: ['id', 'name'],
            limit: 30,
        );
    }

    /** @return array<string, string> */
    #[Computed]
    public function statuses(): array
    {
        return OutsideLabourOrder::statuses();
    }

    /**
     * @return Builder<OutsideLabourOrder>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return OutsideLabourOrder::query()
            ->with([
                'jobCard:id,job_card_no',
                'vendor:id,name',
                'orderType:id,name',
                'items:id,outside_labour_order_id,hours',
                'pauses',
            ])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('order_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('jobCard', fn ($jc) => $jc->whereLike('job_card_no', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->vendorFilter !== 'all', fn ($q) => $q->where('vendor_id', (int) $this->vendorFilter))
            ->when($this->fromDate !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->fromDate))
            ->when($this->toDate !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->toDate))
            ->latest('created_at');
    }

    /** Stream the Outside Labour Status Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('outside_labour_progress.export');

        $rows = $this->baseQuery()->get();
        $filename = 'outside-labour-status-'.Carbon::now()->format('Ymd-His').'.csv';
        $statuses = OutsideLabourOrder::statuses();

        return response()->streamDownload(function () use ($rows, $statuses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['OLO No', 'Job Card', 'Vendor', 'Order Type', 'Status', 'Item Hours', 'Gross Mins', 'Pause Mins', 'Net TAT Mins', 'Completion', 'Started', 'Ended']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->order_no,
                    $r->jobCard?->job_card_no,
                    $r->vendor?->name,
                    $r->orderType?->name,
                    $statuses[$r->status] ?? $r->status,
                    $r->totalItemHours(),
                    $r->grossMinutes() ?? '',
                    $r->pauseMinutes(),
                    $r->netTatMinutes() ?? '',
                    $r->completion_type,
                    $r->started_at?->format('Y-m-d H:i'),
                    $r->ended_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $paginator = $this->baseQuery()->paginate(25);

        return view('outside-labour-progress::index', [
            'paginator' => $paginator,
        ]);
    }
}
