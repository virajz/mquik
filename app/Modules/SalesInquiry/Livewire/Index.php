<?php

namespace App\Modules\SalesInquiry\Livewire;

use App\Modules\SalesInquiry\Models\SalesInquiry;
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
#[Title('Sales Inquiries')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'source')]
    public string $sourceFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'inquiry_no', 'status', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSourceFilter(): void
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
        $this->authorize('sales_inquiry.delete');
        SalesInquiry::findOrFail($id)->delete();
        Flux::toast(text: 'Inquiry #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'sourceFilter']);
        $this->resetPage();
    }

    /**
     * @return array{new_today:int, open:int, quotations:int, converted:int, lost:int, conversion_rate:float, revenue:float}
     */
    protected function kpis(): array
    {
        $total = SalesInquiry::query()->count();
        $converted = SalesInquiry::query()->where('status', SalesInquiry::STATUS_CONVERTED)->count();

        return [
            'new_today' => SalesInquiry::query()->whereDate('created_at', Carbon::today())->count(),
            'open' => SalesInquiry::query()->whereIn('status', SalesInquiry::openStatuses())->count(),
            'quotations' => SalesInquiry::query()->where('status', SalesInquiry::STATUS_QUOTATION_SENT)->count(),
            'converted' => $converted,
            'lost' => SalesInquiry::query()->where('status', SalesInquiry::STATUS_LOST)->count(),
            'conversion_rate' => $total > 0 ? round($converted / $total * 100, 1) : 0.0,
            'revenue' => (float) SalesInquiry::query()->where('status', SalesInquiry::STATUS_CONVERTED)->sum('estimated_value'),
        ];
    }

    /**
     * @return Builder<SalesInquiry>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return SalesInquiry::query()
            ->with(['customer:id,first_name,last_name', 'assignedTo:id,name'])
            ->when($search !== '', fn ($q) => $q->whereLike('inquiry_no', '%'.$search.'%', caseSensitive: false))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->sourceFilter !== 'all', fn ($q) => $q->where('inquiry_source', $this->sourceFilter));
    }

    /** Stream the Sales Inquiry Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('sales_inquiry.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'sales-inquiry-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $types = SalesInquiry::inquiryTypes();
        $sources = SalesInquiry::inquirySources();
        $statuses = SalesInquiry::statuses();

        return response()->streamDownload(function () use ($rows, $types, $sources, $statuses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Inquiry No', 'Customer', 'Type', 'Source', 'Assigned To', 'Est. Value', 'Status', 'Inquiry Date']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->inquiry_no,
                    trim(($r->customer?->first_name ?? '').' '.($r->customer?->last_name ?? '')),
                    $types[$r->inquiry_type] ?? '',
                    $sources[$r->inquiry_source] ?? '',
                    $r->assignedTo?->name,
                    $r->estimated_value !== null ? number_format((float) $r->estimated_value, 2, '.', '') : '',
                    $statuses[$r->status] ?? $r->status,
                    $r->inquiry_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        // Source-wise inquiry counts.
        $bySource = SalesInquiry::query()
            ->selectRaw('inquiry_source, count(*) as total')
            ->whereNotNull('inquiry_source')
            ->groupBy('inquiry_source')
            ->orderByDesc('total')
            ->get();

        return view('sales-inquiry::index', [
            'rows' => $rows,
            'statuses' => SalesInquiry::statuses(),
            'sources' => SalesInquiry::inquirySources(),
            'kpis' => $this->kpis(),
            'bySource' => $bySource,
        ]);
    }
}
