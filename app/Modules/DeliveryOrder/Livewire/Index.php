<?php

namespace App\Modules\DeliveryOrder\Livewire;

use App\Modules\DeliveryOrder\Models\DeliveryOrder;
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
#[Title('Delivery Order (DO)')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'do_no', 'status', 'do_amount', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
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
        $this->authorize('delivery_order.delete');
        DeliveryOrder::findOrFail($id)->delete();
        Flux::toast(text: 'DO #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter']);
        $this->resetPage();
    }

    /**
     * "Amount Mismatch Cases" = received DOs whose amount differs from the proforma.
     *
     * @return array{pending:int, received:int, mismatch:int}
     */
    protected function kpis(): array
    {
        $counts = DeliveryOrder::query()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $get = fn (string $s) => (int) ($counts[$s] ?? 0);

        $mismatch = DeliveryOrder::query()
            ->whereNotNull('do_amount')
            ->whereNotNull('proforma_amount')
            ->whereColumn('do_amount', '!=', 'proforma_amount')
            ->count();

        return [
            'pending' => $get(DeliveryOrder::STATUS_REQUESTED) + $get(DeliveryOrder::STATUS_UNDER_VERIFICATION) + $get(DeliveryOrder::STATUS_ON_HOLD),
            'received' => $get(DeliveryOrder::STATUS_RECEIVED) + $get(DeliveryOrder::STATUS_MISMATCH_APPROVED) + $get(DeliveryOrder::STATUS_REQUESTED_TO_SETTLE),
            'mismatch' => $mismatch,
        ];
    }

    /**
     * @return Builder<DeliveryOrder>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return DeliveryOrder::query()
            ->with(['jobCard:id,job_card_no', 'insuranceCompany:id,name'])
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter));
    }

    /** Stream the Delivery Order Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('delivery_order.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'delivery-order-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $statuses = DeliveryOrder::statuses();

        return response()->streamDownload(function () use ($rows, $statuses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['DO No', 'Job Card', 'Insurance', 'Claim No', 'Proforma Amt', 'DO Amt', 'Mismatch', 'Status', 'DO Received']);

            foreach ($rows as $r) {
                $delta = $r->mismatchDelta();
                fputcsv($out, [
                    $r->do_no,
                    $r->jobCard?->job_card_no,
                    $r->insuranceCompany?->name,
                    $r->claim_number,
                    $r->proforma_amount !== null ? number_format((float) $r->proforma_amount, 2, '.', '') : '',
                    $r->do_amount !== null ? number_format((float) $r->do_amount, 2, '.', '') : '',
                    $delta !== null && $delta !== 0.0 ? number_format($delta, 2, '.', '') : '',
                    $statuses[$r->status] ?? $r->status,
                    $r->do_received_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        return view('delivery-order::index', [
            'rows' => $rows,
            'statuses' => DeliveryOrder::statuses(),
            'kpis' => $this->kpis(),
        ]);
    }
}
