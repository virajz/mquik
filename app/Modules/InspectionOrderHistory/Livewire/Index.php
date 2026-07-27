<?php

namespace App\Modules\InspectionOrderHistory\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\BayMaster\Models\BayMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrder;
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
#[Title('VIO History')]
class Index extends Component
{
    use SearchesPickerOptions;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'tech')]
    public string $technicianFilter = 'all';

    #[Url(as: 'bay')]
    public string $bayFilter = 'all';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'from')]
    public string $fromDate = '';

    #[Url(as: 'to')]
    public string $toDate = '';

    public string $technicianSearch = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'technicianFilter', 'bayFilter', 'statusFilter', 'fromDate', 'toDate']);
        $this->resetPage();
    }

    /**
     * The report query, shared by the on-screen table and the CSV download so
     * the export is always exactly what the user is looking at.
     *
     * @return Builder<VehicleInspectionOrder>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return VehicleInspectionOrder::query()
            ->with([
                'jobCard:id,job_card_no,customer_vehicle_id',
                'jobCard.customerVehicle:id,registration_no',
                'technician:id,name',
                'advisor:id,name',
                'bay:id,name',
                'priority:id,name',
            ])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('order_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereHas('jobCard', fn ($jc) => $jc->whereLike('job_card_no', '%'.$search.'%', caseSensitive: false));
            }))
            ->when($this->technicianFilter !== 'all', fn ($q) => $q->where('technician_id', (int) $this->technicianFilter))
            ->when($this->bayFilter !== 'all', fn ($q) => $q->where('bay_id', (int) $this->bayFilter))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->fromDate !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->fromDate))
            ->when($this->toDate !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->toDate))
            ->latest('created_at');
    }

    #[Computed]
    public function technicians()
    {
        return $this->pickerOptions(
            query: EmployeeMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'employee_code'],
            term: $this->technicianSearch,
            selected: is_numeric($this->technicianFilter) ? (int) $this->technicianFilter : null,
            columns: ['id', 'name'],
            limit: 30,
        );
    }

    #[Computed]
    public function bays()
    {
        return BayMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /** @return array<string, string> */
    #[Computed]
    public function statuses(): array
    {
        return VehicleInspectionOrder::statuses();
    }

    /**
     * TAT (turnaround) in human form: started → ended. Null while still open.
     */
    protected function tat(VehicleInspectionOrder $order): ?string
    {
        if (! $order->started_at || ! $order->ended_at) {
            return null;
        }

        return $order->started_at->diffForHumans($order->ended_at, ['syntax' => Carbon::DIFF_ABSOLUTE, 'parts' => 2]);
    }

    /** Stream the filtered history as CSV — exactly what's on screen. */
    public function download(): StreamedResponse
    {
        $this->authorize('inspection_order_history.export');

        $rows = $this->baseQuery()->get();
        $filename = 'vio-history-'.Carbon::now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['VIO No', 'Job Card', 'Vehicle', 'Technician', 'Advisor', 'Bay', 'Priority', 'Status', 'Assigned', 'Started', 'Ended', 'TAT']);

            foreach ($rows as $order) {
                fputcsv($out, [
                    $order->order_no,
                    $order->jobCard?->job_card_no,
                    $order->jobCard?->customerVehicle?->registration_no,
                    $order->technician?->name,
                    $order->advisor?->name,
                    $order->bay?->name,
                    $order->priority?->name,
                    VehicleInspectionOrder::statuses()[$order->status] ?? $order->status,
                    $order->assigned_at?->format('Y-m-d H:i'),
                    $order->started_at?->format('Y-m-d H:i'),
                    $order->ended_at?->format('Y-m-d H:i'),
                    $this->tat($order) ?? '',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $paginator = $this->baseQuery()->paginate(25);

        return view('inspection-order-history::index', [
            'paginator' => $paginator,
            'tat' => fn (VehicleInspectionOrder $o) => $this->tat($o),
        ]);
    }
}
