<?php

namespace App\Modules\CustomerComplaint\Livewire;

use App\Modules\CustomerComplaint\Models\CustomerComplaint;
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
#[Title('Customer Complaints')]
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

    protected array $sortable = ['id', 'complaint_no', 'status', 'priority', 'created_at'];

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
        $this->authorize('customer_complaint.delete');
        CustomerComplaint::findOrFail($id)->delete();
        Flux::toast(text: 'Complaint #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'typeFilter']);
        $this->resetPage();
    }

    /**
     * @return array{received:int, open:int, resolved:int, avg_score:float}
     */
    protected function kpis(): array
    {
        return [
            'received' => CustomerComplaint::query()->count(),
            'open' => CustomerComplaint::query()->whereIn('status', CustomerComplaint::openStatuses())->count(),
            'resolved' => CustomerComplaint::query()->where('status', CustomerComplaint::STATUS_RESOLVED)->count(),
            'avg_score' => round((float) CustomerComplaint::query()->whereNotNull('achieved_score')->avg('achieved_score'), 2),
        ];
    }

    /**
     * @return Builder<CustomerComplaint>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return CustomerComplaint::query()
            ->with(['customer:id,first_name,last_name', 'customerVehicle:id,registration_no'])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('complaint_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('invoice_reference', '%'.$search.'%', caseSensitive: false);
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter !== 'all', fn ($q) => $q->where('complaint_type', $this->typeFilter));
    }

    /** Stream the Customer Complaint Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('customer_complaint.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'customer-complaint-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $types = CustomerComplaint::complaintTypes();
        $statuses = CustomerComplaint::statuses();

        return response()->streamDownload(function () use ($rows, $types, $statuses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Complaint No', 'Customer', 'Vehicle', 'Type', 'Priority', 'Status', 'Satisfaction', 'Opened', 'Closed']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->complaint_no,
                    trim(($r->customer?->first_name ?? '').' '.($r->customer?->last_name ?? '')),
                    $r->customerVehicle?->registration_no,
                    $types[$r->complaint_type] ?? '',
                    ucfirst($r->priority),
                    $statuses[$r->status] ?? $r->status,
                    $r->achieved_score,
                    $r->opened_at?->format('Y-m-d H:i'),
                    $r->closed_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        return view('customer-complaint::index', [
            'rows' => $rows,
            'statuses' => CustomerComplaint::statuses(),
            'types' => CustomerComplaint::complaintTypes(),
            'kpis' => $this->kpis(),
        ]);
    }
}
