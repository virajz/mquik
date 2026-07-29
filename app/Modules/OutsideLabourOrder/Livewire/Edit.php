<?php

namespace App\Modules\OutsideLabourOrder\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\BayMaster\Models\BayMaster;
use App\Modules\ComplaintTypeMaster\Models\ComplaintTypeMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DelayReasonMaster\Models\DelayReasonMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\FollowUpModeMaster\Models\FollowUpModeMaster;
use App\Modules\InspectionTemplateMaster\Models\InspectionTemplateMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\JobDescriptionMaster\Models\JobDescriptionMaster;
use App\Modules\OutsideLabourInquiry\Models\OutsideLabourInquiry;
use App\Modules\OutsideLabourOrder\Models\OutsideLabourOrder;
use App\Modules\PhotoTypeMaster\Models\PhotoTypeMaster;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\ReworkReasonMaster\Models\ReworkReasonMaster;
use App\Modules\ServicePackageMaster\Models\ServicePackageMaster;
use App\Modules\ServiceSpecialistMaster\Models\ServiceSpecialistMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\TechnicianFinding\Models\TechnicianFinding;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\WorkOrderHoldReasonMaster\Models\WorkOrderHoldReasonMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Flux\Flux;
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
#[Title('Outside Labour Order')]
class Edit extends Component
{
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $order_no = null;

    public ?int $job_card_id = null;

    public ?int $vendor_id = null;

    public ?int $outside_labour_inquiry_id = null;

    public ?int $order_type_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $follow_up_mode_id = null;

    public ?string $communication_mode = null;

    public ?int $sequence_no = null;

    public string $vendorSearch = '';

    public string $vehicleSearch = '';

    public string $inquirySearch = '';

    public ?int $department_id = null;

    public ?int $service_type_id = null;

    public ?int $advisor_id = null;

    public ?int $technician_id = null;

    public ?int $bay_id = null;

    public ?int $inspection_template_id = null;

    public ?int $priority_id = null;

    public string $status = OutsideLabourOrder::STATUS_ASSIGNMENT_PENDING;

    public ?string $completion_type = null;

    public ?int $hold_reason_id = null;

    public ?int $rework_reason_id = null;

    public ?int $delay_reason_id = null;

    public ?string $notes = null;

    #[Url(as: 'tab')]
    public string $activeTab = 'details';

    /** ?int — passed via ?from-job-card=ID for the JobCard → OLO handoff. */
    #[Url(as: 'from-job-card')]
    public ?int $fromJobCard = null;

    /** @var list<array{id: ?int, inspection_item_id: ?int, inspection_item_group_id: ?int, label: string, group_name: ?string, result: string, notes: ?string, sequence_no: int, before_photo_path: ?string, after_photo_path: ?string}> */
    public array $items = [];

    /** @var array<int, TemporaryUploadedFile> keyed by item index */
    public array $itemBeforeFiles = [];

    /** @var array<int, TemporaryUploadedFile> keyed by item index */
    public array $itemAfterFiles = [];

    /** @var list<array{id: ?int, hold_reason_id: ?int, paused_at: ?string, resumed_at: ?string, notes: ?string}> */
    public array $pauses = [];

    /** @var array<int, array{id:?int, complaint_type_id:?int, job_description_id:?int, service_package_id:?int, is_additional:bool, description:string}> */
    public array $workScopes = [];

    /** @var array<int, array{id:?int, photo_type_id:?int, path:?string, notes:?string}> */
    public array $photos = [];

    /** Freshly uploaded order-level evidence, keyed by photo row index. */
    public array $photoFiles = [];

    public function mount(?OutsideLabourOrder $outsideLabourOrder = null): void
    {
        if ($outsideLabourOrder && $outsideLabourOrder->exists) {
            $this->load($outsideLabourOrder);

            return;
        }

        if ($this->fromJobCard) {
            $this->job_card_id = $this->fromJobCard;
        }
    }

    protected function load(OutsideLabourOrder $order): void
    {
        $order->load(['items.inspectionItem.group', 'pauses', 'workScopes', 'photos']);

        $this->editingId = $order->id;
        $this->order_no = $order->order_no;
        $this->job_card_id = $order->job_card_id;
        $this->vendor_id = $order->vendor_id;
        $this->outside_labour_inquiry_id = $order->outside_labour_inquiry_id;
        $this->order_type_id = $order->order_type_id;
        $this->customer_vehicle_id = $order->customer_vehicle_id;
        $this->follow_up_mode_id = $order->follow_up_mode_id;
        $this->communication_mode = $order->communication_mode;
        $this->sequence_no = $order->sequence_no;
        $this->department_id = $order->department_id;
        $this->service_type_id = $order->service_type_id;
        $this->advisor_id = $order->advisor_id;
        $this->technician_id = $order->technician_id;
        $this->bay_id = $order->bay_id;
        $this->inspection_template_id = $order->inspection_template_id;
        $this->priority_id = $order->priority_id;
        $this->status = $order->status;
        $this->completion_type = $order->completion_type;
        $this->hold_reason_id = $order->hold_reason_id;
        $this->rework_reason_id = $order->rework_reason_id;
        $this->delay_reason_id = $order->delay_reason_id;
        $this->notes = $order->notes;

        $this->items = $order->items->map(fn ($i) => [
            'id' => $i->id,
            'inspection_item_id' => $i->inspection_item_id,
            'inspection_item_group_id' => $i->inspection_item_group_id,
            'label' => $i->label,
            'group_name' => $i->group?->name,
            'result' => $i->result,
            'hours' => $i->hours === null ? null : (float) $i->hours,
            'notes' => $i->notes,
            'sequence_no' => (int) $i->sequence_no,
            'before_photo_path' => $i->before_photo_path,
            'after_photo_path' => $i->after_photo_path,
        ])->all();

        $this->pauses = $order->pauses->map(fn ($p) => [
            'id' => $p->id,
            'hold_reason_id' => $p->hold_reason_id,
            'paused_at' => $p->paused_at?->format('Y-m-d\TH:i'),
            'resumed_at' => $p->resumed_at?->format('Y-m-d\TH:i'),
            'notes' => $p->notes,
        ])->all();

        $this->workScopes = $order->workScopes->map(fn ($s) => [
            'id' => $s->id,
            'complaint_type_id' => $s->complaint_type_id,
            'job_description_id' => $s->job_description_id,
            'service_package_id' => $s->service_package_id,
            'is_additional' => (bool) $s->is_additional,
            'description' => $s->description,
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
    public function updatedInspectionTemplateId(): void
    {
        if ($this->editingId) {
            return;
        }

        $this->items = [];

        if (! $this->inspection_template_id) {
            return;
        }

        $template = InspectionTemplateMaster::with(['items.group'])->find($this->inspection_template_id);
        if (! $template) {
            return;
        }

        $seq = 1;
        foreach ($template->items as $tplItem) {
            $this->items[] = [
                'id' => null,
                'inspection_item_id' => $tplItem->id,
                'inspection_item_group_id' => $tplItem->inspection_item_group_id,
                'label' => $tplItem->name,
                'group_name' => $tplItem->group?->name,
                'result' => 'pending',
                'hours' => null,
                'notes' => null,
                'sequence_no' => $seq++,
                'before_photo_path' => null,
                'after_photo_path' => null,
            ];
        }
    }

    public function addItem(): void
    {
        $this->items[] = [
            'id' => null,
            'inspection_item_id' => null,
            'inspection_item_group_id' => null,
            'label' => '',
            'group_name' => null,
            'result' => 'pending',
            'hours' => null,
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
            'service_package_id' => null, 'is_additional' => false, 'description' => '',
        ];
    }

    public function removeWorkScope(int $index): void
    {
        unset($this->workScopes[$index]);
        $this->workScopes = array_values($this->workScopes);
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

    public function addPause(): void
    {
        $this->pauses[] = [
            'id' => null,
            'hold_reason_id' => null,
            'paused_at' => null,
            'resumed_at' => null,
            'notes' => null,
        ];
    }

    public function removePause(int $index): void
    {
        unset($this->pauses[$index]);
        $this->pauses = array_values($this->pauses);
    }

    protected function rules(): array
    {
        return [
            'job_card_id' => ['required', 'integer', 'exists:job_cards,id'],
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'outside_labour_inquiry_id' => ['nullable', 'integer', 'exists:outside_labour_inquiries,id'],
            'order_type_id' => ['nullable', 'integer', 'exists:service_specialists,id'],
            'customer_vehicle_id' => ['nullable', 'integer', 'exists:customer_vehicles,id'],
            'follow_up_mode_id' => ['nullable', 'integer', 'exists:follow_up_modes,id'],
            'communication_mode' => ['nullable', Rule::in(array_keys(OutsideLabourOrder::communicationModes()))],
            'sequence_no' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'department_id' => ['nullable', 'integer', 'exists:workshop_departments,id'],
            'service_type_id' => ['nullable', 'integer', 'exists:service_types,id'],
            'advisor_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'technician_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'bay_id' => ['nullable', 'integer', Rule::exists('bays', 'id')->where('is_active', true)],
            'inspection_template_id' => ['nullable', 'integer', Rule::exists('inspection_templates', 'id')->where('is_active', true)],
            'priority_id' => ['nullable', 'integer', Rule::exists('priorities', 'id')->where('is_active', true)],
            'status' => ['required', Rule::in(array_keys(OutsideLabourOrder::statuses()))],
            'completion_type' => ['nullable', Rule::in(array_keys(OutsideLabourOrder::completionTypes()))],
            'hold_reason_id' => ['nullable', 'integer', 'exists:work_order_hold_reasons,id'],
            'rework_reason_id' => ['nullable', 'integer', 'exists:rework_reasons,id'],
            'delay_reason_id' => ['nullable', 'integer', 'exists:delay_reasons,id'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['array'],
            'items.*.label' => ['required', 'string', 'max:255'],
            'items.*.result' => ['required', Rule::in(array_keys(OutsideLabourOrder::results()))],
            'items.*.hours' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],

            'itemBeforeFiles' => ['array'],
            'workScopes' => ['array'],
            'workScopes.*.complaint_type_id' => ['nullable', 'integer', Rule::exists('complaint_types', 'id')->where('is_active', true)],
            'workScopes.*.job_description_id' => ['nullable', 'integer', Rule::exists('job_descriptions', 'id')->where('is_active', true)],
            'workScopes.*.service_package_id' => ['nullable', 'integer', Rule::exists('service_packages', 'id')->where('is_active', true)],
            'workScopes.*.is_additional' => ['boolean'],
            'workScopes.*.description' => ['required', 'string', 'max:500'],
            'photos' => ['array'],
            'photos.*.photo_type_id' => ['nullable', 'integer', Rule::exists('photo_types', 'id')->where('is_active', true)],
            'photos.*.notes' => ['nullable', 'string', 'max:255'],
            'photoFiles.*' => ['nullable', 'image', 'max:8192'],
            'itemBeforeFiles.*' => ['image', 'max:8192'],
            'itemAfterFiles' => ['array'],
            'itemAfterFiles.*' => ['image', 'max:8192'],

            'pauses' => ['array'],
            'pauses.*.hold_reason_id' => ['nullable', 'integer', 'exists:work_order_hold_reasons,id'],
            'pauses.*.paused_at' => ['nullable', 'date'],
            'pauses.*.resumed_at' => ['nullable', 'date'],
            'pauses.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** Workshop-scoped urgency levels from the shared priority master. */
    #[Computed]
    public function priorities()
    {
        return PriorityMaster::forScope(PriorityMaster::APPLIES_WORKSHOP)->get(['id', 'name']);
    }

    #[Computed]
    public function vendors()
    {
        return $this->pickerOptions(
            query: VendorMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'code'],
            term: $this->vendorSearch,
            selected: $this->vendor_id,
            columns: ['id', 'name'],
            limit: 30,
        );
    }

    #[Computed]
    public function vehicles()
    {
        return $this->pickerOptions(
            query: CustomerVehicleMaster::query()->orderBy('registration_no'),
            searchColumns: ['registration_no'],
            term: $this->vehicleSearch,
            selected: $this->customer_vehicle_id,
            columns: ['id', 'registration_no'],
            limit: 30,
        );
    }

    #[Computed]
    public function inquiries()
    {
        return $this->pickerOptions(
            query: OutsideLabourInquiry::query()->latest('id'),
            searchColumns: ['inquiry_no'],
            term: $this->inquirySearch,
            selected: $this->outside_labour_inquiry_id,
            columns: ['id', 'inquiry_no'],
            limit: 30,
        );
    }

    #[Computed]
    public function orderTypes()
    {
        return ServiceSpecialistMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function followUpModes()
    {
        return FollowUpModeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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
            ->where('outside_labour_order_id', $this->editingId)
            ->orderByDesc('id')
            ->get();
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'outside_labour_order.update' : 'outside_labour_order.create');

        $data = $this->validate();
        $items = $data['items'] ?? [];
        $pauses = $data['pauses'] ?? [];
        $workScopes = $data['workScopes'] ?? [];
        $photos = $data['photos'] ?? [];
        unset($data['items'], $data['pauses'], $data['workScopes'], $data['photos'],
            $data['itemBeforeFiles'], $data['itemAfterFiles'], $data['photoFiles']);

        // Stamp lifecycle timestamps on status transitions.
        $existing = $this->editingId ? OutsideLabourOrder::find($this->editingId) : null;
        if ($data['status'] === OutsideLabourOrder::STATUS_ASSIGNED && (! $existing || ! $existing->assigned_at)) {
            $data['assigned_at'] = now();
        }
        if ($data['status'] === OutsideLabourOrder::STATUS_WIP && (! $existing || ! $existing->started_at)) {
            $data['started_at'] = now();
        }
        if ($data['status'] === OutsideLabourOrder::STATUS_COMPLETED) {
            $data['ended_at'] = now();
        }

        if (isset($data['notes']) && is_string($data['notes'])) {
            $data['notes'] = strtoupper($data['notes']);
        }

        $isCreate = $this->editingId === null;

        $order = DB::transaction(function () use ($data, $items, $pauses, $workScopes, $photos, $isCreate) {
            if ($isCreate) {
                $row = OutsideLabourOrder::create($data);
                $this->editingId = $row->id;
                $this->order_no = $row->fresh()->order_no;
            } else {
                $row = OutsideLabourOrder::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncItems($row, $items);
            $this->syncPauses($row, $pauses);
            $this->syncWorkScopes($row, $workScopes);
            $this->syncPhotos($row, $photos);

            return $row;
        });

        $this->itemBeforeFiles = [];
        $this->itemAfterFiles = [];
        $this->photoFiles = [];

        Flux::toast(
            text: 'Work Order '.$order->fresh()->order_no.($isCreate ? ' created.' : ' updated.'),
            variant: 'success',
        );

        if ($isCreate) {
            return redirect()->route('outside-labour-order.edit', $order->id);
        }

        return redirect()->route('outside-labour-order.index');
    }

    /**
     * @param  array<int, array{label: string, result: string, notes?: string|null}>  $rows
     */
    protected function syncItems(OutsideLabourOrder $order, array $rows): void
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
                $beforePath = $this->itemBeforeFiles[$i]->store("outside-labour-orders/{$order->id}/items", 'public');
            }
            if (isset($this->itemAfterFiles[$i]) && $this->itemAfterFiles[$i] instanceof TemporaryUploadedFile) {
                if ($afterPath) {
                    Storage::disk('public')->delete($afterPath);
                }
                $afterPath = $this->itemAfterFiles[$i]->store("outside-labour-orders/{$order->id}/items", 'public');
            }

            $payload = [
                'inspection_item_id' => $local['inspection_item_id'] ?? null,
                'inspection_item_group_id' => $local['inspection_item_group_id'] ?? null,
                'label' => strtoupper((string) $row['label']),
                'result' => $row['result'],
                'hours' => ($row['hours'] ?? '') !== '' ? $row['hours'] : null,
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
    protected function syncPauses(OutsideLabourOrder $order, array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $i => $row) {
            $local = $this->pauses[$i] ?? [];
            $payload = [
                'hold_reason_id' => $row['hold_reason_id'] ?? null,
                'paused_at' => $row['paused_at'] ?? null,
                'resumed_at' => $row['resumed_at'] ?? null,
                'notes' => isset($row['notes']) && is_string($row['notes']) ? strtoupper($row['notes']) : null,
            ];

            if (! empty($local['id'])) {
                $existing = $order->pauses()->whereKey($local['id'])->first();
                if ($existing) {
                    $existing->update($payload);
                    $keptIds[] = $existing->id;

                    continue;
                }
            }

            $created = $order->pauses()->create($payload);
            $keptIds[] = $created->id;
            $this->pauses[$i]['id'] = $created->id;
        }

        $order->pauses()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncWorkScopes(OutsideLabourOrder $order, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $keptIds[] = $order->workScopes()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'complaint_type_id' => $row['complaint_type_id'] ?: null,
                    'job_description_id' => $row['job_description_id'] ?: null,
                    'service_package_id' => $row['service_package_id'] ?: null,
                    'is_additional' => (bool) ($row['is_additional'] ?? false),
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
    protected function syncPhotos(OutsideLabourOrder $order, array $rows): void
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

            $keptIds[] = $order->photos()->updateOrCreate(
                ['id' => $row['id'] ?? null],
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
        return view('outside-labour-order::edit');
    }
}
