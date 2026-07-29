<?php

namespace App\Modules\VehicleMovement\Livewire;

use App\Modules\VehicleMovement\Models\VehicleMovement;
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
#[Title('Vehicle Inward / Outward')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'type')]
    public string $typeFilter = 'all';

    #[Url(as: 'job')]
    public string $jobFilter = 'all';

    #[Url(as: 'sort')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    protected array $sortable = ['id', 'movement_no', 'movement_type', 'entry_at', 'created_at'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingJobFilter(): void
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
        $this->authorize('vehicle_movement.delete');
        VehicleMovement::findOrFail($id)->delete();
        Flux::toast(text: 'Movement #'.$id.' deleted.', variant: 'success');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'typeFilter', 'jobFilter']);
        $this->resetPage();
    }

    /**
     * @return array{inward_today:int, trial_today:int, outward_today:int}
     */
    protected function kpis(): array
    {
        $today = Carbon::today();

        return [
            'inward_today' => VehicleMovement::query()
                ->where('movement_type', VehicleMovement::TYPE_INWARD)
                ->whereDate('created_at', $today)->count(),
            'trial_today' => VehicleMovement::query()
                ->where('movement_type', VehicleMovement::TYPE_OUTWARD)
                ->where('outward_type', 'trial_run')
                ->whereDate('created_at', $today)->count(),
            'outward_today' => VehicleMovement::query()
                ->where('movement_type', VehicleMovement::TYPE_OUTWARD)
                ->whereDate('created_at', $today)->count(),
        ];
    }

    /**
     * @return Builder<VehicleMovement>
     */
    protected function baseQuery(): Builder
    {
        $search = trim($this->search);

        return VehicleMovement::query()
            ->with(['customerVehicle:id,registration_no', 'jobCard:id,job_card_no'])
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->whereLike('movement_no', '%'.$search.'%', caseSensitive: false)
                    ->orWhereLike('number_plate', '%'.$search.'%', caseSensitive: false);
            }))
            ->when($this->typeFilter !== 'all', fn ($q) => $q->where('movement_type', $this->typeFilter))
            ->when($this->jobFilter !== 'all', fn ($q) => $q->where('job_status', $this->jobFilter));
    }

    /** Stream the Inward / Outward Register CSV. */
    public function download(): StreamedResponse
    {
        $this->authorize('vehicle_movement.view');

        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->get();
        $filename = 'inward-outward-register-'.Carbon::now()->format('Ymd-His').'.csv';
        $types = VehicleMovement::movementTypes();
        $outwardTypes = VehicleMovement::outwardTypes();
        $jobStatuses = VehicleMovement::jobStatuses();

        return response()->streamDownload(function () use ($rows, $types, $outwardTypes, $jobStatuses) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Movement No', 'Type', 'Vehicle', 'Number Plate', 'Outward Purpose', 'Job Card', 'Entry', 'Exit', 'TAT', 'Job Status']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->movement_no,
                    $types[$r->movement_type] ?? '',
                    $r->customerVehicle?->registration_no,
                    $r->number_plate,
                    $outwardTypes[$r->outward_type] ?? '',
                    $r->jobCard?->job_card_no,
                    $r->entry_at?->format('Y-m-d H:i'),
                    $r->exit_at?->format('Y-m-d H:i'),
                    $r->tatLabel() ?? '',
                    $jobStatuses[$r->job_status] ?? $r->job_status,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $rows = $this->baseQuery()->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        return view('vehicle-movement::index', [
            'rows' => $rows,
            'movementTypes' => VehicleMovement::movementTypes(),
            'jobStatuses' => VehicleMovement::jobStatuses(),
            'kpis' => $this->kpis(),
        ]);
    }
}
