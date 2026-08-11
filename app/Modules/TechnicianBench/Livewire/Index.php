<?php

namespace App\Modules\TechnicianBench\Livewire;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\VehicleInspectionOrder\Concerns\ManagesScopeTimers;
use App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrderScope;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The floor screen. A technician picks themselves once, then works from a list
 * of their own task lines — start, pause, complete — without opening the
 * advisor's full work-order form.
 *
 * The technician is chosen on-screen rather than taken from the logged-in user
 * because there is no users→employees link; the bench is a shared terminal.
 */
#[Layout('layouts.app')]
#[Title('Technician Bench')]
class Index extends Component
{
    use ManagesScopeTimers;

    #[Url(as: 'tech')]
    public ?int $technicianId = null;

    /** Hide lines already finished — the usual view when working a shift. */
    #[Url(as: 'open')]
    public bool $openOnly = true;

    #[Computed]
    public function technicians()
    {
        return EmployeeMaster::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * The picked technician's task lines, newest order first.
     *
     * @return Collection<int, VehicleInspectionOrderScope>
     */
    #[Computed]
    public function tasks(): Collection
    {
        if (! $this->technicianId) {
            return collect();
        }

        return VehicleInspectionOrderScope::query()
            ->with([
                'order:id,order_no,status,job_card_id',
                'order.jobCard:id,job_card_no',
                'labour:id,name',
                'requestedRepair:id,name',
                'jobDescription:id,name',
                'servicePackage:id,name',
            ])
            ->where('technician_id', $this->technicianId)
            ->when($this->openOnly, fn ($q) => $q->where('work_status', '!=', VehicleInspectionOrderScope::STATUS_COMPLETED))
            ->orderByDesc('vehicle_inspection_order_id')
            ->orderBy('sequence_no')
            ->get();
    }

    /** The one line currently running for this technician, if any. */
    #[Computed]
    public function runningTask(): ?VehicleInspectionOrderScope
    {
        if (! $this->technicianId) {
            return null;
        }

        return VehicleInspectionOrderScope::query()
            ->with(['order:id,order_no'])
            ->where('technician_id', $this->technicianId)
            ->where('work_status', VehicleInspectionOrderScope::STATUS_IN_PROGRESS)
            ->first();
    }

    public function start(int $scopeId): void
    {
        $this->authorize('technician_bench.update');

        if (! $this->technicianId) {
            Flux::toast(text: 'Pick who you are first.', variant: 'warning');

            return;
        }

        $scope = $this->ownedScope($scopeId);
        if (! $scope) {
            return;
        }

        $this->startScopeTimer($scope, $this->technicianId);
        $this->refreshLists();
        Flux::toast(text: 'Timer started.', variant: 'success');
    }

    public function pause(int $scopeId): void
    {
        $this->authorize('technician_bench.update');

        $scope = $this->ownedScope($scopeId);
        if (! $scope) {
            return;
        }

        $this->accumulateAndStop($scope, VehicleInspectionOrderScope::STATUS_PAUSED);
        $this->refreshLists();
        Flux::toast(text: 'Timer paused.');
    }

    public function complete(int $scopeId): void
    {
        $this->authorize('technician_bench.update');

        $scope = $this->ownedScope($scopeId);
        if (! $scope) {
            return;
        }

        $this->completeScopeTimer($scope);
        $this->refreshLists();
        Flux::toast(text: 'Task completed.', variant: 'success');
    }

    /**
     * Load a scope only if it belongs to the technician on screen — the id
     * arrives from the browser, so it is never trusted on its own.
     */
    protected function ownedScope(int $scopeId): ?VehicleInspectionOrderScope
    {
        $scope = VehicleInspectionOrderScope::query()
            ->whereKey($scopeId)
            ->where('technician_id', $this->technicianId)
            ->first();

        if (! $scope) {
            Flux::toast(text: 'That task is not assigned to you.', variant: 'warning');
        }

        return $scope;
    }

    protected function refreshLists(): void
    {
        unset($this->tasks, $this->runningTask);
    }

    public function updatedTechnicianId(): void
    {
        $this->refreshLists();
    }

    /** @return array<string, string> */
    #[Computed]
    public function workStatuses(): array
    {
        return VehicleInspectionOrderScope::workStatuses();
    }

    public function render()
    {
        return view('technician-bench::index');
    }
}
