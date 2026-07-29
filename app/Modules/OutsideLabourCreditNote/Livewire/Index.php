<?php

namespace App\Modules\OutsideLabourCreditNote\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\OutsideLabourCreditNote\Models\OutsideLabourCreditNote;
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
#[Title('Outside Labour Credit / Debit Note')]
class Index extends Component
{
    use SearchesPickerOptions;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'type')]
    public string $typeFilter = 'all';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'vendor')]
    public string $vendorFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    public string $vendorSearch = '';

    protected array $sortable = ['id', 'note_no', 'status', 'amount', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
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
        $this->authorize('outside_labour_credit_note.delete');
        OutsideLabourCreditNote::findOrFail($id)->delete();
        Flux::toast(text: 'Note #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'typeFilter', 'statusFilter', 'vendorFilter']);
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
     * @return array{credit:int, debit:int, cancelled:int}
     */
    protected function kpis(): array
    {
        $posted = fn (string $type) => OutsideLabourCreditNote::query()
            ->where('note_type', $type)->where('status', OutsideLabourCreditNote::STATUS_POSTED)->count();

        return [
            'credit' => $posted('credit_note'),
            'debit' => $posted('debit_note'),
            'cancelled' => OutsideLabourCreditNote::query()->where('status', OutsideLabourCreditNote::STATUS_CANCELLED)->count(),
        ];
    }

    /**
     * @return Builder<OutsideLabourCreditNote>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return OutsideLabourCreditNote::query()
            ->with(['vendor:id,name', 'return:id,return_no'])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->whereLike('note_no', '%'.$search.'%', caseSensitive: false))
            ->when($this->typeFilter !== 'all', fn ($q) => $q->where('note_type', $this->typeFilter))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->vendorFilter !== 'all', fn ($q) => $q->where('vendor_id', (int) $this->vendorFilter));
    }

    /** Stream the Outside Labour Return Register CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('outside_labour_credit_note.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'outside-labour-credit-note-register-'.Carbon::now()->format('Ymd-His').'.csv';
        $types = OutsideLabourCreditNote::noteTypes();
        $reasons = OutsideLabourCreditNote::returnReasons();
        $statuses = OutsideLabourCreditNote::statuses();

        return response()->streamDownload(function () use ($rows, $types, $reasons, $statuses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Note No', 'Type', 'Vendor', 'Return Ref', 'Reason', 'Lines', 'Amount', 'Status', 'Raised On']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->note_no,
                    $types[$r->note_type] ?? '',
                    $r->vendor?->name,
                    $r->return?->return_no,
                    $reasons[$r->return_reason] ?? '',
                    $r->items_count,
                    $r->amount !== null ? number_format((float) $r->amount, 2, '.', '') : '',
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

        return view('outside-labour-credit-note::index', [
            'rows' => $rows,
            'noteTypes' => OutsideLabourCreditNote::noteTypes(),
            'statuses' => OutsideLabourCreditNote::statuses(),
            'kpis' => $this->kpis(),
        ]);
    }
}
