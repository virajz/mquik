<?php

namespace App\Modules\DocumentDelivery\Livewire;

use App\Modules\DocumentDelivery\Models\DocumentDelivery;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
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
#[Title('Document Delivery')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'mode')]
    public string $modeFilter = 'all';

    #[Url(as: 'company')]
    public string $companyFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'delivery_no', 'status', 'created_at', 'delivered_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingModeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCompanyFilter(): void
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
        $this->authorize('document_delivery.delete');

        DocumentDelivery::findOrFail($id)->delete();

        Flux::toast(text: 'Document delivery #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'modeFilter', 'companyFilter']);
        $this->resetPage();
    }

    #[Computed]
    public function companies()
    {
        return InsuranceCompanyMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /**
     * @return array{pending:int, in_transit:int, delivered:int, returned:int}
     */
    protected function kpis(): array
    {
        $counts = DocumentDelivery::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $get = fn (string $s) => (int) ($counts[$s] ?? 0);

        return [
            'pending' => $get(DocumentDelivery::STATUS_PENDING),
            'in_transit' => $get(DocumentDelivery::STATUS_IN_TRANSIT),
            'delivered' => $get(DocumentDelivery::STATUS_DELIVERED),
            'returned' => $get(DocumentDelivery::STATUS_RETURNED) + $get(DocumentDelivery::STATUS_RE_SENT),
        ];
    }

    /**
     * @return Builder<DocumentDelivery>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return DocumentDelivery::query()
            ->with([
                'jobCard:id,job_card_no',
                'customerVehicle:id,registration_no',
                'insuranceCompany:id,name',
                'courierCompany:id,name',
            ])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->modeFilter !== 'all', fn ($q) => $q->where('delivery_mode', $this->modeFilter))
            ->when($this->companyFilter !== 'all', fn ($q) => $q->where('insurance_company_id', (int) $this->companyFilter));
    }

    /** Stream the Document Delivery report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('document_delivery.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'document-delivery-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $modes = DocumentDelivery::deliveryModes();
        $statuses = DocumentDelivery::statuses();
        $acks = DocumentDelivery::acknowledgementTypes();

        return response()->streamDownload(function () use ($rows, $modes, $statuses, $acks) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Delivery No', 'Job Card', 'Vehicle', 'Insurer', 'Courier', 'Mode', 'Acknowledgement', 'Docs', 'Status', 'Delivered At']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->delivery_no,
                    $r->jobCard?->job_card_no,
                    $r->customerVehicle?->registration_no,
                    $r->insuranceCompany?->name,
                    $r->courierCompany?->name,
                    $modes[$r->delivery_mode] ?? '',
                    $acks[$r->acknowledgement_type] ?? '',
                    $r->items_count,
                    $statuses[$r->status] ?? $r->status,
                    $r->delivered_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);

        return view('document-delivery::index', [
            'rows' => $rows,
            'statuses' => DocumentDelivery::statuses(),
            'deliveryModes' => DocumentDelivery::deliveryModes(),
            'kpis' => $this->kpis(),
        ]);
    }
}
