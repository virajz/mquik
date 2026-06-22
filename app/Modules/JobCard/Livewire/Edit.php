<?php

namespace App\Modules\JobCard\Livewire;

use App\Modules\Appointment\Models\Appointment;
use App\Modules\ComplaintTypeMaster\Models\ComplaintTypeMaster;
use App\Modules\CustomerApprovalTypeMaster\Models\CustomerApprovalTypeMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DamageTypeMaster\Models\DamageTypeMaster;
use App\Modules\DigitalInspection\Models\DigitalInspection;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\JobCard\Models\JobCardInventoryItem;
use App\Modules\JobCard\Models\JobCardPhoto;
use App\Modules\JobCardPendingReasonMaster\Models\JobCardPendingReasonMaster;
use App\Modules\JobDescriptionMaster\Models\JobDescriptionMaster;
use App\Modules\JobStageMaster\Models\JobStageMaster;
use App\Modules\PhotoTypeMaster\Models\PhotoTypeMaster;
use App\Modules\RequestedRepairMaster\Models\RequestedRepairMaster;
use App\Modules\ServicePackageMaster\Models\ServicePackageMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\VehicleInventoryItemMaster\Models\VehicleInventoryItemMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Flux\Flux;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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
#[Title('Job Card')]
class Edit extends Component
{
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $job_card_no = null;

    public ?int $appointment_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $workshop_department_id = null;

    public ?int $service_type_id = null;

    public ?int $service_package_id = null;

    public ?int $job_description_id = null;

    public ?int $insurance_company_id = null;

    public ?string $policy_no = null;

    public ?int $vendor_id = null;

    public ?int $customer_approval_type_id = null;

    public ?int $assigned_advisor_id = null;

    public ?int $assigned_technician_id = null;

    public string $opened_date = '';

    public string $opened_time = '';

    public string $promised_date = '';

    public string $promised_time = '';

    public string $expected_completion_date = '';

    public string $expected_completion_time = '';

    public ?int $km_at_service = null;

    public ?string $fuel_level = null;

    public ?string $suggested_services = null;

    public bool $terms_accepted = false;

    public string $status = JobCard::STATUS_OPEN;

    public ?int $current_stage_id = null;

    public ?int $pending_reason_id = null;

    public ?string $notes = null;

    /** Active tab in the rich (edit) layout. */
    #[Url(as: 'tab')]
    public string $activeTab = 'details';

    /** @var list<array{id: ?int, complaint_type_id: ?int, description: string, severity: string, sequence_no: int}> */
    public array $complaints = [];

    /** @var array<int, array{status: string, damage_type_id: ?int, condition_notes: ?string}>  keyed by vehicle_inventory_item_id */
    public array $inventoryItems = [];

    /** @var list<int> selected RequestedRepairMaster ids */
    public array $requestedRepairIds = [];

    /** @var array<int, TemporaryUploadedFile>  staged slot photo, keyed by photo_type_id (one per slot) */
    public array $slotFiles = [];

    /** @var array<int, TemporaryUploadedFile>  staged additional / damage photos (flat multi-upload) */
    public array $extraFiles = [];

    /** @var array<int, ?int>  damage type per staged extra photo, keyed by extraFiles index */
    public array $extraDamageTypes = [];

    /** @var array<int, ?string>  location note per staged extra photo, keyed by extraFiles index */
    public array $extraLocations = [];

    /** @var array<int, array{damage_type_id: ?int, location_note: ?string}>  editable meta for saved extra photos, keyed by photo id */
    public array $existingPhotoMeta = [];

    /** @var array<int, int>  ids of existing photos the user removed during this edit */
    public array $removedPhotoIds = [];

    public ?UploadedFile $signatureUpload = null;

    public bool $clearSignature = false;

    #[Url(as: 'from-appointment')]
    public ?int $fromAppointment = null;

    public function mount(?JobCard $jobCard = null): void
    {
        if ($jobCard && $jobCard->exists) {
            $this->load($jobCard);

            return;
        }

        $now = now();
        $this->opened_date = $now->format('Y-m-d');
        $this->opened_time = $now->format('H:i');
        $tomorrow = $now->copy()->addDay();
        $this->promised_date = $tomorrow->format('Y-m-d');
        $this->promised_time = $tomorrow->format('H:i');

        if ($this->fromAppointment) {
            $this->prefillFromAppointment($this->fromAppointment);
        }

        $this->seedInventoryChecklist();
    }

    protected function load(JobCard $jc): void
    {
        $jc->load(['complaints', 'inventoryItems', 'requestedRepairs:id']);

        $this->editingId = $jc->id;
        $this->job_card_no = $jc->job_card_no;
        $this->appointment_id = $jc->appointment_id;
        $this->customer_id = $jc->customer_id;
        $this->customer_vehicle_id = $jc->customer_vehicle_id;
        $this->workshop_department_id = $jc->workshop_department_id;
        $this->service_type_id = $jc->service_type_id;
        $this->service_package_id = $jc->service_package_id;
        $this->job_description_id = $jc->job_description_id;
        $this->insurance_company_id = $jc->insurance_company_id;
        $this->policy_no = $jc->policy_no;
        $this->vendor_id = $jc->vendor_id;
        $this->customer_approval_type_id = $jc->customer_approval_type_id;
        $this->assigned_advisor_id = $jc->assigned_advisor_id;
        $this->assigned_technician_id = $jc->assigned_technician_id;
        $this->opened_date = $jc->opened_at?->format('Y-m-d') ?? '';
        $this->opened_time = $jc->opened_at?->format('H:i') ?? '';
        $this->promised_date = $jc->promised_at?->format('Y-m-d') ?? '';
        $this->promised_time = $jc->promised_at?->format('H:i') ?? '';
        $this->expected_completion_date = $jc->expected_completion_at?->format('Y-m-d') ?? '';
        $this->expected_completion_time = $jc->expected_completion_at?->format('H:i') ?? '';
        $this->km_at_service = $jc->km_at_service;
        $this->fuel_level = $jc->fuel_level;
        $this->suggested_services = $jc->suggested_services;
        $this->terms_accepted = (bool) $jc->terms_accepted;
        $this->status = $jc->status;
        $this->current_stage_id = $jc->current_stage_id;
        $this->pending_reason_id = $jc->pending_reason_id;
        $this->notes = $jc->notes;

        $this->complaints = $jc->complaints
            ->map(fn ($c) => [
                'id' => $c->id,
                'complaint_type_id' => $c->complaint_type_id,
                'description' => $c->description,
                'severity' => $c->severity,
                'sequence_no' => (int) $c->sequence_no,
            ])->all();

        $this->seedInventoryChecklist();
        // Overlay saved values onto the seeded checklist.
        foreach ($jc->inventoryItems as $item) {
            $this->inventoryItems[$item->vehicle_inventory_item_id] = [
                'status' => $item->status ?? JobCardInventoryItem::STATUS_PRESENT,
                'damage_type_id' => $item->damage_type_id,
                'condition_notes' => $item->condition_notes,
            ];
        }

        $this->requestedRepairIds = $jc->requestedRepairs->pluck('id')->all();

        // Editable damage-type / location meta for saved additional-damage photos.
        foreach (JobCardPhoto::query()->where('job_card_id', $jc->id)->whereNull('photo_type_id')->get(['id', 'damage_type_id', 'location_note']) as $photo) {
            $this->existingPhotoMeta[$photo->id] = [
                'damage_type_id' => $photo->damage_type_id,
                'location_note' => $photo->location_note,
            ];
        }
    }

    /**
     * Seed the inventory checklist from active VehicleInventoryItemMaster rows so
     * the form shows every item from day one. Each item defaults to "present" —
     * the advisor only flags the exceptions (missing / damaged).
     */
    protected function seedInventoryChecklist(): void
    {
        $items = VehicleInventoryItemMaster::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id']);

        foreach ($items as $item) {
            if (! isset($this->inventoryItems[$item->id])) {
                $this->inventoryItems[$item->id] = [
                    'status' => JobCardInventoryItem::STATUS_PRESENT,
                    'damage_type_id' => null,
                    'condition_notes' => null,
                ];
            }
        }
    }

    protected function prefillFromAppointment(int $appointmentId): void
    {
        $appointment = Appointment::find($appointmentId);
        if (! $appointment) {
            return;
        }

        $this->appointment_id = $appointment->id;
        $this->customer_id = $appointment->customer_id;
        $this->customer_vehicle_id = $appointment->customer_vehicle_id;
        $this->workshop_department_id = $appointment->workshop_department_id;
        $this->service_type_id = $appointment->service_type_id;
        $this->assigned_advisor_id = $appointment->assigned_advisor_id;
        $this->assigned_technician_id = $appointment->assigned_technician_id;
    }

    protected function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('is_active', true)],
            'customer_vehicle_id' => [
                'required', 'integer',
                Rule::exists('customer_vehicles', 'id')->where(fn ($q) => $q->where('customer_id', $this->customer_id)->where('is_active', true)),
            ],
            'workshop_department_id' => ['required', 'integer', Rule::exists('workshop_departments', 'id')->where('is_active', true)],
            'service_type_id' => ['nullable', 'integer', Rule::exists('service_types', 'id')->where('is_active', true)],
            'service_package_id' => ['nullable', 'integer', Rule::exists('service_packages', 'id')->where('is_active', true)],
            'job_description_id' => ['nullable', 'integer', Rule::exists('job_descriptions', 'id')->where('is_active', true)],
            'insurance_company_id' => ['nullable', 'integer', Rule::exists('insurance_companies', 'id')->where('is_active', true)],
            'policy_no' => ['nullable', 'string', 'max:60'],
            'vendor_id' => ['nullable', 'integer', Rule::exists('vendors', 'id')->where('is_active', true)],
            'customer_approval_type_id' => ['nullable', 'integer', Rule::exists('customer_approval_types', 'id')->where('is_active', true)],
            'assigned_advisor_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'assigned_technician_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'opened_date' => ['required', 'date_format:Y-m-d'],
            'opened_time' => ['required', 'date_format:H:i'],
            'promised_date' => ['nullable', 'date_format:Y-m-d'],
            'promised_time' => ['nullable', 'date_format:H:i'],
            'expected_completion_date' => ['nullable', 'date_format:Y-m-d'],
            'expected_completion_time' => ['nullable', 'date_format:H:i'],
            'km_at_service' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'fuel_level' => ['nullable', Rule::in(array_keys(JobCard::fuelLevels()))],
            'suggested_services' => ['nullable', 'string', 'max:2000'],
            'terms_accepted' => ['boolean'],
            'status' => ['required', Rule::in(array_keys(JobCard::statuses()))],
            'current_stage_id' => ['nullable', 'integer', Rule::exists('job_stages', 'id')->where('is_active', true)],
            'pending_reason_id' => ['nullable', 'integer', Rule::exists('job_card_pending_reasons', 'id')->where('is_active', true)],
            'notes' => ['nullable', 'string', 'max:2000'],

            'complaints' => ['array'],
            'complaints.*.description' => ['required', 'string', 'max:1000'],
            'complaints.*.severity' => ['required', 'string', 'in:low,medium,high'],
            'complaints.*.complaint_type_id' => ['nullable', 'integer', Rule::exists('complaint_types', 'id')->where('is_active', true)],
            'complaints.*.sequence_no' => ['integer', 'min:1', 'max:99'],

            'inventoryItems' => ['array'],
            'inventoryItems.*.status' => ['required', Rule::in(array_keys(JobCardInventoryItem::statuses()))],
            'inventoryItems.*.damage_type_id' => ['nullable', 'integer', Rule::exists('damage_types', 'id')->where('is_active', true)],
            'inventoryItems.*.condition_notes' => ['nullable', 'string', 'max:500'],

            'requestedRepairIds' => ['array'],
            'requestedRepairIds.*' => ['integer', Rule::exists('requested_repairs', 'id')->where('is_active', true)],

            'slotFiles' => ['array'],
            'slotFiles.*' => ['image', 'max:8192'],  // 8 MB per photo
            'extraFiles' => ['array', 'max:30'],
            'extraFiles.*' => ['image', 'max:8192'],
            'extraDamageTypes' => ['array'],
            'extraDamageTypes.*' => ['nullable', 'integer', Rule::exists('damage_types', 'id')->where('is_active', true)],
            'extraLocations' => ['array'],
            'extraLocations.*' => ['nullable', 'string', 'max:255'],
            'existingPhotoMeta' => ['array'],
            'existingPhotoMeta.*.damage_type_id' => ['nullable', 'integer', Rule::exists('damage_types', 'id')->where('is_active', true)],
            'existingPhotoMeta.*.location_note' => ['nullable', 'string', 'max:255'],

            'signatureUpload' => ['nullable', 'image', 'max:2048'],
        ];
    }

    /**
     * Combined picker: choosing a vehicle sets its owning customer automatically.
     */
    public function updatedCustomerVehicleId(): void
    {
        $this->customer_id = $this->customer_vehicle_id
            ? CustomerVehicleMaster::whereKey($this->customer_vehicle_id)->value('customer_id')
            : null;
    }

    public function addComplaint(): void
    {
        $this->complaints[] = [
            'id' => null,
            'complaint_type_id' => null,
            'description' => '',
            'severity' => 'medium',
            'sequence_no' => count($this->complaints) + 1,
        ];
    }

    public function removeComplaint(int $index): void
    {
        if (! isset($this->complaints[$index])) {
            return;
        }
        unset($this->complaints[$index]);
        $this->complaints = array_values($this->complaints);
    }

    /**
     * Drop a staged (not-yet-saved) slot photo so the slot reverts to empty.
     */
    public function clearSlotFile(int $photoTypeId): void
    {
        unset($this->slotFiles[$photoTypeId]);
    }

    /**
     * Drop a staged (not-yet-saved) additional / damage photo.
     */
    public function removeExtraFile(int $index): void
    {
        if (! isset($this->extraFiles[$index])) {
            return;
        }
        unset($this->extraFiles[$index], $this->extraDamageTypes[$index], $this->extraLocations[$index]);
        $this->extraFiles = array_values($this->extraFiles);
        $this->extraDamageTypes = array_values($this->extraDamageTypes);
        $this->extraLocations = array_values($this->extraLocations);
    }

    public function removeExistingPhoto(int $photoId): void
    {
        if (! in_array($photoId, $this->removedPhotoIds, true)) {
            $this->removedPhotoIds[] = $photoId;
        }
    }

    public function undoRemoveExistingPhoto(int $photoId): void
    {
        $this->removedPhotoIds = array_values(array_filter(
            $this->removedPhotoIds,
            fn ($id) => $id !== $photoId,
        ));
    }

    public function markClearSignature(): void
    {
        $this->clearSignature = true;
        $this->signatureUpload = null;
    }

    #[Computed]
    public function customers()
    {
        return CustomerMaster::query()->where('is_active', true)->orderBy('first_name')->limit(200)->get(['id', 'first_name', 'last_name', 'phone']);
    }

    /**
     * Combined customer + vehicle search: every active vehicle, labelled with its
     * make/model, registration and owner so it's searchable by reg no or customer.
     * The currently-selected vehicle is always included even if outside the cap.
     */
    #[Computed]
    public function vehiclePickerOptions()
    {
        $rows = CustomerVehicleMaster::query()
            ->with(['model.brand:id,name', 'customer:id,first_name,last_name'])
            ->where('is_active', true)
            ->orderByDesc('id')
            ->limit(300)
            ->get(['id', 'registration_no', 'model_id', 'customer_id']);

        if ($this->customer_vehicle_id && ! $rows->contains('id', $this->customer_vehicle_id)) {
            $selected = CustomerVehicleMaster::query()
                ->with(['model.brand:id,name', 'customer:id,first_name,last_name'])
                ->find($this->customer_vehicle_id);
            if ($selected) {
                $rows->prepend($selected);
            }
        }

        return $rows->map(fn ($v) => [
            'id' => $v->id,
            'label' => trim(($v->model?->brand?->name ?? '').' '.($v->model?->name ?? '')).' — '.$v->registration_no
                .' · '.trim($v->customer?->first_name.' '.($v->customer?->last_name ?? '')),
        ]);
    }

    /**
     * The picked customer with the attributes shown in the read-only summary.
     */
    #[Computed]
    public function selectedCustomer()
    {
        if (! $this->customer_id) {
            return null;
        }

        return CustomerMaster::query()
            ->with(['businessType:id,name', 'gstType:id,name', 'primaryAddress.region.parent.parent.parent'])
            ->find($this->customer_id);
    }

    /**
     * The picked vehicle with brand/model/variant/fuel/etc. for the summary.
     */
    #[Computed]
    public function selectedVehicle()
    {
        if (! $this->customer_vehicle_id) {
            return null;
        }

        return CustomerVehicleMaster::query()
            ->with([
                'model.brand:id,name',
                'model.vehicleSegment:id,name',
                'variant.fuelType:id,name',
                'variant.transmissionType:id,name',
                'color:id,name',
                'registrationType:id,name',
            ])
            ->find($this->customer_vehicle_id);
    }

    /**
     * Walk the selected customer's primary-address region chain into
     * state / city / area / pincode parts for display.
     *
     * @return array<string, ?string>
     */
    #[Computed]
    public function customerLocation(): array
    {
        $parts = ['state' => null, 'city' => null, 'area' => null, 'pincode' => null];

        $region = $this->selectedCustomer?->primaryAddress?->region;
        while ($region) {
            if (array_key_exists($region->kind, $parts)) {
                $parts[$region->kind] = $region->name;
            }
            $region = $region->parent;
        }

        return $parts;
    }

    #[Computed]
    public function workshopDepartments()
    {
        return WorkshopDepartmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function jobStages()
    {
        return JobStageMaster::query()
            ->where('is_active', true)
            ->orderBy('track')->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'name', 'track']);
    }

    #[Computed]
    public function pendingReasons()
    {
        return JobCardPendingReasonMaster::query()
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Other job cards for the same vehicle — the "Vehicle History" drawer.
     */
    #[Computed]
    public function vehicleJobCards()
    {
        if (! $this->customer_vehicle_id) {
            return collect();
        }

        return JobCard::query()
            ->where('customer_vehicle_id', $this->customer_vehicle_id)
            ->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))
            ->with(['advisor:id,name', 'currentStage:id,name', 'workshopDepartment:id,name'])
            ->orderByDesc('opened_at')
            ->limit(50)
            ->get(['id', 'job_card_no', 'status', 'current_stage_id', 'assigned_advisor_id', 'workshop_department_id', 'opened_at', 'promised_at', 'closed_at', 'km_at_service']);
    }

    #[Computed]
    public function serviceTypes()
    {
        return ServiceTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function servicePackages()
    {
        return ServicePackageMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'is_amc']);
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Digital inspections already raised for this job card (for the in-context link).
     */
    #[Computed]
    public function digitalInspections()
    {
        if (! $this->editingId) {
            return collect();
        }

        return DigitalInspection::query()
            ->where('job_card_id', $this->editingId)
            ->orderByDesc('id')
            ->get(['id', 'inspection_no', 'status']);
    }

    #[Computed]
    public function jobDescriptions()
    {
        return JobDescriptionMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function insuranceCompanies()
    {
        return InsuranceCompanyMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function vendors()
    {
        return VendorMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function customerApprovalTypes()
    {
        return CustomerApprovalTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function complaintTypes()
    {
        return ComplaintTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function inventoryChecklist()
    {
        return VehicleInventoryItemMaster::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function damageTypes()
    {
        return DamageTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function requestedRepairOptions()
    {
        return RequestedRepairMaster::query()
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Active photo "slots" grouped into capture tabs, ordered by sort_order.
     *
     * @return Collection<int, array{key: string, label: string, slots: Collection}>
     */
    #[Computed]
    public function photoGroups()
    {
        return PhotoTypeMaster::query()
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'name', 'group', 'sort_order'])
            ->groupBy('group')
            ->map(fn ($slots, $group) => [
                'key' => $this->groupKey((string) $group),
                'label' => (string) $group,
                'minOrder' => (int) $slots->min('sort_order'),
                'slots' => $slots->values(),
            ])
            ->sortBy('minOrder')
            ->values();
    }

    /**
     * All persisted photos for this job card, eager-loaded for display.
     */
    #[Computed]
    public function jobCardPhotos()
    {
        if (! $this->editingId) {
            return collect();
        }

        return JobCardPhoto::query()
            ->with(['photoType:id,name', 'damageType:id,name'])
            ->where('job_card_id', $this->editingId)
            ->orderBy('sequence_no')
            ->get(['id', 'path', 'caption', 'location_note', 'photo_group', 'photo_type_id', 'damage_type_id', 'original_name', 'sequence_no']);
    }

    /**
     * The existing (not-removed) photo filling a given slot, or null.
     */
    public function slotPhoto(int $photoTypeId): ?JobCardPhoto
    {
        return $this->jobCardPhotos->first(
            fn ($p) => (int) $p->photo_type_id === $photoTypeId && ! in_array($p->id, $this->removedPhotoIds, true),
        );
    }

    /**
     * Is this slot covered — either a staged upload or a saved photo?
     */
    public function slotIsCaptured(int $photoTypeId): bool
    {
        return isset($this->slotFiles[$photoTypeId]) || $this->slotPhoto($photoTypeId) !== null;
    }

    /**
     * Existing (not-removed) additional / damage photos (no fixed slot).
     */
    public function extraPhotos(): Collection
    {
        return $this->jobCardPhotos
            ->filter(fn ($p) => $p->photo_type_id === null && ! in_array($p->id, $this->removedPhotoIds, true))
            ->values();
    }

    /**
     * @return array{captured: int, total: int}
     */
    public function photoProgress(): array
    {
        $total = 0;
        $captured = 0;
        foreach ($this->photoGroups as $group) {
            foreach ($group['slots'] as $slot) {
                $total++;
                if ($this->slotIsCaptured((int) $slot->id)) {
                    $captured++;
                }
            }
        }

        return ['captured' => $captured, 'total' => $total];
    }

    /**
     * Captured / total count for one tab's slots.
     *
     * @param  Collection  $slots
     */
    public function groupProgress($slots): string
    {
        $captured = $slots->filter(fn ($s) => $this->slotIsCaptured((int) $s->id))->count();

        return $captured.'/'.$slots->count();
    }

    protected function groupKey(string $group): string
    {
        return trim((string) preg_replace('/[^A-Za-z0-9]+/', '_', $group), '_') ?: 'GROUP';
    }

    public function existingSignaturePath(): ?string
    {
        if (! $this->editingId) {
            return null;
        }

        $jc = JobCard::query()->whereKey($this->editingId)->first(['customer_signature_path']);

        return $jc?->customer_signature_path;
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'job_card.update' : 'job_card.create');

        // Strip blank complaint rows before validating.
        $this->complaints = array_values(array_filter(
            $this->complaints,
            fn ($c) => filled($c['description'] ?? null),
        ));

        $data = $this->validate();
        $complaints = $data['complaints'] ?? [];
        unset(
            $data['complaints'],
            $data['inventoryItems'],
            $data['requestedRepairIds'],
            $data['slotFiles'],
            $data['extraFiles'],
            $data['extraDamageTypes'],
            $data['extraLocations'],
            $data['existingPhotoMeta'],
            $data['signatureUpload'],
        );

        $data['opened_at'] = Carbon::parse($data['opened_date'].' '.$data['opened_time'].':00');
        unset($data['opened_date'], $data['opened_time']);
        if (! empty($data['promised_date']) && ! empty($data['promised_time'])) {
            $data['promised_at'] = Carbon::parse($data['promised_date'].' '.$data['promised_time'].':00');
        } else {
            $data['promised_at'] = null;
        }
        unset($data['promised_date'], $data['promised_time']);
        if (! empty($data['expected_completion_date']) && ! empty($data['expected_completion_time'])) {
            $data['expected_completion_at'] = Carbon::parse($data['expected_completion_date'].' '.$data['expected_completion_time'].':00');
        } else {
            $data['expected_completion_at'] = null;
        }
        unset($data['expected_completion_date'], $data['expected_completion_time']);

        if ($data['terms_accepted']) {
            $data['terms_accepted_at'] = $data['terms_accepted_at'] ?? now();
        }

        foreach (['suggested_services', 'notes', 'policy_no'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        $jc = DB::transaction(function () use ($data, $complaints, $isCreate) {
            if ($isCreate) {
                $row = JobCard::create($data);
                $this->editingId = $row->id;
                $this->job_card_no = $row->fresh()->job_card_no;
            } else {
                $row = JobCard::findOrFail($this->editingId);
                $row->update($data);
            }

            $this->syncComplaints($row, $complaints);
            $this->syncInventoryItems($row);
            $row->requestedRepairs()->sync($this->requestedRepairIds);
            $this->syncPhotos($row);
            $this->syncSignature($row);

            return $row;
        });

        // Reset transient upload state so a subsequent save on the same component
        // (e.g. user stays on edit page in future) doesn't try to re-process them.
        $this->slotFiles = [];
        $this->extraFiles = [];
        $this->extraDamageTypes = [];
        $this->extraLocations = [];
        $this->removedPhotoIds = [];
        $this->signatureUpload = null;
        $this->clearSignature = false;

        Flux::toast(
            text: 'Job Card '.$jc->fresh()->job_card_no.($isCreate ? ' created.' : ' updated.'),
            variant: 'success',
        );

        // On create, drop into the rich edit view so the full tabs (inventory,
        // photos, inspection, sign-off) unlock for the just-opened card.
        if ($isCreate) {
            return redirect()->route('job-card.edit', $jc->id);
        }

        return redirect()->route('job-card.index');
    }

    protected function syncPhotos(JobCard $jc): void
    {
        // 1. Hard-delete photos the user removed.
        if ($this->removedPhotoIds) {
            $toDelete = JobCardPhoto::query()
                ->where('job_card_id', $jc->id)
                ->whereIn('id', $this->removedPhotoIds)
                ->get();

            foreach ($toDelete as $photo) {
                Storage::disk('public')->delete($photo->path);
                $photo->delete();
            }
        }

        $seq = (int) ($jc->photos()->max('sequence_no') ?? 0);

        // 2. Slot photos — one per slot. A new upload replaces the existing slot photo (retake).
        $slotGroups = PhotoTypeMaster::query()
            ->whereIn('id', array_keys($this->slotFiles))
            ->pluck('group', 'id');

        foreach ($this->slotFiles as $photoTypeId => $file) {
            if (! $file) {
                continue;
            }

            $existing = JobCardPhoto::query()
                ->where('job_card_id', $jc->id)
                ->where('photo_type_id', (int) $photoTypeId)
                ->get();
            foreach ($existing as $old) {
                Storage::disk('public')->delete($old->path);
                $old->delete();
            }

            $seq++;
            $jc->photos()->create($this->photoAttributes($file, $seq, [
                'photo_type_id' => (int) $photoTypeId,
                'photo_group' => $slotGroups[$photoTypeId] ?? null,
            ]));
        }

        // 3. Additional / damage photos (no fixed slot) — each can carry a damage type + location.
        foreach ($this->extraFiles as $i => $file) {
            if (! $file) {
                continue;
            }
            $location = $this->extraLocations[$i] ?? null;
            $seq++;
            $jc->photos()->create($this->photoAttributes($file, $seq, [
                'photo_type_id' => null,
                'photo_group' => 'ADDITIONAL',
                'damage_type_id' => $this->extraDamageTypes[$i] ?? null,
                'location_note' => filled($location) ? strtoupper((string) $location) : null,
            ]));
        }

        // 4. Update damage-type / location on already-saved additional photos.
        foreach ($this->existingPhotoMeta as $photoId => $meta) {
            $location = $meta['location_note'] ?? null;
            $jc->photos()
                ->whereKey($photoId)
                ->whereNull('photo_type_id')
                ->update([
                    'damage_type_id' => $meta['damage_type_id'] ?? null,
                    'location_note' => filled($location) ? strtoupper((string) $location) : null,
                ]);
        }
    }

    /**
     * Build a job_card_photos row payload for a stored upload.
     *
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function photoAttributes(TemporaryUploadedFile $file, int $seq, array $extra): array
    {
        $path = $file->store("job-cards/{$this->editingId}/photos", 'public');

        return array_merge([
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'sequence_no' => $seq,
        ], $extra);
    }

    protected function syncSignature(JobCard $jc): void
    {
        if ($this->clearSignature && $jc->customer_signature_path) {
            Storage::disk('public')->delete($jc->customer_signature_path);
            $jc->forceFill(['customer_signature_path' => null])->save();
        }

        if ($this->signatureUpload instanceof UploadedFile) {
            if ($jc->customer_signature_path) {
                Storage::disk('public')->delete($jc->customer_signature_path);
            }
            $path = $this->signatureUpload->store("job-cards/{$jc->id}/signature", 'public');
            $jc->forceFill(['customer_signature_path' => $path])->save();
        }
    }

    /**
     * @param  array<int, array{id?: int|null, complaint_type_id?: int|null, description: string, severity: string, sequence_no?: int}>  $rows
     */
    protected function syncComplaints(JobCard $jc, array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $i => $row) {
            $payload = [
                'complaint_type_id' => $row['complaint_type_id'] ?? null,
                'description' => strtoupper($row['description']),
                'severity' => $row['severity'],
                'sequence_no' => (int) ($row['sequence_no'] ?? $i + 1),
            ];

            if (! empty($row['id'])) {
                $existing = $jc->complaints()->whereKey($row['id'])->first();
                if ($existing) {
                    $existing->update($payload);
                    $keptIds[] = $existing->id;

                    continue;
                }
            }

            $created = $jc->complaints()->create($payload);
            $keptIds[] = $created->id;
        }

        $jc->complaints()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * Persist the inventory checklist as job_card_inventory_items rows. Only
     * exceptions are stored: an item that is missing, damaged, or carries a
     * condition note. A plain "present, nothing to note" item writes no row —
     * a job card with no inventory rows means the vehicle came in complete.
     */
    protected function syncInventoryItems(JobCard $jc): void
    {
        $keptIds = [];

        foreach ($this->inventoryItems as $vehicleInventoryItemId => $state) {
            $status = $state['status'] ?? JobCardInventoryItem::STATUS_PRESENT;
            $notes = $state['condition_notes'] ?? null;
            $damageTypeId = $status === JobCardInventoryItem::STATUS_DAMAGED
                ? ($state['damage_type_id'] ?? null)
                : null;

            $isException = $status !== JobCardInventoryItem::STATUS_PRESENT || filled($notes);
            if (! $isException) {
                continue;
            }

            $row = $jc->inventoryItems()
                ->updateOrCreate(
                    ['vehicle_inventory_item_id' => (int) $vehicleInventoryItemId],
                    [
                        'status' => $status,
                        'is_present' => $status === JobCardInventoryItem::STATUS_PRESENT,
                        'damage_type_id' => $damageTypeId,
                        'condition_notes' => filled($notes) ? strtoupper((string) $notes) : null,
                    ],
                );
            $keptIds[] = $row->id;
        }

        $jc->inventoryItems()->whereNotIn('id', $keptIds)->delete();
    }

    public function render()
    {
        return view('job-card::edit');
    }
}
