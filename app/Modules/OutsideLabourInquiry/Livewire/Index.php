<?php

namespace App\Modules\OutsideLabourInquiry\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\OutsideLabourInquiry\Models\OutsideLabourInquiry;
use App\Modules\ServiceSpecialistMaster\Models\ServiceSpecialistMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Outside Labour Inquiries')]
class Index extends Component
{
    use SearchesPickerOptions;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'type')]
    public string $typeFilter = 'all';

    #[Url(as: 'vendor')]
    public string $vendorFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    public string $vendorSearch = '';

    protected array $sortable = ['id', 'inquiry_no', 'status', 'priority_id', 'created_at', 'promised_to'];

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

    public function updatingVendorFilter(): void
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
        $this->authorize('outside_labour_inquiry.delete');

        OutsideLabourInquiry::findOrFail($id)->delete();

        Flux::toast(text: 'Inquiry #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'typeFilter', 'vendorFilter']);
        $this->resetPage();
    }

    #[Computed]
    public function inquiryTypes()
    {
        return ServiceSpecialistMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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

    /**
     * Dashboard counters: open, pending responses, completed (work order
     * issued), cancelled.
     *
     * @return array{open:int, pending:int, completed:int, cancelled:int}
     */
    protected function kpis(): array
    {
        $counts = OutsideLabourInquiry::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $get = fn (string $s) => (int) ($counts[$s] ?? 0);

        return [
            // Open = anything not yet issued/rejected/cancelled.
            'open' => $get(OutsideLabourInquiry::STATUS_RESPONSE_PENDING)
                + $get(OutsideLabourInquiry::STATUS_PARTIALLY_RESPONDED)
                + $get(OutsideLabourInquiry::STATUS_FULLY_RESPONDED),
            'pending' => $get(OutsideLabourInquiry::STATUS_RESPONSE_PENDING),
            'completed' => $get(OutsideLabourInquiry::STATUS_WORK_ORDER_ISSUED),
            'cancelled' => $get(OutsideLabourInquiry::STATUS_CANCELLED)
                + $get(OutsideLabourInquiry::STATUS_REJECTED),
        ];
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = OutsideLabourInquiry::query()
            ->with([
                'inquiryType:id,name',
                'vendor:id,name',
                'priority:id,name',
                'jobCard:id,job_card_no',
            ])
            ->withCount('scopes')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('inquiry_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('jobCard', fn ($jc) => $jc->whereLike('job_card_no', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter !== 'all', fn ($q) => $q->where('inquiry_type_id', (int) $this->typeFilter))
            ->when($this->vendorFilter !== 'all', fn ($q) => $q->where('vendor_id', (int) $this->vendorFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('outside-labour-inquiry::index', [
            'rows' => $rows,
            'statuses' => OutsideLabourInquiry::statuses(),
            'kpis' => $this->kpis(),
        ]);
    }
}
