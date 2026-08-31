<?php

namespace App\Modules\VehicleInspectionOrder\Livewire;

use App\Modules\BayMaster\Models\BayMaster;
use App\Modules\ComplaintTypeMaster\Models\ComplaintTypeMaster;
use App\Modules\DelayReasonMaster\Models\DelayReasonMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\JobDescriptionMaster\Models\JobDescriptionMaster;
use App\Modules\LabourMaster\Models\LabourMaster;
use App\Modules\PhotoTypeMaster\Models\PhotoTypeMaster;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\RequestedRepairMaster\Models\RequestedRepairMaster;
use App\Modules\ReworkReasonMaster\Models\ReworkReasonMaster;
use App\Modules\ServicePackageMaster\Models\ServicePackageMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\TechnicianFinding\Models\TechnicianFinding;
use App\Modules\VehicleInspectionOrder\Concerns\ManagesScopeTimers;
use App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrder;
use App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrderScope;
use App\Modules\VehicleInspectionOrder\Support\InspectionOrderStatus;
use App\Modules\WorkOrderHoldReasonMaster\Models\WorkOrderHoldReasonMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use App\Support\ChildRows;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Vehicle Inspection Order')]
class Edit extends Component
{
    use ManagesScopeTimers;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $order_no = null;

    public ?int $job_card_id = null;

    public ?int $department_id = null;

    public ?int $service_type_id = null;

    public ?int $advisor_id = null;

    public ?int $technician_id = null;

    public ?int $bay_id = null;

    /** When this work order was raised — the advisor's statement, not a guess. */
    public string $ordered_date = '';

    public string $ordered_time = '';

    public ?int $inspection_template_id = null;

    public ?int $priority_id = null;

    public string $status = VehicleInspectionOrder::STATUS_ASSIGNMENT_PENDING;

    public ?string $completion_type = null;

    public ?int $hold_reason_id = null;

    public ?int $rework_reason_id = null;

    public ?int $delay_reason_id = null;

    public ?string $notes = null;

    #[Url(as: 'tab')]
    public string $activeTab = 'details';

    /** ?int — passed via ?from-job-card=ID for the JobCard → VIO handoff. */
    #[Url(as: 'from-job-card')]
    public ?int $fromJobCard = null;

    /** @var list<array{id: ?int, inspection_item_id: ?int, inspection_item_group_id: ?int, label: string, group_name: ?string, result: string, notes: ?string, sequence_no: int, before_photo_path: ?string, after_photo_path: ?string}> */
    public array $items = [];

    /** @var array<int, TemporaryUploadedFile> keyed by item index */
    public array $itemBeforeFiles = [];

    /** @var array<int, TemporaryUploadedFile> keyed by item index */
    public array $itemAfterFiles = [];

    /** @var list<array{id: ?int, hold_reason_id: ?int, paused_date: ?string, paused_time: ?string, resumed_date: ?string, resumed_time: ?string, notes: ?string}> */
    public array $pauses = [];

    /** @var array<int, array{id:?int, complaint_type_id:?int, job_description_id:?int, service_package_id:?int, is_additional:bool, description:string}> */
    public array $workScopes = [];

    /** @var array<int, array{id:?int, photo_type_id:?int, path:?string, notes:?string}> */
    public array $photos = [];

    /** Freshly uploaded order-level evidence, keyed by photo row index. */
    public array $photoFiles = [];

    public function mount(?VehicleInspectionOrder $vehicleInspectionOrder = null): void
    {
        $now = now();
        $this->ordered_date = $now->format('Y-m-d');
        $this->ordered_time = $now->format('H:i');

        if ($vehicleInspectionOrder && $vehicleInspectionOrder->exists) {
            $this->load($vehicleInspectionOrder);

            return;
        }

        if ($this->fromJobCard) {
            $this->job_card_id = $this->fromJobCard;
            $this->prefillFromJobCard();
        }
    }

    /** Populate department / service / advisor / technician from the picked job card. */
    public function updatedJobCardId(): void
    {
        $this->prefillFromJobCard();

        // A fresh order starts from the card's own to-do list; an order that
        // already has scope keeps whatever someone put on it.
        if ($this->job_card_id && $this->workScopes === []) {
            $this->fetchScopeFromJobCard();
        }
    }

    protected function prefillFromJobCard(): void
    {
        if (! $this->job_card_id) {
            return;
        }
        $jc = JobCard::find($this->job_card_id);
        if (! $jc) {
            return;
        }
        $this->department_id = $jc->workshop_department_id;
        $this->service_type_id = $jc->service_type_id;
        $this->advisor_id = $jc->assigned_advisor_id;
        $this->technician_id = $jc->assigned_technician_id;
    }

    protected function load(VehicleInspectionOrder $order): void
    {
        $order->load(['items.inspectionItem.group', 'pauses', 'workScopes', 'photos']);

        $this->editingId = $order->id;
        $this->order_no = $order->order_no;
        $this->job_card_id = $order->job_card_id;
        $this->department_id = $order->department_id;
        $this->service_type_id = $order->service_type_id;
        $this->advisor_id = $order->advisor_id;
        $this->technician_id = $order->technician_id;
        $this->bay_id = $order->bay_id;
        $this->inspection_template_id = $order->inspection_template_id;
        $this->ordered_date = $order->ordered_at?->format('Y-m-d') ?? $this->ordered_date;
        $this->ordered_time = $order->ordered_at?->format('H:i') ?? $this->ordered_time;
        $this->priority_id = $order->priority_id;
        $this->status = $order->status;
        $this->completion_type = $order->completion_type;
        $this->hold_reason_id = $order->hold_reason_id;
        $this->rework_reason_id = $order->rework_reason_id;
        $this->delay_reason_id = $order->delay_reason_id;
        $this->notes = $order->notes;

        $this->items = $order->items->map(fn ($i) => [
            'inspection_template_id' => $i->inspection_template_id,
            'id' => $i->id,
            'inspection_item_id' => $i->inspection_item_id,
            'inspection_item_group_id' => $i->inspection_item_group_id,
            'label' => $i->label,
            'group_name' => $i->group?->name,
            'result' => $i->result,
            'notes' => $i->notes,
            'sequence_no' => (int) $i->sequence_no,
            'before_photo_path' => $i->before_photo_path,
            'after_photo_path' => $i->after_photo_path,
        ])->all();

        $this->pauses = $order->pauses->map(fn ($p) => [
            'id' => $p->id,
            'hold_reason_id' => $p->hold_reason_id,
            'paused_date' => $p->paused_at?->format('Y-m-d'),
            'paused_time' => $p->paused_at?->format('H:i'),
            'resumed_date' => $p->resumed_at?->format('Y-m-d'),
            'resumed_time' => $p->resumed_at?->format('H:i'),
            'notes' => $p->notes,
        ])->all();

        $this->workScopes = $order->workScopes->map(fn ($s) => [
            'is_chargeable' => (bool) $s->is_chargeable,
            'approved_by_id' => $s->approved_by_id,
            'approved_at' => $s->approved_at?->format('Y-m-d H:i:s'),
            'id' => $s->id,
            'complaint_type_id' => $s->complaint_type_id,
            'job_description_id' => $s->job_description_id,
            'service_package_id' => $s->service_package_id,
            'labour_id' => $s->labour_id,
            'requested_repair_id' => $s->requested_repair_id,
            'technician_id' => $s->technician_id,
            'is_additional' => (bool) $s->is_additional,
            'description' => $s->description,
            'work_status' => $s->work_status ?? 'pending',
            'completion_type' => $s->completion_type,
            'run_started_at' => $s->run_started_at?->getTimestamp(),
            'duration_seconds' => (int) $s->duration_seconds,
        ])->all();

        $this->photos = $order->photos->map(fn ($ph) => [
            'id' => $ph->id,
            'photo_type_id' => $ph->photo_type_id,
            'path' => $ph->path,
            'notes' => $ph->notes,
        ])->all();
    }

    /**
     * On create, snapshot every template item into $items with result=pending.
     */
    /**
     * The technician adds a checklist for what they are actually doing — PMS,
     * tyre, whatever — and one order can carry several. Appended, never
     * replacing, and each item remembers which template it came from.
     */
    public string $addTemplateId = '';

    public function addTemplateItems(): void
    {
        if ($this->addTemplateId === '') {
            Flux::toast(text: 'Pick a checklist to add.', variant: 'warning');

            return;
        }

        $template = InspectionTemplateMaster::with(['items.group'])->find($this->addTemplateId);

        if (! $template) {
            return;
        }

        $already = collect($this->items)->pluck('inspection_template_id')->filter()->contains((int) $template->id);

        if ($already) {
            Flux::toast(text: $template->name.' is already on this order.', variant: 'warning');

            return;
        }

        $seq = count($this->items);

        foreach ($template->items as $tplItem) {
            $this->items[] = [
                'id' => null,
                'inspection_template_id' => $template->id,
                'inspection_item_id' => $tplItem->id,
                'inspection_item_group_id' => $tplItem->inspection_item_group_id,
                'label' => $tplItem->name,
                'group_name' => $tplItem->group?->name,
                'result' => 'pending',
                'notes' => null,
                'sequence_no' => ++$seq,
                'before_photo_path' => null,
                'after_photo_path' => null,
            ];
        }

        $this->addTemplateId = '';

        Flux::toast(text: $template->name.' checklist added.', variant: 'success');
    }

    /** Drop every checkpoint that came from one template. */
    public function removeTemplateItems(int $templateId): void
    {
        $this->items = array_values(array_filter(
            $this->items,
            fn ($item) => (int) ($item['inspection_template_id'] ?? 0) !== $templateId,
        ));
    }

    public function addItem(): void
    {
        $this->items[] = [
            'id' => null,
            'inspection_template_id' => null,
            'inspection_item_id' => null,
            'inspection_item_group_id' => null,
            'label' => '',
            'group_name' => null,
            'result' => 'pending',
            'notes' => null,
            'sequence_no' => count($this->items) + 1,
            'before_photo_path' => null,
            'after_photo_path' => null,
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index], $this->itemBeforeFiles[$index], $this->itemAfterFiles[$index]);
        $this->items = array_values($this->items);
    }

    public function clearItemPhoto(int $index, string $which): void
    {
        $field = $which === 'after' ? 'after_photo_path' : 'before_photo_path';
        if (isset($this->items[$index][$field]) && $this->items[$index][$field]) {
            Storage::disk('public')->delete($this->items[$index][$field]);
        }
        $this->items[$index][$field] = null;
        unset($this->{$which === 'after' ? 'itemAfterFiles' : 'itemBeforeFiles'}[$index]);
    }

    public function addWorkScope(): void
    {
        $this->workScopes[] = [
            'id' => null, 'complaint_type_id' => null, 'job_description_id' => null,
            'service_package_id' => null, 'labour_id' => null, 'requested_repair_id' => null,
            'technician_id' => null, 'is_additional' => false, 'is_chargeable' => false,
            'approved_by_id' => null, 'approved_at' => null, 'description' => '',
            'work_status' => 'pending', 'completion_type' => null, 'run_started_at' => null, 'duration_seconds' => 0,
        ];
    }

    public function removeWorkScope(int $index): void
    {
        unset($this->workScopes[$index]);
        $this->workScopes = array_values($this->workScopes);
    }

    // ---- Per-task work timers (one running task at a time per technician) ----

    public function startScope(int $index): void
    {
        $scope = $this->scopeRow($index);
        if (! $scope) {
            return;
        }

        $technicianId = $scope->technician_id ?: $this->technician_id;
        if (! $technicianId) {
            Flux::toast(text: 'Assign a technician to the order (or this line) before starting.', variant: 'warning');

            return;
        }

        $this->startScopeTimer($scope, (int) $technicianId);

        $this->refreshScope($index, $scope);
        Flux::toast(text: 'Timer started.', variant: 'success');
    }

    public function pauseScope(int $index): void
    {
        $scope = $this->scopeRow($index);
        if (! $scope) {
            return;
        }
        $this->accumulateAndStop($scope, VehicleInspectionOrderScope::STATUS_PAUSED);
        $this->refreshScope($index, $scope);
    }

    public function completeScope(int $index): void
    {
        $scope = $this->scopeRow($index);
        if (! $scope) {
            return;
        }
        $this->completeScopeTimer($scope);
        $this->refreshScope($index, $scope);
        Flux::toast(text: 'Task completed.', variant: 'success');
    }

    /** The persisted scope row for a timer action, or null (with a toast) if unsaved. */
    protected function scopeRow(int $index): ?VehicleInspectionOrderScope
    {
        $id = $this->workScopes[$index]['id'] ?? null;
        if (! $id) {
            Flux::toast(text: 'Save the order first, then start the timer.', variant: 'warning');

            return null;
        }

        return VehicleInspectionOrderScope::find($id);
    }

    /** Push a scope's timing fields back into the local array for display. */
    protected function refreshScope(int $index, VehicleInspectionOrderScope $scope): void
    {
        $fresh = $scope->fresh();
        $this->workScopes[$index]['technician_id'] = $fresh->technician_id;
        $this->workScopes[$index]['work_status'] = $fresh->work_status;
        $this->workScopes[$index]['run_started_at'] = $fresh->run_started_at?->getTimestamp();
        $this->workScopes[$index]['duration_seconds'] = (int) $fresh->duration_seconds;
    }

    public function addPhoto(): void
    {
        $this->photos[] = ['id' => null, 'photo_type_id' => null, 'path' => null, 'notes' => null];
    }

    public function removePhoto(int $index): void
    {
        unset($this->photos[$index], $this->photoFiles[$index]);
        $this->photos = array_values($this->photos);
        $this->photoFiles = array_values($this->photoFiles);
    }

    protected function rules(): array
    {
        return [
            'ordered_date' => ['required', 'date_format:Y-m-d'],
            'ordered_time' => ['required', 'date_format:H:i'],
            'job_card_id' => ['required', 'integer', 'exists:job_cards,id'],
            'department_id' => ['nullable', 'integer', 'exists:workshop_departments,id'],
            'service_type_id' => ['nullable', 'integer', 'exists:service_types,id'],
            'advisor_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'technician_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'bay_id' => ['nullable', 'integer', Rule::exists('bays', 'id')->where('is_active', true)],
            'inspection_template_id' => ['nullable', 'integer', Rule::exists('inspection_templates', 'id')->where('is_active', true)],
            'priority_id' => ['nullable', 'integer', Rule::exists('priorities', 'id')->where('is_active', true)],
            'status' => ['required', Rule::in(array_keys(VehicleInspectionOrder::statuses()))],
            'hold_reason_id' => ['nullable', 'integer', 'exists:work_order_hold_reasons,id'],
            'rework_reason_id' => ['nullable', 'integer', 'exists:rework_reasons,id'],
            'delay_reason_id' => ['nullable', 'integer', 'exists:delay_reasons,id'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['array'],
            'items.*.label' => ['required', 'string', 'max:255'],
            'items.*.inspection_template_id' => ['nullable', 'integer', 'exists:inspection_templates,id'],
            'items.*.result' => ['required', Rule::in(array_keys(VehicleInspectionOrder::results()))],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],

            'workScopes' => ['array'],
            'workScopes.*.complaint_type_id' => ['nullable', 'integer', Rule::exists('complaint_types', 'id')->where('is_active', true)],
            'workScopes.*.job_description_id' => ['nullable', 'integer', Rule::exists('job_descriptions', 'id')->where('is_active', true)],
            'workScopes.*.service_package_id' => ['nullable', 'integer', Rule::exists('service_packages', 'id')->where('is_active', true)],
            'workScopes.*.labour_id' => ['nullable', 'integer', Rule::exists('labours', 'id')->where('is_active', true)],
            'workScopes.*.requested_repair_id' => ['nullable', 'integer', Rule::exists('requested_repairs', 'id')->where('is_active', true)],
            'workScopes.*.technician_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'workScopes.*.is_additional' => ['boolean'],
            'workScopes.*.is_chargeable' => ['boolean'],
            'workScopes.*.description' => ['required', 'string', 'max:500'],
            'photos' => ['array'],
            'photos.*.photo_type_id' => ['nullable', 'integer', Rule::exists('photo_types', 'id')->where('is_active', true)],
            'photos.*.notes' => ['nullable', 'string', 'max:255'],
            'photoFiles.*' => ['nullable', 'image', 'max:8192'],

        ];
    }

    /** Workshop-scoped urgency levels from the shared priority master. */
    #[Computed]
    public function priorities()
    {
        return PriorityMaster::forScope(PriorityMaster::APPLIES_WORKSHOP)->get(['id', 'name']);
    }

    #[Computed]
    public function jobCards()
    {
        return JobCard::query()
            ->with(['customer:id,first_name,last_name', 'customerVehicle:id,registration_no'])
            ->orderByDesc('opened_at')
            ->limit(100)
            ->get(['id', 'job_card_no', 'customer_id', 'customer_vehicle_id', 'opened_at']);
    }

    #[Computed]
    public function templates()
    {
        return InspectionTemplateMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'applies_to']);
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function bays()
    {
        return BayMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function departments()
    {
        return WorkshopDepartmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function serviceTypes()
    {
        return ServiceTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function holdReasons()
    {
        return WorkOrderHoldReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function reworkReasons()
    {
        return ReworkReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function delayReasons()
    {
        return DelayReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /** Complaint types for the work-scope lines. */
    #[Computed]
    public function complaintTypes()
    {
        return ComplaintTypeMaster::query()
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function jobDescriptions()
    {
        return JobDescriptionMaster::query()
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /** Service, Combo and AMC packages all live here, split by their type. */
    #[Computed]
    public function servicePackages()
    {
        return ServicePackageMaster::query()
            ->with('packageType:id,name')
            ->where('is_active', true)->orderBy('name')->get(['id', 'name', 'service_package_type_id']);
    }

    #[Computed]
    public function labours()
    {
        // The department comes along: it is what tells an alignment job from an
        // AC job, and therefore which technician it belongs to.
        return LabourMaster::query()
            ->where('is_active', true)
            ->with('workshopDepartment:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'workshop_department_id']);
    }

    #[Computed]
    public function requestedRepairs()
    {
        return RequestedRepairMaster::query()
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function photoTypes()
    {
        return PhotoTypeMaster::query()
            ->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'group']);
    }

    /** Extra work raised against this order by the Technician Findings module. */
    #[Computed]
    public function findings()
    {
        if (! $this->editingId) {
            return collect();
        }

        return TechnicianFinding::query()
            ->with(['spare:id,name', 'labour:id,name'])
            ->where('vehicle_inspection_order_id', $this->editingId)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * The job card already lists what the customer asked for. Pull those
     * complaints and requested repairs in as the technician's to-do list rather
     * than making someone re-pick them from six dropdowns.
     */
    public function fetchScopeFromJobCard(): void
    {
        if (! $this->job_card_id) {
            Flux::toast(text: 'Pick a job card first.', variant: 'warning');

            return;
        }

        $card = JobCard::with(['complaints.requestedRepair', 'requestedRepairs'])->find($this->job_card_id);

        if (! $card) {
            return;
        }

        // Whatever is already on the order stays; this only adds what is missing.
        // Matched on the repair where there is one and on the text otherwise —
        // older complaints predate the repair link and carry only words.
        $seen = collect($this->workScopes)
            ->map(fn ($s) => [
                'repair' => (int) ($s['requested_repair_id'] ?? 0),
                'text' => mb_strtoupper(trim((string) ($s['description'] ?? ''))),
            ]);

        $isNew = function (?int $repairId, string $text) use (&$seen) {
            $text = mb_strtoupper(trim($text));

            $known = $seen->contains(
                fn ($s) => ($repairId && $s['repair'] === $repairId) || ($text !== '' && $s['text'] === $text),
            );

            if ($known) {
                return false;
            }

            $seen->push(['repair' => (int) $repairId, 'text' => $text]);

            return true;
        };

        $added = 0;

        foreach ($card->complaints as $complaint) {
            // Prefer what the complaint actually says; fall back to the repair's name.
            $text = trim((string) $complaint->description) ?: (string) $complaint->requestedRepair?->name;

            if ($text === '' || ! $isNew($complaint->requested_repair_id, $text)) {
                continue;
            }

            $this->workScopes[] = array_merge($this->blankScope(), [
                'complaint_type_id' => $complaint->complaint_type_id,
                'requested_repair_id' => $complaint->requested_repair_id,
                'description' => $text,
            ]);
            $added++;
        }

        foreach ($card->requestedRepairs as $repair) {
            if (! $isNew($repair->id, $repair->name)) {
                continue;
            }

            $this->workScopes[] = array_merge($this->blankScope(), [
                'requested_repair_id' => $repair->id,
                'description' => $repair->name,
            ]);
            $added++;
        }

        Flux::toast(
            text: $added ? $added.' item(s) pulled from '.$card->job_card_no.'.' : 'Nothing new on that job card.',
            variant: $added ? 'success' : 'warning',
        );
    }

    /** @return array<string, mixed> */
    protected function blankScope(): array
    {
        return [
            'id' => null, 'complaint_type_id' => null, 'job_description_id' => null,
            'service_package_id' => null, 'labour_id' => null, 'requested_repair_id' => null,
            'technician_id' => null, 'is_additional' => false, 'is_chargeable' => false,
            'approved_by_id' => null, 'approved_at' => null, 'description' => '',
            'work_status' => 'pending', 'completion_type' => null, 'run_started_at' => null, 'duration_seconds' => 0,
        ];
    }

    /**
     * An additional check the technician found is theirs to raise; a chargeable
     * one is not theirs to bill. The advisor confirms it, and that confirmation
     * is stamped with who gave it.
     */
    public function approveAdditionalWork(int $index): void
    {
        $this->authorize('vehicle_inspection_order.update');

        if (! isset($this->workScopes[$index])) {
            return;
        }

        $this->workScopes[$index]['approved_by_id'] = $this->advisor_id;
        $this->workScopes[$index]['approved_at'] = now()->format('Y-m-d H:i:s');

        Flux::toast(text: 'Additional work approved — it can now be billed.', variant: 'success');
    }

    public function revokeAdditionalWork(int $index): void
    {
        $this->authorize('vehicle_inspection_order.update');

        if (! isset($this->workScopes[$index])) {
            return;
        }

        $this->workScopes[$index]['approved_by_id'] = null;
        $this->workScopes[$index]['approved_at'] = null;
    }

    #[Computed]
    public function statusExplanation(): string
    {
        return InspectionOrderStatus::explain(
            $this->editingId ? VehicleInspectionOrder::find($this->editingId) : null
        );
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'vehicle_inspection_order.update' : 'vehicle_inspection_order.create');

        $data = $this->validate();
        $items = $data['items'] ?? [];
        // Use the component array (not the validated copy) so each scope keeps its
        // `id` — validate() drops unruled keys, which would delete+recreate rows and
        // wipe their timer state. syncWorkScopes only writes the descriptive columns.
        $workScopes = $this->workScopes;
        $photos = $data['photos'] ?? [];
        $data['ordered_at'] = Carbon::parse($data['ordered_date'].' '.$data['ordered_time'].':00');
        unset($data['items'], $data['pauses'], $data['workScopes'], $data['photos'],
            $data['ordered_date'], $data['ordered_time'],
            $data['itemBeforeFiles'], $data['itemAfterFiles'], $data['photoFiles']);

        // Status is derived from the bench, never typed: keep whatever it is now
        // and let InspectionOrderStatus recompute once the scopes are written.
        $data['status'] = $existing?->status ?? VehicleInspectionOrder::STATUS_ASSIGNMENT_PENDING;
        $data['completion_type'] = $existing?->completion_type;

        $order = DB::transaction(function () use ($data, $items, $workScopes, $photos, $isCreate) {
            if ($isCreate) {
                $row = VehicleInspectionOrder::create($data);
                $this->editingId = $row->id;
                $this->order_no = $row->fresh()->order_no;
            } else {
                $row = VehicleInspectionOrder::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncItems($row, $items);
            $this->syncWorkScopes($row, $workScopes);
            $this->syncPhotos($row, $photos);

            return $row;
        });

        InspectionOrderStatus::refresh($order);
        $this->status = $order->fresh()->status;

        $this->itemBeforeFiles = [];
        $this->itemAfterFiles = [];
        $this->photoFiles = [];

        Flux::toast(
            text: 'Work Order '.$order->fresh()->order_no.($isCreate ? ' created.' : ' updated.'),
            variant: 'success',
        );

        if ($isCreate) {
            return redirect()->route('vehicle-inspection-order.edit', $order->id);
        }

        return redirect()->route('vehicle-inspection-order.index');
    }

    /**
     * @param  array<int, array{label: string, result: string, notes?: string|null}>  $rows
     */
    protected function syncItems(VehicleInspectionOrder $order, array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $i => $row) {
            $local = $this->items[$i] ?? [];

            $beforePath = $local['before_photo_path'] ?? null;
            $afterPath = $local['after_photo_path'] ?? null;

            if (isset($this->itemBeforeFiles[$i]) && $this->itemBeforeFiles[$i] instanceof TemporaryUploadedFile) {
                if ($beforePath) {
                    Storage::disk('public')->delete($beforePath);
                }
                $beforePath = $this->itemBeforeFiles[$i]->store("vehicle-inspection-orders/{$order->id}/items", 'public');
            }
            if (isset($this->itemAfterFiles[$i]) && $this->itemAfterFiles[$i] instanceof TemporaryUploadedFile) {
                if ($afterPath) {
                    Storage::disk('public')->delete($afterPath);
                }
                $afterPath = $this->itemAfterFiles[$i]->store("vehicle-inspection-orders/{$order->id}/items", 'public');
            }

            $payload = [
                'inspection_template_id' => $local['inspection_template_id'] ?? null,
                'inspection_item_id' => $local['inspection_item_id'] ?? null,
                'inspection_item_group_id' => $local['inspection_item_group_id'] ?? null,
                'label' => strtoupper((string) $row['label']),
                'result' => $row['result'],
                'notes' => isset($row['notes']) && is_string($row['notes']) ? strtoupper($row['notes']) : null,
                'sequence_no' => (int) ($local['sequence_no'] ?? $i + 1),
                'before_photo_path' => $beforePath,
                'after_photo_path' => $afterPath,
            ];

            if (! empty($local['id'])) {
                $existing = $order->items()->whereKey($local['id'])->first();
                if ($existing) {
                    $existing->update($payload);
                    $keptIds[] = $existing->id;
                    $this->items[$i]['before_photo_path'] = $beforePath;
                    $this->items[$i]['after_photo_path'] = $afterPath;

                    continue;
                }
            }

            $created = $order->items()->create($payload);
            $keptIds[] = $created->id;
            $this->items[$i]['id'] = $created->id;
            $this->items[$i]['before_photo_path'] = $beforePath;
            $this->items[$i]['after_photo_path'] = $afterPath;
        }

        $order->items()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * @param  array<int, array{hold_reason_id?: int|null, paused_at?: string|null, resumed_at?: string|null, notes?: string|null}>  $rows
     */
    protected function syncWorkScopes(VehicleInspectionOrder $order, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            // Descriptive fields only — timer columns (work_status / run_started_at /
            // duration_seconds / completed_at) are owned by the start/pause/complete
            // actions and must never be overwritten by a form save.
            $keptIds[] = ChildRows::upsert($order->workScopes(), $row['id'] ?? null,
                [
                    'complaint_type_id' => $row['complaint_type_id'] ?: null,
                    'job_description_id' => $row['job_description_id'] ?: null,
                    'service_package_id' => $row['service_package_id'] ?: null,
                    'labour_id' => $row['labour_id'] ?: null,
                    'requested_repair_id' => $row['requested_repair_id'] ?: null,
                    // The order's technician owns the whole order; a scope line
                    // does not carry its own.
                    'technician_id' => null,
                    'is_additional' => (bool) ($row['is_additional'] ?? false),
                    'is_chargeable' => (bool) ($row['is_chargeable'] ?? false),
                    'approved_by_id' => $row['approved_by_id'] ?: null,
                    'approved_at' => $row['approved_at'] ?: null,
                    'description' => strtoupper(trim($row['description'])),
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $order->workScopes()->whereKeyNot($keptIds)->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncPhotos(VehicleInspectionOrder $order, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $row['path'] ?? null;
            $originalName = null;
            $size = null;

            $upload = $this->photoFiles[$i] ?? null;
            if ($upload) {
                $path = $upload->store('inspection-orders/'.$order->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
            }

            // A row with no image is not worth persisting.
            if ($path === null) {
                continue;
            }

            $keptIds[] = ChildRows::upsert($order->photos(), $row['id'] ?? null,
                [
                    'photo_type_id' => $row['photo_type_id'] ?: null,
                    'path' => $path,
                    'original_name' => $originalName,
                    'size_bytes' => $size,
                    'notes' => $row['notes'] ?: null,
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $order->photos()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('vehicle-inspection-order::edit');
    }
}
