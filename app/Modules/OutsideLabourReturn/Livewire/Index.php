<?php

namespace App\Modules\OutsideLabourReturn\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\OutsideLabourReturn\Models\OutsideLabourReturn;
use App\Modules\VendorMaster\Models\VendorMaster;
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
#[Title('Outside Labour Return / Warranty')]
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

    protected array $sortable = ['id', 'return_no', 'status', 'recovery_amount', 'created_at'];

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
        $this->authorize('outside_labour_return.delete');
        OutsideLabourReturn::findOrFail($id)->delete();
        Flux::toast(text: 'Return #'.$id.' deleted.', variant: 'success');
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
            searchColumns: ['name', 'vendor_code'],
            term: $this->vendorSearch,
            selected: is_numeric($this->vendorFilter) ? (int) $this->vendorFilter : null,
            columns: ['id', 'name'],
            limit: 30,
        );
    }

    /**
     * @return array{open_claims:int, recovery_pending:float}
     */
    protected function kpis(): array
    {
        $open = OutsideLabourReturn::openStatuses();

        return [
            'open_claims' => OutsideLabourReturn::query()->whereIn('status', $open)->count(),
            'recovery_pending' => (float) OutsideLabourReturn::query()->whereIn('status', $open)->sum('recovery_amount'),
        ];
    }

    /**
     * @return Builder<OutsideLabourReturn>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return OutsideLabourReturn::query()
            ->with(['vendor:id,name', 'customerVehicle:id,registration_no'])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->whereLike('return_no', '%'.$search.'%', caseSensitive: false))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter !== 'all', fn ($q) => $q->where('return_type', $this->typeFilter))
            ->when($this->vendorFilter !== 'all', fn ($q) => $q->where('vendor_id', (int) $this->vendorFilter));
    }

    /** Stream the Outside Labour Return Register CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('outside_labour_return.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'outside-labour-return-register-'.Carbon::now()->format('Ymd-His').'.csv';
        $types = OutsideLabourReturn::returnTypes();
        $claims = OutsideLabourReturn::claimTypes();
        $reasons = OutsideLabourReturn::returnReasons();
        $statuses = OutsideLabourReturn::statuses();

        return response()->streamDownload(function () use ($rows, $types, $claims, $reasons, $statuses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Return No', 'Vendor', 'Vehicle', 'Return Type', 'Claim Type', 'Reason', 'Lines', 'Status', 'Recovery Amount', 'Raised On']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->return_no,
                    $r->vendor?->name,
                    $r->customerVehicle?->registration_no,
                    $types[$r->return_type] ?? '',
                    $claims[$r->claim_type] ?? '',
                    $reasons[$r->return_reason] ?? '',
                    $r->items_count,
                    $statuses[$r->status] ?? $r->status,
                    $r->recovery_amount !== null ? number_format((float) $r->recovery_amount, 2, '.', '') : '',
                    $r->created_at?->format('Y-m-d'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        return view('outside-labour-return::index', [
            'rows' => $rows,
            'statuses' => OutsideLabourReturn::statuses(),
            'returnTypes' => OutsideLabourReturn::returnTypes(),
            'kpis' => $this->kpis(),
        ]);
    }
}
