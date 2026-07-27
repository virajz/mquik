<?php

namespace App\Modules\VendorPurchaseInquiry\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiry;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Vendor Purchase Inquiries')]
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

    protected array $sortable = ['id', 'vpi_no', 'status', 'priority_id', 'created_at'];

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
        $this->authorize('vendor_purchase_inquiry.delete');

        VendorPurchaseInquiry::findOrFail($id)->delete();

        Flux::toast(text: 'RFQ #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'typeFilter', 'vendorFilter']);
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

    /**
     * Dashboard counters.
     *
     * @return array{pending:int, in_progress:int, completed:int, cancelled:int}
     */
    protected function kpis(): array
    {
        $counts = VendorPurchaseInquiry::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $get = fn (string $s) => (int) ($counts[$s] ?? 0);

        return [
            'pending' => $get(VendorPurchaseInquiry::STATUS_PENDING),
            'in_progress' => $get(VendorPurchaseInquiry::STATUS_IN_PROGRESS),
            'completed' => $get(VendorPurchaseInquiry::STATUS_COMPLETED),
            'cancelled' => $get(VendorPurchaseInquiry::STATUS_CANCELLED),
        ];
    }

    public function render()
    {
        $search = trim($this->search);

        $rows = VendorPurchaseInquiry::query()
            ->with([
                'vendor:id,name',
                'priority:id,name',
                'jobCard:id,job_card_no',
            ])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('vpi_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('jobCard', fn ($jc) => $jc->whereLike('job_card_no', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter !== 'all', fn ($q) => $q->where('inquiry_type', $this->typeFilter))
            ->when($this->vendorFilter !== 'all', fn ($q) => $q->where('vendor_id', (int) $this->vendorFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('vendor-purchase-inquiry::index', [
            'rows' => $rows,
            'statuses' => VendorPurchaseInquiry::statuses(),
            'inquiryTypes' => VendorPurchaseInquiry::inquiryTypes(),
            'kpis' => $this->kpis(),
        ]);
    }
}
