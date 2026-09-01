<?php

namespace App\Modules\JobCard\Livewire;

use App\Concerns\SearchesPickerOptions;
use App\Modules\Appointment\Models\Appointment;
use App\Modules\CustomerApprovalTypeMaster\Models\CustomerApprovalTypeMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DamageTypeMaster\Models\DamageTypeMaster;
use App\Modules\DigitalInspection\Models\DigitalInspection;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\GateInOut\Models\GateInOut;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\JobCard\Concerns\ShowsServiceHistory;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\JobCard\Models\JobCardInventoryItem;
use App\Modules\JobCard\Models\JobCardPhoto;
use App\Modules\JobCardPendingReasonMaster\Models\JobCardPendingReasonMaster;
use App\Modules\JobDescriptionMaster\Models\JobDescriptionMaster;
use App\Modules\JobHistory\Models\JobCardHistoryEvent;
use App\Modules\JobStageMaster\Models\JobStageMaster;
use App\Modules\PhotoTypeMaster\Models\PhotoTypeMaster;
use App\Modules\RequestedRepairMaster\Models\RequestedRepairMaster;
use App\Modules\ServicePackageMaster\Models\ServicePackageMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\StandardObservationMaster\Models\StandardObservationMaster;
use App\Modules\VehicleInspectionOrder\Models\VehicleInspectionOrder;
use App\Modules\VehicleInventoryItemMaster\Models\VehicleInventoryItemMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use App\Support\AppSettings;
use App\Support\RegistrationNumber;
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
    use SearchesPickerOptions;
    use ShowsServiceHistory;
    use WithFileUploads;

    /** Search term for the server-backed vendors picker. */
    public string $vendorSearch = '';

    public ?int $editingId = null;

    public ?string $job_card_no = null;

    public ?int $appointment_id = null;

    public ?int $customer_id = null;

    /** Search term for the server-backed customer+vehicle picker (~10k rows). */
    public string $customerSearch = '';

    public string $vehicleSearch = '';

    /** Search term for the inward picker. */
    public string $gateVisitSearch = '';

    public ?int $customer_vehicle_id = null;

    public ?int $workshop_department_id = null;

    public ?int $service_type_id = null;

    public ?int $service_package_id = null;

    public ?int $job_description_id = null;

    public ?int $insurance_company_id = null;

    /** Asked once, at intake, on a bodyshop card. The claim module owns it after that. */
    public ?string $policy_no = null;

    public ?int $vendor_id = null;

    public ?int $customer_approval_type_id = null;

    public ?int $assigned_advisor_id = null;

    public ?int $assigned_technician_id = null;

    /** Display-only: when the current technician was assigned (stamped by the model). */
    public ?string $technician_assigned_at = null;

    public string $opened_date = '';

    public string $opened_time = '';

    public string $promised_date = '';

    public string $promised_time = '';

    public string $expected_completion_date = '';

    public string $expected_completion_time = '';

    public ?int $km_at_service = null;

    public ?int $odometer_out = null;

    public ?int $avg_mileage = null;

    public ?string $brought_by = null;

    public ?string $fuel_level = null;

    public ?string $suggested_services = null;

    public ?string $additional_work = null;

    /** Read-only: the old-ERP bill number this card was billed under (links to the imported invoice). */
    public ?string $legacy_bill_no = null;

    public bool $terms_accepted = false;

    public string $status = JobCard::STATUS_OPEN;

    public ?int $current_stage_id = null;

    public ?int $pending_reason_id = null;

    public ?string $notes = null;

    /** Active tab in the rich (edit) layout. */
    #[Url(as: 'tab')]
    public string $activeTab = 'details';

    /** @var list<array{id: ?int, requested_repair_id: ?int, complaint_type_id: ?int, description: string, reported_at: ?string, is_repeat_job: bool, sequence_no: int}> */
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

    /** Who accepted: the customer themselves, or a named reference standing in. */
    public string $terms_accepted_by = 'customer';

    public ?string $reference_name = null;

    public ?string $reference_phone = null;

    public ?UploadedFile $signatureUpload = null;

    public bool $clearSignature = false;

    #[Url(as: 'from-appointment')]
    public ?int $fromAppointment = null;

    /** The gate visit this card was raised from (?from-gate-event=ID). */
    #[Url(as: 'from-gate-event')]
    public ?int $fromGateEvent = null;

    public ?int $gate_event_id = null;

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

        if ($this->fromGateEvent) {
            $this->prefillFromGateEvent($this->fromGateEvent);
        }

        $this->seedInventoryChecklist();
    }

    protected function load(JobCard $jc): void
    {
        $jc->load(['complaints', 'inventoryItems', 'requestedRepairs:id']);

        $this->editingId = $jc->id;
        $this->job_card_no = $jc->job_card_no;
        $this->appointment_id = $jc->appointment_id;
        $this->gate_event_id = $jc->gate_event_id;
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
        $this->technician_assigned_at = $jc->technician_assigned_at?->format('d/m/Y, h:i A');
        $this->opened_date = $jc->opened_at?->format('Y-m-d') ?? '';
        $this->opened_time = $jc->opened_at?->format('H:i') ?? '';
        $this->promised_date = $jc->promised_at?->format('Y-m-d') ?? '';
        $this->promised_time = $jc->promised_at?->format('H:i') ?? '';
        $this->expected_completion_date = $jc->expected_completion_at?->format('Y-m-d') ?? '';
        $this->expected_completion_time = $jc->expected_completion_at?->format('H:i') ?? '';
        $this->km_at_service = $jc->km_at_service;
        $this->fuel_level = $jc->fuel_level;
        $this->odometer_out = $jc->odometer_out;
        $this->avg_mileage = $jc->avg_mileage;
        $this->brought_by = $jc->brought_by;
        $this->additional_work = $jc->additional_work;
        $this->legacy_bill_no = $jc->legacy_bill_no;
        $this->suggested_services = $jc->suggested_services;
        $this->terms_accepted = (bool) $jc->terms_accepted;
        $this->terms_accepted_by = $jc->terms_accepted_by ?: 'customer';
        $this->reference_name = $jc->reference_name;
        $this->reference_phone = $jc->reference_phone;
        $this->status = $jc->status;
        $this->current_stage_id = $jc->current_stage_id;
        $this->pending_reason_id = $jc->pending_reason_id;
        $this->notes = $jc->notes;

        $this->complaints = $jc->complaints
            ->map(fn ($c) => [
                'id' => $c->id,
                'complaint_type_id' => $c->complaint_type_id,
                'requested_repair_id' => $c->requested_repair_id,
                'description' => $c->description,
                'reported_at' => $c->reported_at?->format('Y-m-d\TH:i'),
                'is_repeat_job' => (bool) $c->is_repeat_job,
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
        $this->syncOtherComplaintIds();

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
    /**
     * Switching an item's status wipes what the previous status collected —
     * a missing-quantity note reappearing as a damage description is worse
     * than no note at all.
     */
    public function updatedInventoryItems($value, string $key): void
    {
        if (! str_ends_with($key, '.status')) {
            return;
        }

        $itemId = explode('.', $key)[0];

        $this->inventoryItems[$itemId]['condition_notes'] = null;

        if ($value !== JobCardInventoryItem::STATUS_DAMAGED) {
            $this->inventoryItems[$itemId]['damage_type_id'] = null;
        }
    }

    protected function seedInventoryChecklist(): void
    {
        $items = VehicleInventoryItemMaster::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id']);

        foreach ($items as $item) {
            if (! isset($this->inventoryItems[$item->id])) {
                // Nothing is pre-picked: an untouched row must be visibly
                // unanswered, not silently "present".
                $this->inventoryItems[$item->id] = [
                    'status' => null,
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

    /**
     * Raise the card straight off the gate visit — the vehicle is physically
     * here, so the inward record already knows who and what.
     */
    protected function prefillFromGateEvent(int $gateEventId): void
    {
        $gate = GateInOut::find($gateEventId);
        if (! $gate) {
            return;
        }

        $this->gate_event_id = $gate->id;
        $this->customer_id = $gate->customer_id;
        $this->customer_vehicle_id = $gate->customer_vehicle_id;
    }

    protected function rules(): array
    {
        return [
            // Source links. Without rules these never reach save() — validate()
            // is what builds the persisted payload — so the card would lose the
            // appointment or gate visit it was raised from.
            'appointment_id' => ['nullable', 'integer', Rule::exists('appointments', 'id')],
            // Every new card must come from an inward, so a vehicle in the
            // workshop is always traceable to the moment it arrived. The column
            // stays nullable for the imported cards that predate the gate log.
            'gate_event_id' => ['required', 'integer', Rule::exists('gate_visits', 'id')],
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('is_active', true)],
            'customer_vehicle_id' => [
                'required', 'integer',
                Rule::exists('customer_vehicles', 'id')->where(fn ($q) => $q->where('customer_id', $this->customer_id)->where('is_active', true)),
            ],
            'workshop_department_id' => ['required', 'integer', Rule::exists('workshop_departments', 'id')->where('is_active', true)],
            'service_type_id' => ['required', 'integer', Rule::exists('service_types', 'id')->where('is_active', true)],
            'service_package_id' => ['nullable', 'integer', Rule::exists('service_packages', 'id')->where('is_active', true)],
            'job_description_id' => ['nullable', 'integer', Rule::exists('job_descriptions', 'id')->where('is_active', true)],
            // Hydrated on load and written back on save; without a rule here it
            // is silently dropped by validate() and never persists.
            'customer_approval_type_id' => ['nullable', 'integer', Rule::exists('customer_approval_types', 'id')],

            // An insurance job is not an insurance job without the insurer.
            'policy_no' => ['nullable', 'string', 'max:60'],
            'insurance_company_id' => [
                Rule::requiredIf(fn () => $this->isBodyshopDepartment && $this->isInsuranceServiceType()),
                'nullable', 'integer', Rule::exists('insurance_companies', 'id')->where('is_active', true),
            ],
            'assigned_advisor_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            // The work needs an owner: an in-house technician or an outside
            // contractor. Either satisfies it; neither does not.
            'assigned_technician_id' => [
                Rule::requiredIf(fn () => ! $this->vendor_id),
                'nullable', 'integer', Rule::exists('employees', 'id')->where('is_active', true),
            ],
            'vendor_id' => [
                Rule::requiredIf(fn () => ! $this->assigned_technician_id),
                'nullable', 'integer', Rule::exists('vendors', 'id'),
            ],
            'opened_date' => ['required', 'date_format:Y-m-d'],
            'opened_time' => ['required', 'date_format:H:i'],
            'promised_date' => ['required', 'date_format:Y-m-d'],
            'promised_time' => ['required', 'date_format:H:i'],
            'expected_completion_date' => ['nullable', 'date_format:Y-m-d'],
            'expected_completion_time' => ['nullable', 'date_format:H:i'],
            'km_at_service' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'fuel_level' => ['required', Rule::in(array_keys(JobCard::fuelLevels()))],
            'odometer_out' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'avg_mileage' => ['nullable', 'integer', 'min:0', 'max:32767'],
            'brought_by' => ['nullable', Rule::in(array_keys(JobCard::broughtByOptions()))],
            'additional_work' => ['nullable', 'string', 'max:2000'],
            'suggested_services' => ['nullable', 'string', 'max:2000'],
            'terms_accepted' => ['boolean'],
            'terms_accepted_by' => ['required', 'in:customer,reference'],
            // A reference stands in for the customer, so we need to know who.
            'reference_name' => [Rule::requiredIf(fn () => $this->terms_accepted && $this->terms_accepted_by === 'reference'), 'nullable', 'string', 'max:255'],
            'reference_phone' => ['nullable', 'string', 'min:10', 'max:20'],
            'status' => ['required', Rule::in(array_keys(JobCard::statuses()))],
            'current_stage_id' => ['nullable', 'integer', Rule::exists('job_stages', 'id')->where('is_active', true)],
            'pending_reason_id' => ['nullable', 'integer', Rule::exists('job_card_pending_reasons', 'id')->where('is_active', true)],
            'notes' => ['nullable', 'string', 'max:2000'],

            'complaints' => ['array'],
            'complaints.*.requested_repair_id' => ['required', 'integer', Rule::exists('requested_repairs', 'id')->where('is_active', true)],
            'complaints.*.reported_at' => ['nullable', 'date'],
            'complaints.*.is_repeat_job' => ['boolean'],
            'complaints.*.description' => ['nullable', 'string', 'max:1000'],
            'complaints.*.complaint_type_id' => ['nullable', 'integer', Rule::exists('complaint_types', 'id')->where('is_active', true)],
            'complaints.*.sequence_no' => ['integer', 'min:1', 'max:99'],

            'inventoryItems' => ['array'],
            // Unanswered is a legitimate state now that nothing is pre-picked.
            'inventoryItems.*.status' => ['nullable', Rule::in([JobCardInventoryItem::STATUS_PRESENT, JobCardInventoryItem::STATUS_MISSING, JobCardInventoryItem::STATUS_DAMAGED])],
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
            'requested_repair_id' => null,
            'description' => '',
            'reported_at' => now()->format('Y-m-d\\TH:i'),
            'is_repeat_job' => false,
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
        unset($this->selectedComplaintIds);
        $this->syncOtherComplaintIds();
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

    /**
     * Accept the T&Cs in one tap. The customer's own details fill a customer
     * acceptance; a reference acceptance keeps whoever the advisor named.
     */
    public function quickApproveTerms(): void
    {
        $this->validate([
            'terms_accepted_by' => ['required', 'in:customer,reference'],
            'reference_name' => [Rule::requiredIf(fn () => $this->terms_accepted_by === 'reference'), 'nullable', 'string', 'max:255'],
            'reference_phone' => ['nullable', 'string', 'min:10', 'max:20'],
        ], attributes: [
            'reference_name' => 'reference name',
            'reference_phone' => 'reference contact no.',
        ]);

        $this->terms_accepted = true;

        Flux::toast(
            text: $this->terms_accepted_by === 'reference'
                ? 'T&Cs accepted by '.$this->reference_name.'.'
                : 'T&Cs accepted by the customer.',
            variant: 'success',
        );
    }

    /** The current T&C text — edited in Settings, so it changes without a deploy. */
    #[Computed]
    public function jobCardTerms(): string
    {
        return (string) AppSettings::get('terms.job_card', config('mquik.terms.job_card', ''));
    }

    /**
     * The out-reading is taken at the counter as the car leaves, often by
     * someone who has no business submitting the whole job card — so it saves
     * on its own.
     */
    // ---- Pending reason: quick-add into the master --------------------------

    public string $pendingReasonQuickName = '';

    public function openPendingReasonQuickAdd(): void
    {
        $this->pendingReasonQuickName = '';
        $this->resetErrorBag('pendingReasonQuickName');

        Flux::modal('job-card-pending-reason-quick-add')->show();
    }

    /** Add a reason to the master and select it, without leaving the card. */
    public function createPendingReason(): void
    {
        $this->authorize('job_card_pending_reason_master.create');

        $this->validate(
            ['pendingReasonQuickName' => ['required', 'string', 'max:255']],
            attributes: ['pendingReasonQuickName' => 'reason'],
        );

        $reason = JobCardPendingReasonMaster::firstOrCreate(
            ['name' => mb_strtoupper($this->pendingReasonQuickName)],
            ['is_active' => true],
        );

        $this->pending_reason_id = $reason->id;
        $this->pendingReasonQuickName = '';
        unset($this->pendingReasons, $this->pendingReasonName);

        Flux::modal('job-card-pending-reason-quick-add')->close();
        Flux::toast(text: 'Reason added and selected.', variant: 'success');
    }

    public function saveOdometerOut(): void
    {
        $this->authorize('job_card.update');

        if (! $this->editingId) {
            return;
        }

        $this->validate(
            ['odometer_out' => ['nullable', 'integer', 'min:0', 'max:9999999']],
            attributes: ['odometer_out' => 'odometer out'],
        );

        JobCard::whereKey($this->editingId)->update(['odometer_out' => $this->odometer_out ?: null]);

        Flux::toast(
            text: $this->odometer_out ? 'Odometer out saved: '.number_format((int) $this->odometer_out).' km.' : 'Odometer out cleared.',
            variant: 'success',
        );
    }

    public function markClearSignature(): void
    {
        $this->clearSignature = true;
        $this->signatureUpload = null;
    }

    /** A different customer means a different garage — drop a vehicle that is not theirs. */
    public function updatedCustomerId(): void
    {
        if ($this->customer_vehicle_id
            && (int) CustomerVehicleMaster::whereKey($this->customer_vehicle_id)->value('customer_id') !== (int) $this->customer_id) {
            $this->customer_vehicle_id = null;
        }

        unset($this->vehiclePickerOptions);
    }

    /**
     * Insurance only matters for bodyshop work, so the insurer and policy are
     * asked for there and nowhere else. Matched on the department name — the
     * master carries no explicit flag for it.
     */
    #[Computed]
    public function isBodyshopDepartment(): bool
    {
        if (! $this->workshop_department_id) {
            return false;
        }

        $name = (string) WorkshopDepartmentMaster::whereKey($this->workshop_department_id)->value('name');

        return str_contains(mb_strtoupper($name), 'BODYSHOP');
    }

    /**
     * Outside help for this card: service contractors and outside-labour
     * vendors. The advisor picks one of these OR an in-house technician —
     * "vendor" on its own was too vague to route work by.
     */
    #[Computed]
    public function outsideVendors()
    {
        return $this->pickerOptions(
            query: VendorMaster::query()
                ->where('is_active', true)
                ->whereHas('vendorTypes', fn ($q) => $q->where(function ($w) {
                    $w->where('name', 'like', '%SERVICE CONTRACTOR%')->orWhere('name', 'like', '%OUTSIDE LABOUR%')->orWhere('name', 'like', 'OSL%');
                }))
                ->with('vendorTypes:id,name')
                ->orderBy('name'),
            searchColumns: ['name', 'code'],
            term: $this->vendorSearch,
            selected: $this->vendor_id,
            columns: ['id', 'name'],
            limit: 30,
        );
    }

    /** Is the chosen service type an insurance job? Matched on its name. */
    public function isInsuranceServiceType(): bool
    {
        if (! $this->service_type_id) {
            return false;
        }

        // The master says so. Matching on the name broke the moment one was
        // called "CASHLESS CLAIM" or "TP REPAIR".
        return (bool) ServiceTypeMaster::whereKey($this->service_type_id)->value('is_insurance');
    }

    /**
     * Insurance follows the service type, not just the department: a bodyshop
     * repair the customer pays for has no insurer, so moving onto one must not
     * leave an insurer and policy quietly attached.
     */
    public function updatedServiceTypeId(): void
    {
        if (! $this->isInsuranceServiceType()) {
            $this->insurance_company_id = null;
            $this->policy_no = null;
        }
    }

    /** Work goes to one place: in-house, or out. Picking one clears the other. */
    public function updatedAssignedTechnicianId(): void
    {
        if ($this->assigned_technician_id) {
            $this->vendor_id = null;
        }
    }

    public function updatedVendorId(): void
    {
        if ($this->vendor_id) {
            $this->assigned_technician_id = null;
            $this->technician_assigned_at = null;
        }
    }

    /**
     * Complaints are picked from the Requested Repair master — that is the list
     * a customer's words map onto. Standard observations belong to checklist
     * templates, which is a different conversation.
     *
     * @return Collection<int, RequestedRepairMaster>
     */
    #[Computed]
    public function complaintOptions()
    {
        return RequestedRepairMaster::query()
            ->where('is_active', true)
            ->when($this->workshop_department_id, fn ($q) => $q->whereHas(
                'workshopDepartments',
                fn ($d) => $d->where('workshop_departments.id', $this->workshop_department_id),
            ))
            ->with('complaintType:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'category', 'complaint_type_id']);
    }

    /**
     * The frequent complaints, grouped by their category — the tick list.
     *
     * The same split job descriptions already use: frequent earns a checkbox,
     * general lives in the picker underneath. A complaint is promoted to
     * frequent deliberately, so anything reaching here has been looked at.
     *
     * @return Collection<string, Collection<int, RequestedRepairMaster>>
     */
    #[Computed]
    public function complaintGroups()
    {
        return $this->complaintOptions
            ->where('category', 'frequent')
            ->groupBy(fn ($opt) => $opt->complaintType?->name ?? 'Other')
            ->sortKeys();
    }

    /**
     * Everything not common enough to earn a checkbox.
     *
     * @return Collection<int, RequestedRepairMaster>
     */
    #[Computed]
    public function otherComplaints()
    {
        return $this->complaintOptions->where('category', '!=', 'frequent')->values();
    }

    /**
     * Which complaints are ticked. Derived from the rows, and written back to
     * them — the rows stay the source of truth because they carry the repeat
     * flag and the reported time that a checkbox has no room for.
     *
     * @return list<string>
     */
    #[Computed]
    public function selectedComplaintIds(): array
    {
        return collect($this->complaints)
            ->pluck('requested_repair_id')
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();
    }

    /** Tick or untick one complaint, keeping whatever the row already carried. */
    public function toggleComplaint(int $repairId): void
    {
        $existing = collect($this->complaints)
            ->first(fn ($row) => (int) ($row['requested_repair_id'] ?? 0) === $repairId);

        if ($existing) {
            $this->complaints = collect($this->complaints)
                ->reject(fn ($row) => (int) ($row['requested_repair_id'] ?? 0) === $repairId)
                ->values()
                ->all();
        } else {
            $this->complaints[] = [
                'id' => null,
                'complaint_type_id' => RequestedRepairMaster::whereKey($repairId)->value('complaint_type_id'),
                'requested_repair_id' => $repairId,
                'description' => '',
                'reported_at' => now()->format('Y-m-d\\TH:i'),
                'is_repeat_job' => false,
                'sequence_no' => count($this->complaints) + 1,
            ];
        }

        $this->resequenceComplaints();
        unset($this->selectedComplaintIds);
        $this->syncOtherComplaintIds();
    }

    /**
     * The general complaints picked from the box below the checklist.
     *
     * A separate property because that select only ever lists its own half; the
     * frequent ones ticked above must not be dropped just because this box has
     * never heard of them.
     *
     * @var list<string>
     */
    public array $otherComplaintIds = [];

    public function updatedOtherComplaintIds(): void
    {
        $wanted = array_map('intval', $this->otherComplaintIds);

        foreach ($this->otherComplaints->pluck('id') as $id) {
            $id = (int) $id;
            $isOn = in_array((string) $id, $this->selectedComplaintIds, true);

            if ($isOn !== in_array($id, $wanted, true)) {
                $this->toggleComplaint($id);
            }
        }
    }

    /** Keep the picker showing exactly the general complaints on the card. */
    protected function syncOtherComplaintIds(): void
    {
        $general = $this->otherComplaints->pluck('id')->map(fn ($id) => (string) $id)->all();

        $this->otherComplaintIds = array_values(
            array_intersect($this->selectedComplaintIds, $general)
        );
    }

    /** Tick or untick a whole category at once. */
    public function toggleComplaintGroup(string $group): void
    {
        $ids = $this->complaintGroups->get($group)?->pluck('id')->all() ?? [];
        $selected = array_map('intval', $this->selectedComplaintIds);
        $allOn = $ids !== [] && count(array_diff($ids, $selected)) === 0;

        foreach ($ids as $id) {
            $isOn = in_array($id, array_map('intval', $this->selectedComplaintIds), true);

            if ($allOn === $isOn) {
                $this->toggleComplaint((int) $id);
            }
        }
    }

    protected function resequenceComplaints(): void
    {
        $this->complaints = collect($this->complaints)
            ->values()
            ->map(function ($row, $i) {
                $row['sequence_no'] = $i + 1;

                return $row;
            })
            ->all();
    }

    /** Picking a complaint fills its group; the advisor never sets the category by hand. */
    public function updatedComplaints($value, string $key): void
    {
        if (! str_ends_with($key, '.requested_repair_id')) {
            return;
        }

        $index = (int) explode('.', $key)[0];

        $this->complaints[$index]['complaint_type_id'] = $value
            ? RequestedRepairMaster::whereKey($value)->value('complaint_type_id')
            : null;
    }

    // ---- Quick-create a complaint into the master, group and all ------------

    public string $quickComplaintName = '';

    public function openComplaintQuickAdd(int $index): void
    {
        $this->quickComplaintIndex = $index;
        $this->quickComplaintName = '';

        $this->resetErrorBag('quickComplaintName');

        Flux::modal('job-card-complaint-quick-add')->show();
    }

    public ?int $quickComplaintIndex = null;

    public function createComplaintOption(): void
    {
        $this->authorize('requested_repair_master.create');

        $this->validate(
            ['quickComplaintName' => ['required', 'string', 'max:255']],
            attributes: ['quickComplaintName' => 'complaint'],
        );

        // No group asked for here: filing a complaint into a category is the
        // Requested Repair master's job, and asking mid-job-card only got a
        // guess. It stays unfiled until somebody files it there.
        $repair = RequestedRepairMaster::firstOrCreate(
            ['name' => mb_strtoupper(trim($this->quickComplaintName))],
            ['is_active' => true],
        );

        // A new complaint belongs to the department being worked, or it will not
        // show up in this picker the next time round.
        if ($this->workshop_department_id) {
            $repair->workshopDepartments()->syncWithoutDetaching([$this->workshop_department_id]);
        }

        if ($this->quickComplaintIndex !== null && isset($this->complaints[$this->quickComplaintIndex])) {
            $this->complaints[$this->quickComplaintIndex]['requested_repair_id'] = $repair->id;
            $this->complaints[$this->quickComplaintIndex]['complaint_type_id'] = $repair->complaint_type_id;
        }

        unset($this->complaintOptions);

        Flux::modal('job-card-complaint-quick-add')->close();
        Flux::toast(text: 'Complaint "'.$repair->name.'" added to the master.', variant: 'success');
    }

    #[Computed]
    public function customers()
    {
        // Server-side search and A–Z: a fixed 200-row slice hid everyone past it.
        return $this->pickerOptions(
            query: CustomerMaster::query()->where('is_active', true)->orderBy('first_name')->orderBy('last_name'),
            searchColumns: ['first_name', 'last_name', 'phone'],
            term: $this->customerSearch,
            selected: $this->customer_id,
            columns: ['id', 'first_name', 'last_name', 'phone'],
            limit: 30,
        );
    }

    /**
     * Vehicles for the picker, labelled with the full name the workshop says out
     * loud — "VOGUE 3.0 LWB (DSL) AT — GJ 05 RD 1234". Filtered to the chosen
     * customer once there is one, and sorted A–Z by that label.
     *
     * The brand is left off: the customer is already picked, and repeating the
     * make on every row just pushes the part that distinguishes them off-screen.
     */
    #[Computed]
    public function vehiclePickerOptions()
    {
        return $this->pickerOptions(
            query: CustomerVehicleMaster::query()
                ->with(['model.brand:id,name', 'variant.fuelType:id,name', 'variant.transmissionType:id,name', 'customer:id,first_name,last_name'])
                ->where('is_active', true)
                ->when($this->customer_id, fn ($q) => $q->where('customer_id', $this->customer_id)),
            searchColumns: ['registration_no', 'customer.first_name', 'customer.last_name'],
            term: $this->vehicleSearch,
            selected: $this->customer_vehicle_id,
            columns: ['id', 'registration_no', 'model_id', 'variant_id', 'customer_id'],
            limit: 30,
        )->map(fn ($v) => [
            'id' => $v->id,
            'label' => $v->fullName(withBrand: false).' — '.$v->registration_no,
            'owner' => trim($v->customer?->first_name.' '.($v->customer?->last_name ?? '')),
        ])->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)->values();
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
            ->with(['businessType:id,name', 'gstType:id,name', 'primaryAddress:id,customer_id,address_line,region_id', 'primaryAddress.region.parent.parent.parent'])
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
     * Advisors and technicians for the picked department.
     *
     * Both lists are now hard filters: the department a job card routes to is
     * linked to the HR department staff belong to, so this is a real join rather
     * than a name comparison, and showing people from other departments only
     * invites picking the wrong one.
     *
     * @return array{advisors: Collection, technicians: Collection}
     */
    #[Computed]
    public function employeesByDepartment(): array
    {
        $empty = ['advisors' => collect(), 'technicians' => collect()];

        if (! $this->workshop_department_id) {
            return $empty;
        }

        $departmentId = WorkshopDepartmentMaster::whereKey($this->workshop_department_id)->value('department_id');

        if (! $departmentId) {
            return $empty;
        }

        $staff = EmployeeMaster::query()
            ->where('is_active', true)
            ->where('department_id', $departmentId)
            ->with('designation:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'designation_id']);

        $designation = fn ($e) => mb_strtoupper((string) $e->designation?->name);

        return [
            'advisors' => $staff->filter(fn ($e) => str_contains($designation($e), 'ADVISOR'))->values(),
            'technicians' => $staff->filter(fn ($e) => $designation($e) === 'TECHNICIAN')->values(),
        ];
    }

    /**
     * Service types belonging to the picked department.
     *
     * `service_types.workshop_department_id` is a real link, so this filters
     * strictly — nothing from another department is offered.
     */
    #[Computed]
    public function serviceTypesForDepartment()
    {
        if (! $this->workshop_department_id) {
            return collect();
        }

        return ServiceTypeMaster::query()
            ->where('is_active', true)
            ->where('workshop_department_id', $this->workshop_department_id)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /** Stage name for the read-only routing summary. */
    #[Computed]
    public function currentStageName(): ?string
    {
        return $this->current_stage_id
            ? JobStageMaster::whereKey($this->current_stage_id)->value('name')
            : null;
    }

    #[Computed]
    public function pendingReasonName(): ?string
    {
        return $this->pending_reason_id
            ? JobCardPendingReasonMaster::whereKey($this->pending_reason_id)->value('name')
            : null;
    }

    /**
     * When the current pending reason was set.
     *
     * Taken from the history event rather than a column, so "on hold since" is
     * the same fact the timeline shows and cannot drift from it.
     */
    #[Computed]
    public function pendingSince(): ?string
    {
        if (! $this->editingId || ! $this->pending_reason_id) {
            return null;
        }

        return JobCardHistoryEvent::query()
            ->where('job_card_id', $this->editingId)
            ->where('event_type', JobCardHistoryEvent::TYPE_PENDING_REASON_CHANGED)
            ->latest('occurred_at')
            ->value('occurred_at')
            ?->format('d/m/Y, h:i A');
    }

    /**
     * Department drives the three fields under it, so changing it clears them.
     *
     * Leaving a stale advisor or service type from the previous department is
     * exactly the error this ordering is meant to prevent.
     */
    public function updatedWorkshopDepartmentId(): void
    {
        unset($this->isBodyshopDepartment);

        // Insurance is a bodyshop concern; moving off it must not leave an
        // insurer and policy quietly attached to a service card.
        if (! $this->isBodyshopDepartment) {
            $this->insurance_company_id = null;
            $this->policy_no = null;
        }

        $this->service_type_id = null;
        $this->assigned_advisor_id = null;
        $this->assigned_technician_id = null;

        unset($this->employeesByDepartment, $this->serviceTypesForDepartment, $this->requestedRepairOptions);

        // Drop any repair the new department does not offer.
        $allowed = $this->requestedRepairOptions->pluck('id')->all();
        $this->requestedRepairIds = array_values(array_intersect($this->requestedRepairIds, $allowed));
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

    /**
     * Vehicle inspection orders (work assignments) raised for this job card.
     */
    /**
     * Inward records to raise this card against.
     *
     * The visit is the starting point, so this is not filtered by vehicle —
     * picking an inward is what sets the vehicle. Cars still on site come first,
     * since those are the ones a card is normally being raised for.
     */
    #[Computed]
    public function gateVisits()
    {
        $term = trim($this->gateVisitSearch);

        // A plate typed "711%Q7" or "gj05" must find one stored "GJ 05 AR 1234",
        // so both sides are compared with their spacing and punctuation removed.
        $compact = RegistrationNumber::compact($term);

        return GateInOut::query()
            // Only cars still with us: a delivered visit cannot take a new card.
            ->whereNull('exited_at')
            // The chosen visit stays visible even once it has been delivered —
            // an existing card must not lose the inward it was raised against.
            ->when($this->gate_event_id, fn ($q) => $q->orWhere('id', $this->gate_event_id))
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                ->whereLike('gate_event_no', '%'.$term.'%', caseSensitive: false)
                ->orWhereRaw(
                    "upper(regexp_replace(registration_no, '[^A-Za-z0-9]', '', 'g')) like ?",
                    ['%'.$compact.'%'],
                )
                ->orWhereHas('customer', fn ($c) => $c
                    ->whereLike('first_name', '%'.$term.'%', caseSensitive: false)
                    ->orWhereLike('last_name', '%'.$term.'%', caseSensitive: false))
                ->when($this->gate_event_id, fn ($k) => $k->orWhere('id', $this->gate_event_id))))
            // The plate alone does not tell an advisor which car this is.
            ->with(['customerVehicle:id,registration_no,model_id', 'customerVehicle.model:id,name,brand_id', 'customerVehicle.model.brand:id,name'])
            ->orderByRaw('case when exited_at is null then 0 else 1 end')
            ->orderByDesc('entered_at')
            ->limit(25)
            ->get(['id', 'gate_event_no', 'registration_no', 'entered_at', 'exited_at', 'customer_vehicle_id', 'customer_id']);
    }

    /** Picking the inward fills the vehicle and customer it arrived with. */
    public function updatedGateEventId($value): void
    {
        if (! $value) {
            return;
        }

        $visit = GateInOut::find($value);
        if (! $visit) {
            return;
        }

        if ($visit->customer_vehicle_id) {
            $this->customer_vehicle_id = $visit->customer_vehicle_id;
            $this->customer_id = $visit->customer_id
                ?? CustomerVehicleMaster::whereKey($visit->customer_vehicle_id)->value('customer_id');
        }
    }

    #[Computed]
    public function inspectionOrders()
    {
        if (! $this->editingId) {
            return collect();
        }

        return VehicleInspectionOrder::query()
            ->where('job_card_id', $this->editingId)
            ->orderByDesc('id')
            ->get(['id', 'order_no', 'status']);
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
        return $this->pickerOptions(
            query: VendorMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'vendor_code'],
            term: $this->vendorSearch,
            selected: $this->vendor_id,
            columns: ['id', 'name'],
            limit: 20,
        );
    }

    #[Computed]
    public function customerApprovalTypes()
    {
        return CustomerApprovalTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /** Common complaint phrases users pick from (instead of typing). */
    #[Computed]
    public function standardObservations()
    {
        return StandardObservationMaster::query()
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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

    /**
     * Requested repairs offered by the card's department.
     *
     * Strictly what is assigned: a repair with no department appears nowhere,
     * so a new one has to be placed deliberately rather than quietly showing up
     * on every department's card.
     */
    #[Computed]
    public function requestedRepairOptions()
    {
        if (! $this->workshop_department_id) {
            return collect();
        }

        return RequestedRepairMaster::query()
            ->where('is_active', true)
            ->whereHas('workshopDepartments', fn ($d) => $d->where('workshop_departments.id', $this->workshop_department_id))
            ->orderBy('name')
            ->get(['id', 'name']);
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

        // Strip only blank NEW rows before validating (a new row is real once a
        // common-complaint is picked). Already-persisted rows are kept so they are
        // never silently deleted — validation makes the user pick a phrase instead.
        $this->complaints = array_values(array_filter(
            $this->complaints,
            fn ($c) => filled($c['requested_repair_id'] ?? null) || filled($c['id'] ?? null),
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

        // Stamped when the card is opened and never moved by a later edit — the
        // form shows it read-only, and this is what enforces it server-side.
        if ($this->editingId) {
            unset($data['opened_at']);
        }
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

            // A customer acceptance carries no reference; keeping one would
            // leave a stale name attached to a signature that isn't theirs.
            if ($data['terms_accepted_by'] === 'customer') {
                $data['reference_name'] = null;
                $data['reference_phone'] = null;
            }
        }

        foreach (['suggested_services', 'notes'] as $k) {
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
     * @param  array<int, array{id?: int|null, complaint_type_id?: int|null, requested_repair_id?: int|null, description?: string, reported_at?: string|null, is_repeat_job?: bool, sequence_no?: int}>  $rows
     */
    protected function syncComplaints(JobCard $jc, array $rows): void
    {
        $keptIds = [];

        // The picked repair is the authoritative complaint text — users pick,
        // they don't type — and it carries its own group.
        $repairs = RequestedRepairMaster::query()->get(['id', 'name', 'complaint_type_id'])->keyBy('id');

        foreach ($rows as $i => $row) {
            $repairId = $row['requested_repair_id'] ?? null;
            $repair = $repairId ? $repairs->get($repairId) : null;

            $payload = [
                // The group follows the repair, falling back to whatever was set.
                'complaint_type_id' => $repair?->complaint_type_id ?? ($row['complaint_type_id'] ?? null),
                'requested_repair_id' => $repairId,
                'description' => strtoupper((string) ($repair?->name ?? $row['description'] ?? '')),
                'reported_at' => $row['reported_at'] ?: now(),
                'is_repeat_job' => (bool) ($row['is_repeat_job'] ?? false),
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
            $status = $state['status'] ?? null;

            // Unanswered rows are not a record of anything — skip them.
            if ($status === null) {
                continue;
            }

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
