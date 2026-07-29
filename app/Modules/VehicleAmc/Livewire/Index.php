<?php

namespace App\Modules\VehicleAmc\Livewire;

use App\Modules\VehicleAmc\Models\VehicleAmc;
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
#[Title('Vehicle AMC')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'package')]
    public string $packageFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'amc_no', 'status', 'end_date', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPackageFilter(): void
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
        $this->authorize('vehicle_amc.delete');
        VehicleAmc::findOrFail($id)->delete();
        Flux::toast(text: 'AMC #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'packageFilter']);
        $this->resetPage();
    }

    /**
     * @return array{active:int, renewals_due:int, services_availed:int, revenue:float}
     */
    protected function kpis(): array
    {
        $today = Carbon::today();

        return [
            'active' => VehicleAmc::query()->where('status', VehicleAmc::STATUS_ACTIVE)->count(),
            'renewals_due' => VehicleAmc::query()->where('status', VehicleAmc::STATUS_ACTIVE)
                ->whereBetween('end_date', [$today, $today->copy()->addDays(7)])->count(),
            'services_availed' => (int) VehicleAmc::query()->sum('services_availed'),
            'revenue' => (float) VehicleAmc::query()->where('payment_status', 'fully_paid')->sum('amount'),
        ];
    }

    /**
     * @return Builder<VehicleAmc>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return VehicleAmc::query()
            ->with(['customer:id,first_name,last_name', 'customerVehicle:id,registration_no'])
            ->withCount('items')
            ->when($search !== '', fn ($q) => $q->whereLike('amc_no', '%'.$search.'%', caseSensitive: false))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->packageFilter !== 'all', fn ($q) => $q->where('amc_package', $this->packageFilter));
    }

    /** Stream the AMC Service Due Follow-Up Report CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('vehicle_amc.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'vehicle-amc-report-'.Carbon::now()->format('Ymd-His').'.csv';
        $packages = VehicleAmc::packages();
        $statuses = VehicleAmc::statuses();
        $payments = VehicleAmc::paymentStatuses();

        return response()->streamDownload(function () use ($rows, $packages, $statuses, $payments) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['AMC No', 'Customer', 'Vehicle', 'Package', 'Start', 'End', 'Services (Used/Limit)', 'Amount', 'Payment', 'Status']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->amc_no,
                    trim(($r->customer?->first_name ?? '').' '.($r->customer?->last_name ?? '')),
                    $r->customerVehicle?->registration_no,
                    $packages[$r->amc_package] ?? '',
                    $r->start_date?->format('Y-m-d'),
                    $r->end_date?->format('Y-m-d'),
                    $r->services_availed.' / '.($r->services_limit ?? '—'),
                    $r->amount !== null ? number_format((float) $r->amount, 2, '.', '') : '',
                    $payments[$r->payment_status] ?? '',
                    $statuses[$r->status] ?? $r->status,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        return view('vehicle-amc::index', [
            'rows' => $rows,
            'statuses' => VehicleAmc::statuses(),
            'packages' => VehicleAmc::packages(),
            'kpis' => $this->kpis(),
            'today' => Carbon::today(),
        ]);
    }
}
