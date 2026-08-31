<?php

namespace App\Modules\TechnicianBench\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\SpareMaster\Models\SpareMaster;
use App\Modules\TechnicianFinding\Models\TechnicianFinding;
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
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * The technician's own page for one work order.
 *
 * Deliberately not the advisor's form: the vehicle is what a technician needs
 * (the customer is the advisor's business), the todo list is what they have to
 * do, and the findings box is where the job's real value lands — the parts that
 * need replacing and the labour that has to be performed.
 */
#[Layout('layouts.app')]
#[Title('Technician Response')]
class Response extends Component
{
    use ManagesScopeTimers;
    use SearchesPickerOptions;
    use WithFileUploads;

    public VehicleInspectionOrder $order;

    public ?int $technicianId = null;

    public ?string $technicianRemark = null;

    /** Files staged against a scope, keyed by "scopeId.stage". */
    public array $scopePhotoFiles = [];

    public ?int $pauseReasonId = null;

    public ?int $pausingScopeId = null;

    public ?string $completionType = null;

    public ?int $completingScopeId = null;

    // ---- finding form ----
    public string $findingType = TechnicianFinding::TYPE_SPARE;

    public ?int $findingSpareId = null;

    public ?int $findingLabourId = null;

    public string $findingDescription = '';

    public float $findingQuantity = 1;

    public ?float $findingEstimatedAmount = null;

    public ?string $findingRecommendation = null;

    public ?int $editingFindingId = null;

    public string $spareSearch = '';

    public string $labourSearch = '';

    public function mount(VehicleInspectionOrder $vehicleInspectionOrder): void
    {
        $this->order = $vehicleInspectionOrder;
        $this->technicianId = ActingEmployee::id() ?? $vehicleInspectionOrder->technician_id;
        $this->technicianRemark = $vehicleInspectionOrder->technician_remark;
    }

    // -----------------------------------------------------------------
    // Todo list
    // -----------------------------------------------------------------

    /** @return Collection<int, VehicleInspectionOrderScope> */
    #[Computed]
    public function todo()
    {
        return VehicleInspectionOrderScope::query()
            ->where('vehicle_inspection_order_id', $this->order->id)
            ->with(['labour:id,name', 'requestedRepair:id,name', 'jobDescription:id,name',
                'servicePackage:id,name', 'complaintType:id,name', 'photos'])
            ->orderBy('sequence_no')
            ->get();
    }

    /**
     * Checklists on this order, by template name — the technician is told which
     * inspection they are working through, not just a flat list of checkpoints.
     *
     * @return Collection<string, Collection<int, mixed>>
     */
    #[Computed]
    public function checklists()
    {
        return $this->order->items()
            ->with('template:id,name')
            ->orderBy('sequence_no')
            ->get()
            ->groupBy(fn ($item) => $item->template?->name ?? 'Unnamed checklist');
    }

    // -----------------------------------------------------------------
    // Timers — same rules as the bench
    // -----------------------------------------------------------------

    public function start(int $scopeId): void
    {
        $this->authorize('technician_bench.update');

        if (! $this->technicianId) {
            Flux::toast(text: 'Pick who you are first.', variant: 'warning');

            return;
        }

        $scope = $this->orderScope($scopeId);
        if (! $scope) {
            return;
        }

        $this->startScopeTimer($scope, $this->technicianId);

        VehicleInspectionOrderPause::where('vehicle_inspection_order_scope_id', $scope->id)
            ->whereNull('resumed_at')
            ->update(['resumed_at' => now()]);

        $this->refreshOrder();
        Flux::toast(text: 'Timer started.', variant: 'success');
    }

    public function askPauseReason(int $scopeId): void
    {
        $this->pausingScopeId = $scopeId;
        $this->pauseReasonId = null;
        $this->resetErrorBag('pauseReasonId');

        Flux::modal('response-pause-reason')->show();
    }

    public function pause(): void
    {
        $this->authorize('technician_bench.update');

        $this->validate(
            ['pauseReasonId' => ['required', 'integer', Rule::exists('work_order_hold_reasons', 'id')->where('is_active', true)]],
            attributes: ['pauseReasonId' => 'reason'],
        );

        $scope = $this->orderScope((int) $this->pausingScopeId);
        if (! $scope) {
            return;
        }

        $this->accumulateAndStop($scope, VehicleInspectionOrderScope::STATUS_PAUSED);

        VehicleInspectionOrderPause::create([
            'vehicle_inspection_order_id' => $scope->vehicle_inspection_order_id,
            'vehicle_inspection_order_scope_id' => $scope->id,
            'hold_reason_id' => $this->pauseReasonId,
            'paused_at' => now(),
            'paused_by_id' => $this->technicianId,
        ]);

        $this->pausingScopeId = null;
        $this->refreshOrder();

        Flux::modal('response-pause-reason')->close();
        Flux::toast(text: 'Timer paused.');
    }

    public function askCompletionType(int $scopeId): void
    {
        $this->completingScopeId = $scopeId;
        $this->completionType = null;
        $this->resetErrorBag('completionType');

        Flux::modal('response-completion-type')->show();
    }

    public function complete(): void
    {
        $this->authorize('technician_bench.update');

        $this->validate(
            ['completionType' => ['required', Rule::in(array_keys(VehicleInspectionOrder::completionTypes()))]],
            attributes: ['completionType' => 'completion type'],
        );

        $scope = $this->orderScope((int) $this->completingScopeId);
        if (! $scope) {
            return;
        }

        $this->completeScopeTimer($scope);
        $scope->forceFill(['completion_type' => $this->completionType])->save();

        $this->completingScopeId = null;
        $this->refreshOrder();

        Flux::modal('response-completion-type')->close();
        Flux::toast(text: 'Task completed.', variant: 'success');
    }

    // -----------------------------------------------------------------
    // Evidence
    // -----------------------------------------------------------------

    public function uploadScopePhotos(int $scopeId, string $stage): void
    {
        $this->authorize('technician_bench.update');

        $scope = $this->orderScope($scopeId);
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
        $this->refreshOrder();

        Flux::toast(text: count($files).' '.$stage.' photo(s) saved.', variant: 'success');
    }

    public function removeScopePhoto(int $photoId): void
    {
        $this->authorize('technician_bench.update');

        $photo = VehicleInspectionOrderScopePhoto::find($photoId);

        if (! $photo || ! $this->orderScope($photo->vehicle_inspection_order_scope_id)) {
            return;
        }

        Storage::disk('public')->delete($photo->path);
        $photo->delete();

        $this->refreshOrder();
    }

    // -----------------------------------------------------------------
    // Findings — parts to replace and labour to perform
    // -----------------------------------------------------------------

    /** @return Collection<int, TechnicianFinding> */
    #[Computed]
    public function findings()
    {
        return TechnicianFinding::query()
            ->where('vehicle_inspection_order_id', $this->order->id)
            ->with(['spare:id,name,spare_code', 'labour:id,name,labour_code', 'reportedBy:id,name'])
            ->latest('id')
            ->get();
    }

    public function newFinding(string $type = TechnicianFinding::TYPE_SPARE): void
    {
        $this->resetFindingForm();
        $this->findingType = $type === TechnicianFinding::TYPE_LABOUR
            ? TechnicianFinding::TYPE_LABOUR
            : TechnicianFinding::TYPE_SPARE;

        Flux::modal('response-finding')->show();
    }

    public function editFinding(int $id): void
    {
        $finding = $this->ownedFinding($id);
        if (! $finding) {
            return;
        }

        $this->resetFindingForm();
        $this->editingFindingId = $finding->id;
        $this->findingType = $finding->finding_type;
        $this->findingSpareId = $finding->spare_id;
        $this->findingLabourId = $finding->labour_id;
        $this->findingDescription = (string) $finding->description;
        $this->findingQuantity = (float) $finding->quantity;
        $this->findingEstimatedAmount = $finding->estimated_amount !== null ? (float) $finding->estimated_amount : null;
        $this->findingRecommendation = $finding->recommendation;

        Flux::modal('response-finding')->show();
    }

    public function saveFinding(): void
    {
        $this->authorize('technician_bench.update');

        $isSpare = $this->findingType === TechnicianFinding::TYPE_SPARE;

        $this->validate([
            'findingType' => ['required', Rule::in([TechnicianFinding::TYPE_SPARE, TechnicianFinding::TYPE_LABOUR])],
            'findingSpareId' => [$isSpare ? 'required' : 'nullable', 'integer', 'exists:spares,id'],
            'findingLabourId' => [$isSpare ? 'nullable' : 'required', 'integer', 'exists:labours,id'],
            'findingDescription' => ['required', 'string', 'max:500'],
            'findingQuantity' => ['required', 'numeric', 'min:0.01'],
            'findingEstimatedAmount' => ['nullable', 'numeric', 'min:0'],
            'findingRecommendation' => ['nullable', 'string', 'max:500'],
        ], attributes: [
            'findingSpareId' => 'part',
            'findingLabourId' => 'labour',
            'findingDescription' => 'description',
            'findingQuantity' => 'quantity',
            'findingEstimatedAmount' => 'estimated amount',
        ]);

        $payload = [
            'job_card_id' => $this->order->job_card_id,
            'vehicle_inspection_order_id' => $this->order->id,
            'finding_type' => $this->findingType,
            'spare_id' => $isSpare ? $this->findingSpareId : null,
            'labour_id' => $isSpare ? null : $this->findingLabourId,
            'reported_by_id' => $this->technicianId,
            'description' => mb_strtoupper($this->findingDescription),
            'quantity' => $this->findingQuantity,
            'estimated_amount' => $this->findingEstimatedAmount,
            'recommendation' => $this->findingRecommendation ? mb_strtoupper($this->findingRecommendation) : null,
        ];

        if ($this->editingFindingId && ($existing = $this->ownedFinding($this->editingFindingId))) {
            // Status is the advisor's to change; editing the detail must not
            // quietly un-approve or re-approve the finding.
            $existing->update($payload);
        } else {
            TechnicianFinding::create($payload + ['status' => TechnicianFinding::STATUS_RECOMMENDED]);
        }

        $this->resetFindingForm();
        unset($this->findings);

        Flux::modal('response-finding')->close();
        Flux::toast(text: 'Finding recorded — the advisor is notified.', variant: 'success');
    }

    public function deleteFinding(int $id): void
    {
        $this->authorize('technician_bench.update');

        $finding = $this->ownedFinding($id);

        if (! $finding) {
            return;
        }

        // Once the advisor has acted on it, it is no longer the technician's to pull back.
        if ($finding->status !== TechnicianFinding::STATUS_RECOMMENDED) {
            Flux::toast(text: 'That finding has already been actioned by the advisor.', variant: 'warning');

            return;
        }

        $finding->delete();
        unset($this->findings);

        Flux::toast(text: 'Finding removed.');
    }

    protected function resetFindingForm(): void
    {
        $this->reset([
            'findingSpareId', 'findingLabourId', 'findingDescription', 'findingQuantity',
            'findingEstimatedAmount', 'findingRecommendation', 'editingFindingId',
            'spareSearch', 'labourSearch',
        ]);
        $this->findingQuantity = 1;
        $this->resetErrorBag();
    }

    /** A part carries its own description and price unless the technician says otherwise. */
    public function updatedFindingSpareId($value): void
    {
        $spare = $value ? SpareMaster::find((int) $value) : null;

        if ($spare && $this->findingDescription === '') {
            $this->findingDescription = 'REPLACE '.$spare->name;
        }

        if ($spare && $this->findingEstimatedAmount === null) {
            $this->findingEstimatedAmount = (float) $spare->rate_before_tax;
        }
    }

    public function updatedFindingLabourId($value): void
    {
        $labour = $value ? LabourMaster::find((int) $value) : null;

        if ($labour && $this->findingDescription === '') {
            $this->findingDescription = (string) $labour->name;
        }

        if ($labour && $this->findingEstimatedAmount === null) {
            $this->findingEstimatedAmount = (float) $labour->rate_before_tax;
        }
    }

    // -----------------------------------------------------------------
    // Remark
    // -----------------------------------------------------------------

    public function saveRemark(): void
    {
        $this->authorize('technician_bench.update');

        $this->validate(
            ['technicianRemark' => ['nullable', 'string', 'max:2000']],
            attributes: ['technicianRemark' => 'remark'],
        );

        $this->order->forceFill([
            'technician_remark' => $this->technicianRemark ? mb_strtoupper($this->technicianRemark) : null,
        ])->save();

        $this->technicianRemark = $this->order->technician_remark;

        Flux::toast(text: 'Remark saved.', variant: 'success');
    }

    // -----------------------------------------------------------------
    // Pickers
    // -----------------------------------------------------------------

    #[Computed]
    public function spares()
    {
        return $this->pickerOptions(
            query: SpareMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'spare_code'],
            term: $this->spareSearch,
            selected: $this->findingSpareId,
            columns: ['id', 'name', 'spare_code'],
            limit: 30,
        );
    }

    #[Computed]
    public function labours()
    {
        return $this->pickerOptions(
            query: LabourMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'labour_code'],
            term: $this->labourSearch,
            selected: $this->findingLabourId,
            columns: ['id', 'name', 'labour_code'],
            limit: 30,
        );
    }

    #[Computed]
    public function pauseReasons()
    {
        return WorkOrderHoldReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /** Hands-on seconds across every line — the order's own TAT. */
    #[Computed]
    public function totalSeconds(): int
    {
        return (int) $this->todo->sum(fn (VehicleInspectionOrderScope $s) => $s->elapsedSeconds());
    }

    /** A line is only workable from here if it belongs to this order. */
    protected function orderScope(int $scopeId): ?VehicleInspectionOrderScope
    {
        $scope = VehicleInspectionOrderScope::query()
            ->whereKey($scopeId)
            ->where('vehicle_inspection_order_id', $this->order->id)
            ->first();

        if (! $scope) {
            Flux::toast(text: 'That task is not on this work order.', variant: 'warning');
        }

        return $scope;
    }

    protected function ownedFinding(int $id): ?TechnicianFinding
    {
        return TechnicianFinding::query()
            ->whereKey($id)
            ->where('vehicle_inspection_order_id', $this->order->id)
            ->first();
    }

    protected function refreshOrder(): void
    {
        $this->order->refresh();
        InspectionOrderStatus::refresh($this->order);
        $this->order->refresh();
        unset($this->todo, $this->totalSeconds);
    }

    public function render()
    {
        return view('technician-bench::response');
    }
}
