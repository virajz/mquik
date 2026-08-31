<?php

namespace App\Modules\TechnicianBench\Livewire;

use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\VehicleInspectionOrder\Concerns\ManagesScopeTimers;
use App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrder;
use App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrderPause;
use App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrderScope;
use App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrderScopePhoto;
use App\Modules\VehicleInspectionOrder\Support\InspectionOrderStatus;
use App\Modules\WorkOrderHoldReasonMaster\Models\WorkOrderHoldReasonMaster;
use App\Support\ActingEmployee;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

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
    use WithFileUploads;

    #[Url(as: 'tech')]
    public ?int $technicianId = null;

    /** Hide lines already finished — the usual view when working a shift. */
    #[Url(as: 'open')]
    public bool $openOnly = true;

    /** Files staged against a scope, keyed by "scopeId.stage". */
    public array $scopePhotoFiles = [];

    /** Reason chosen when pausing — the technician says why, not the advisor. */
    public ?int $pauseReasonId = null;

    public ?int $pausingScopeId = null;

    /** Completion type chosen when finishing a line. */
    public ?string $completionType = null;

    public ?int $completingScopeId = null;

    public function mount(): void
    {
        $this->technicianId ??= ActingEmployee::id();
    }

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
                'photos',
            ])
            // Work reaches a technician through the order they are assigned to;
            // a line may still name its own from before that changed.
            ->where(fn ($q) => $q
                ->where('technician_id', $this->technicianId)
                ->orWhereHas('order', fn ($o) => $o->where('technician_id', $this->technicianId)))
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

        // Close whichever pause this resumes, so the gap has both ends.
        VehicleInspectionOrderPause::where('vehicle_inspection_order_scope_id', $scope->id)
            ->whereNull('resumed_at')
            ->update(['resumed_at' => now()]);

        InspectionOrderStatus::refresh($scope->order);

        $this->refreshLists();
        Flux::toast(text: 'Timer started.', variant: 'success');
    }

    /** Opens the reason picker — a pause without a reason explains nothing later. */
    public function askPauseReason(int $scopeId): void
    {
        $this->pausingScopeId = $scopeId;
        $this->pauseReasonId = null;
        $this->resetErrorBag('pauseReasonId');

        Flux::modal('technician-pause-reason')->show();
    }

    public function pause(): void
    {
        $this->authorize('technician_bench.update');

        $this->validate(
            ['pauseReasonId' => ['required', 'integer', Rule::exists('work_order_hold_reasons', 'id')->where('is_active', true)]],
            attributes: ['pauseReasonId' => 'reason'],
        );

        $scope = $this->ownedScope((int) $this->pausingScopeId);

        if (! $scope) {
            return;
        }

        $this->accumulateAndStop($scope, VehicleInspectionOrderScope::STATUS_PAUSED);

        // The pause itself is a record: when it started, why, and whose it was.
        VehicleInspectionOrderPause::create([
            'vehicle_inspection_order_id' => $scope->vehicle_inspection_order_id,
            'vehicle_inspection_order_scope_id' => $scope->id,
            'hold_reason_id' => $this->pauseReasonId,
            'paused_at' => now(),
            'paused_by_id' => $this->technicianId,
        ]);

        InspectionOrderStatus::refresh($scope->order);

        $this->pausingScopeId = null;
        $this->refreshLists();

        Flux::modal('technician-pause-reason')->close();
        Flux::toast(text: 'Timer paused.');
    }

    public function askCompletionType(int $scopeId): void
    {
        $this->completingScopeId = $scopeId;
        $this->completionType = null;
        $this->resetErrorBag('completionType');

        Flux::modal('technician-completion-type')->show();
    }

    public function complete(): void
    {
        $this->authorize('technician_bench.update');

        $this->validate(
            ['completionType' => ['required', Rule::in(array_keys(VehicleInspectionOrder::completionTypes()))]],
            attributes: ['completionType' => 'completion type'],
        );

        $scope = $this->ownedScope((int) $this->completingScopeId);

        if (! $scope) {
            return;
        }

        $this->completeScopeTimer($scope);
        $scope->forceFill(['completion_type' => $this->completionType])->save();

        InspectionOrderStatus::refresh($scope->order);

        $this->completingScopeId = null;
        $this->refreshLists();

        Flux::modal('technician-completion-type')->close();
        Flux::toast(text: 'Task completed.', variant: 'success');
    }

    /**
     * Evidence for the work itself — several before and after shots per line,
     * because one photo does not show a clutch.
     */
    public function uploadScopePhotos(int $scopeId, string $stage): void
    {
        $this->authorize('technician_bench.update');

        $scope = $this->ownedScope($scopeId);
        $files = $this->scopePhotoFiles[$scopeId][$stage] ?? [];

        if (! $scope || $files === []) {
            return;
        }

        $this->validate([
            "scopePhotoFiles.$scopeId.$stage.*" => ['image', 'max:8192'],
        ], attributes: ["scopePhotoFiles.$scopeId.$stage.*" => 'photo']);

        $seq = $scope->photos()->where('stage', $stage)->max('sequence_no') ?? 0;

        foreach ($files as $file) {
            $scope->photos()->create([
                'stage' => $stage,
                'path' => $file->store("vehicle-inspection-orders/{$scope->vehicle_inspection_order_id}/scopes/{$scope->id}", 'public'),
                'original_name' => $file->getClientOriginalName(),
                'size_bytes' => $file->getSize(),
                'sequence_no' => ++$seq,
            ]);
        }

        unset($this->scopePhotoFiles[$scopeId][$stage]);
        $this->refreshLists();

        Flux::toast(text: count($files).' '.$stage.' photo(s) saved.', variant: 'success');
    }

    public function removeScopePhoto(int $photoId): void
    {
        $this->authorize('technician_bench.update');

        $photo = VehicleInspectionOrderScopePhoto::find($photoId);

        if (! $photo || ! $this->ownedScope($photo->vehicle_inspection_order_scope_id)) {
            return;
        }

        Storage::disk('public')->delete($photo->path);
        $photo->delete();

        $this->refreshLists();
    }

    /** @return Collection<int, mixed> */
    #[Computed]
    public function pauseReasons()
    {
        return WorkOrderHoldReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Load a scope only if it belongs to the technician on screen — the id
     * arrives from the browser, so it is never trusted on its own.
     */
    protected function ownedScope(int $scopeId): ?VehicleInspectionOrderScope
    {
        $scope = VehicleInspectionOrderScope::query()
            ->whereKey($scopeId)
            ->where(fn ($q) => $q
                ->where('technician_id', $this->technicianId)
                ->orWhereHas('order', fn ($o) => $o->where('technician_id', $this->technicianId)))
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
        ActingEmployee::set($this->technicianId);
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
