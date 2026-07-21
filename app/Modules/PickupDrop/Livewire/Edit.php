<?php

namespace App\Modules\PickupDrop\Livewire;

use App\Modules\Appointment\Models\Appointment;
use App\Modules\CancelReasonMaster\Models\CancelReasonMaster;
use App\Modules\ChecklistTemplateMaster\Models\ChecklistTemplateMaster;
use App\Modules\ComplaintTypeMaster\Models\ComplaintTypeMaster;
use App\Modules\CourierCompanyMaster\Models\CourierCompanyMaster;
use App\Modules\CustomerMaster\Models\CustomerAddress;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DistanceSlabMaster\Models\DistanceSlabMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\JobDescriptionMaster\Models\JobDescriptionMaster;
use App\Modules\PendingReasonMaster\Models\PendingReasonMaster;
use App\Modules\PhotoTypeMaster\Models\PhotoTypeMaster;
use App\Modules\PickupDrop\Models\PickupDrop;
use App\Modules\PickupDropOptionMaster\Models\PickupDropOptionMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\TimeSlotMaster\Models\TimeSlotMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Pickup / Drop')]
class Edit extends Component
{
    use WithFileUploads;

    public ?int $editingId = null;

    public ?string $pickup_drop_no = null;

    public string $direction = PickupDrop::DIRECTION_PICKUP;

    public ?int $pickup_drop_option_id = null;

    public ?int $appointment_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public string $scheduled_date = '';

    public string $scheduled_time = '';

    public ?int $time_slot_id = null;

    public string $address_choice = 'custom';

    public ?string $pickup_address = null;

    public ?int $pickup_region_id = null;

    public ?string $drop_address = null;

    public ?int $drop_region_id = null;

    public ?string $contact_phone = null;

    public ?int $driver_employee_id = null;

    public ?int $advisor_employee_id = null;

    public ?int $vendor_courier_id = null;

    public ?int $workshop_department_id = null;

    public ?int $service_type_id = null;

    public ?string $distance_km = null;

    public ?int $distance_slab_id = null;

    public ?string $distance_charge = null;

    public string $status = PickupDrop::STATUS_PENDING;

    public ?int $pending_reason_id = null;

    public ?int $reschedule_reason_id = null;

    public ?int $cancel_reason_id = null;

    public string $otp_mode = PickupDrop::OTP_OPTIONAL;

    public ?string $pickup_otp = null;

    public ?string $delivery_otp = null;

    public ?int $checklist_template_id = null;

    public ?string $notes = null;

    /** @var array<int, array{id:?int, complaint_type_id:?int, job_description_id:?int, description:string}> */
    public array $complaints = [];

    /** @var array<int, array{id:?int, label:string, is_required:bool, is_collected:bool, notes:?string}> */
    public array $documents = [];

    /** @var array<int, array{id:?int, photo_type_id:?int, leg:string, path:?string, notes:?string}> */
    public array $photos = [];

    /** Freshly uploaded files, keyed by the photo row index. */
    public array $photoFiles = [];

    /** ?int — passed via ?from-appointment=ID query string for the "Add from Appointment" flow. */
    #[Url(as: 'from-appointment')]
    public ?int $fromAppointment = null;

    public function mount(?PickupDrop $pickupDrop = null): void
    {
        if ($pickupDrop && $pickupDrop->exists) {
            $this->load($pickupDrop);

            return;
        }

        $when = now()->addDay()->setTime(10, 0);
        $this->scheduled_date = $when->format('Y-m-d');
        $this->scheduled_time = $when->format('H:i');

        if ($this->fromAppointment) {
            $this->prefillFromAppointment($this->fromAppointment);
        }
    }

    protected function load(PickupDrop $p): void
    {
        $this->editingId = $p->id;
        $this->pickup_drop_no = $p->pickup_drop_no;
        $this->direction = $p->direction;
        $this->pickup_drop_option_id = $p->pickup_drop_option_id;
        $this->appointment_id = $p->appointment_id;
        $this->customer_id = $p->customer_id;
        $this->customer_vehicle_id = $p->customer_vehicle_id;
        $this->scheduled_date = $p->scheduled_at?->format('Y-m-d') ?? '';
        $this->scheduled_time = $p->scheduled_at?->format('H:i') ?? '';
        $this->time_slot_id = $p->time_slot_id;
        $this->pickup_address = $p->pickup_address;
        $this->pickup_region_id = $p->pickup_region_id;
        $this->drop_address = $p->drop_address;
        $this->drop_region_id = $p->drop_region_id;
        $this->contact_phone = $p->contact_phone;
        $this->driver_employee_id = $p->driver_employee_id;
        $this->advisor_employee_id = $p->advisor_employee_id;
        $this->vendor_courier_id = $p->vendor_courier_id;
        $this->workshop_department_id = $p->workshop_department_id;
        $this->service_type_id = $p->service_type_id;
        $this->distance_km = $p->distance_km === null ? null : (string) $p->distance_km;
        $this->distance_slab_id = $p->distance_slab_id;
        $this->distance_charge = $p->distance_charge === null ? null : (string) $p->distance_charge;
        $this->status = $p->status;
        $this->pending_reason_id = $p->pending_reason_id;
        $this->reschedule_reason_id = $p->reschedule_reason_id;
        $this->cancel_reason_id = $p->cancel_reason_id;
        // Column is DB-defaulted, so an in-memory model may not carry it yet.
        $this->otp_mode = $p->otp_mode ?? PickupDrop::OTP_OPTIONAL;
        $this->pickup_otp = $p->pickup_otp;
        $this->delivery_otp = $p->delivery_otp;
        $this->checklist_template_id = $p->checklist_template_id;
        $this->notes = $p->notes;

        $this->complaints = $p->complaints()->get()->map(fn ($c) => [
            'id' => $c->id,
            'complaint_type_id' => $c->complaint_type_id,
            'job_description_id' => $c->job_description_id,
            'description' => $c->description,
        ])->all();

        $this->documents = $p->documents()->get()->map(fn ($d) => [
            'id' => $d->id,
            'label' => $d->label,
            'is_required' => (bool) $d->is_required,
            'is_collected' => (bool) $d->is_collected,
            'notes' => $d->notes,
        ])->all();

        $this->photos = $p->photos()->get()->map(fn ($ph) => [
            'id' => $ph->id,
            'photo_type_id' => $ph->photo_type_id,
            'leg' => $ph->leg,
            'path' => $ph->path,
            'notes' => $ph->notes,
        ])->all();
    }

    /**
     * "Add from Appointment" flow — copy customer, vehicle, schedule, routing and
     * the booked pickup/drop option across.
     */
    protected function prefillFromAppointment(int $appointmentId): void
    {
        $appointment = Appointment::find($appointmentId);
        if (! $appointment) {
            return;
        }

        $this->appointment_id = $appointment->id;
        $this->customer_id = $appointment->customer_id;
        $this->customer_vehicle_id = $appointment->customer_vehicle_id;
        $this->scheduled_date = $appointment->appointment_at?->format('Y-m-d') ?? $this->scheduled_date;
        $this->scheduled_time = $appointment->appointment_at?->format('H:i') ?? $this->scheduled_time;
        $this->time_slot_id = $appointment->time_slot_id;
        $this->pickup_drop_option_id = $appointment->pickup_drop_option_id;
        $this->advisor_employee_id = $appointment->assigned_advisor_id;
        $this->workshop_department_id = $appointment->workshop_department_id;
        $this->service_type_id = $appointment->service_type_id;

        // The chosen option decides which leg this job is for.
        if ($appointment->requiresPickup()) {
            $this->direction = PickupDrop::DIRECTION_PICKUP;
        } elseif ($appointment->requiresDrop()) {
            $this->direction = PickupDrop::DIRECTION_DROP;
        }

        $this->pickup_address = $appointment->pickup_address;
        $this->contact_phone = $appointment->pickup_contact_phone;
    }

    protected function rules(): array
    {
        return [
            'direction' => ['required', Rule::in(array_keys(PickupDrop::directions()))],
            'pickup_drop_option_id' => ['nullable', 'integer', Rule::exists('pickup_drop_options', 'id')->where('is_active', true)],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('is_active', true)],
            'customer_vehicle_id' => [
                'required', 'integer',
                Rule::exists('customer_vehicles', 'id')->where(fn ($q) => $q->where('customer_id', $this->customer_id)->where('is_active', true)),
            ],
            'scheduled_date' => ['required', 'date_format:Y-m-d'],
            'scheduled_time' => ['required', 'date_format:H:i'],
            'time_slot_id' => ['nullable', 'integer', Rule::exists('time_slots', 'id')->where('is_active', true)],
            // The leg being run decides which address is mandatory.
            'pickup_address' => [Rule::requiredIf(fn () => $this->direction === PickupDrop::DIRECTION_PICKUP), 'nullable', 'string', 'max:1000'],
            'pickup_region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'drop_address' => [Rule::requiredIf(fn () => $this->direction === PickupDrop::DIRECTION_DROP), 'nullable', 'string', 'max:1000'],
            'drop_region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'contact_phone' => ['nullable', 'string', 'min:10', 'max:20'],
            'driver_employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'advisor_employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'vendor_courier_id' => ['nullable', 'integer', Rule::exists('courier_companies', 'id')->where('is_active', true)],
            'workshop_department_id' => ['nullable', 'integer', Rule::exists('workshop_departments', 'id')->where('is_active', true)],
            'service_type_id' => ['nullable', 'integer', Rule::exists('service_types', 'id')->where('is_active', true)],
            'distance_km' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'distance_slab_id' => ['nullable', 'integer', Rule::exists('distance_slabs', 'id')->where('is_active', true)],
            'distance_charge' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(array_keys(PickupDrop::statuses()))],
            'pending_reason_id' => ['nullable', 'integer', Rule::exists('pending_reasons', 'id')->where('is_active', true)],
            'reschedule_reason_id' => ['nullable', 'integer', Rule::exists('pending_reasons', 'id')->where('is_active', true)],
            'cancel_reason_id' => [
                Rule::requiredIf(fn () => $this->status === PickupDrop::STATUS_CANCELLED),
                'nullable', 'integer',
                Rule::exists('cancel_reasons', 'id')->where('is_active', true),
            ],
            'otp_mode' => ['required', Rule::in(array_keys(PickupDrop::otpModes()))],
            'pickup_otp' => ['nullable', 'string', 'max:10'],
            'delivery_otp' => ['nullable', 'string', 'max:10'],
            'checklist_template_id' => ['nullable', 'integer', Rule::exists('checklist_templates', 'id')->where('is_active', true)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'complaints' => ['array'],
            'complaints.*.complaint_type_id' => ['nullable', 'integer', Rule::exists('complaint_types', 'id')->where('is_active', true)],
            'complaints.*.job_description_id' => ['nullable', 'integer', Rule::exists('job_descriptions', 'id')->where('is_active', true)],
            'complaints.*.description' => ['required', 'string', 'max:500'],
            'documents' => ['array'],
            'documents.*.label' => ['required', 'string', 'max:255'],
            'documents.*.is_required' => ['boolean'],
            'documents.*.is_collected' => ['boolean'],
            'documents.*.notes' => ['nullable', 'string', 'max:500'],
            'photos' => ['array'],
            'photos.*.photo_type_id' => ['nullable', 'integer', Rule::exists('photo_types', 'id')->where('is_active', true)],
            'photos.*.leg' => ['required', Rule::in(array_keys(PickupDrop::legs()))],
            'photos.*.notes' => ['nullable', 'string', 'max:255'],
            'photoFiles.*' => ['nullable', 'image', 'max:8192'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'cancel_reason_id' => 'cancel reason',
            'pickup_drop_option_id' => 'pickup/drop option',
            'time_slot_id' => 'time slot',
        ];
    }

    public function updatedCustomerId(): void
    {
        $this->customer_vehicle_id = null;
        $this->address_choice = 'custom';
    }

    public function updatedAddressChoice(string $value): void
    {
        if ($value === 'custom') {
            return;
        }

        $picked = $this->customerAddresses->firstWhere('id', (int) $value);
        if ($picked) {
            $this->pickup_address = $picked['full'];
        }
    }

    /** Distance drives the slab, and the slab drives the charge. */
    public function updatedDistanceKm(): void
    {
        if ($this->distance_km === null || $this->distance_km === '') {
            $this->distance_slab_id = null;
            $this->distance_charge = null;

            return;
        }

        $slab = DistanceSlabMaster::forDistance((float) $this->distance_km);

        $this->distance_slab_id = $slab?->id;
        $this->distance_charge = $slab === null ? null : (string) $slab->charge_amount;
    }

    /** Picking a template snapshots its document lines onto this job. */
    public function updatedChecklistTemplateId(): void
    {
        if (! $this->checklist_template_id) {
            return;
        }

        $template = ChecklistTemplateMaster::find($this->checklist_template_id);
        $items = $template?->items ?? [];

        if (! is_array($items) || $items === []) {
            return;
        }

        $this->documents = collect($items)->values()->map(fn ($item) => [
            'id' => null,
            'label' => strtoupper(is_array($item) ? ($item['label'] ?? $item['name'] ?? '') : (string) $item),
            'is_required' => is_array($item) ? (bool) ($item['is_required'] ?? false) : false,
            'is_collected' => false,
            'notes' => null,
        ])->filter(fn ($d) => $d['label'] !== '')->values()->all();
    }

    /**
     * Either driver OR vendor must be assigned, not both — this is a soft check
     * surfaced as a validation error before save.
     */
    protected function validateAssignment(): bool
    {
        if (! $this->driver_employee_id && ! $this->vendor_courier_id) {
            $this->addError('driver_employee_id', 'Pick either an in-house driver or a vendor courier.');

            return false;
        }
        if ($this->driver_employee_id && $this->vendor_courier_id) {
            $this->addError('vendor_courier_id', 'Pick only one — driver OR vendor, not both.');

            return false;
        }

        return true;
    }

    public function addComplaint(): void
    {
        $this->complaints[] = ['id' => null, 'complaint_type_id' => null, 'job_description_id' => null, 'description' => ''];
    }

    public function removeComplaint(int $index): void
    {
        unset($this->complaints[$index]);
        $this->complaints = array_values($this->complaints);
    }

    public function addDocument(): void
    {
        $this->documents[] = ['id' => null, 'label' => '', 'is_required' => false, 'is_collected' => false, 'notes' => null];
    }

    public function removeDocument(int $index): void
    {
        unset($this->documents[$index]);
        $this->documents = array_values($this->documents);
    }

    public function addPhoto(): void
    {
        $this->photos[] = ['id' => null, 'photo_type_id' => null, 'leg' => PickupDrop::LEG_PICKUP, 'path' => null, 'notes' => null];
    }

    public function removePhoto(int $index): void
    {
        unset($this->photos[$index], $this->photoFiles[$index]);
        $this->photos = array_values($this->photos);
        $this->photoFiles = array_values($this->photoFiles);
    }

    #[Computed]
    public function customers()
    {
        return CustomerMaster::query()->where('is_active', true)->orderBy('first_name')->limit(200)->get(['id', 'first_name', 'last_name', 'phone']);
    }

    #[Computed]
    public function customerVehicles()
    {
        if (! $this->customer_id) {
            return collect();
        }

        return CustomerVehicleMaster::query()
            ->with(['model.brand'])
            ->where('customer_id', $this->customer_id)
            ->where('is_active', true)
            ->orderBy('registration_no')
            ->get(['id', 'registration_no', 'model_id'])
            ->map(fn ($v) => [
                'id' => $v->id,
                'label' => trim(($v->model?->brand?->name ?? '').' '.($v->model?->name ?? '')).' — '.$v->registration_no,
            ]);
    }

    #[Computed]
    public function customerAddresses()
    {
        if (! $this->customer_id) {
            return collect();
        }

        return CustomerAddress::query()
            ->with('region.parent.parent.parent')
            ->where('customer_id', $this->customer_id)
            ->orderByDesc('is_primary')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'label' => $a->label,
                'is_primary' => (bool) $a->is_primary,
                'full' => trim(($a->address_line ?? '').($a->regionChain() ? ', '.$a->regionChain() : '')),
            ]);
    }

    #[Computed]
    public function drivers()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function couriers()
    {
        return CourierCompanyMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function pickupDropOptions()
    {
        return PickupDropOptionMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function timeSlots()
    {
        return TimeSlotMaster::query()->where('is_active', true)->orderBy('slot_start_time')->get();
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
    public function distanceSlabs()
    {
        return DistanceSlabMaster::query()->where('is_active', true)->orderBy('min_km')->get();
    }

    #[Computed]
    public function pendingReasons()
    {
        return PendingReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function cancelReasons()
    {
        return CancelReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function complaintTypes()
    {
        return ComplaintTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function jobDescriptions()
    {
        return JobDescriptionMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function photoTypes()
    {
        return PhotoTypeMaster::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function checklistTemplates()
    {
        return ChecklistTemplateMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'pickup_drop.update' : 'pickup_drop.create');

        $data = $this->validate();

        if (! $this->validateAssignment()) {
            return;
        }

        $data['scheduled_at'] = Carbon::parse($data['scheduled_date'].' '.$data['scheduled_time'].':00');
        unset($data['scheduled_date'], $data['scheduled_time']);

        $complaints = $data['complaints'] ?? [];
        $documents = $data['documents'] ?? [];
        $photos = $data['photos'] ?? [];
        unset($data['complaints'], $data['documents'], $data['photos'], $data['photoFiles']);

        foreach (['pickup_address', 'drop_address', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        // Reasons only belong to the state that asks for them.
        if ($this->status !== PickupDrop::STATUS_CANCELLED) {
            $data['cancel_reason_id'] = null;
        }
        if ($this->status !== PickupDrop::STATUS_PENDING) {
            $data['pending_reason_id'] = null;
        }

        $isCreate = $this->editingId === null;

        $row = DB::transaction(function () use ($data, $complaints, $documents, $photos, $isCreate) {
            if ($isCreate) {
                $row = PickupDrop::create($data);
            } else {
                $row = PickupDrop::findOrFail($this->editingId);

                // Moving a scheduled job keeps the slot it came from.
                if ($row->scheduled_at?->ne($data['scheduled_at'])) {
                    $data['rescheduled_from_at'] = $row->scheduled_at;
                }

                $row->update($data);
            }

            $this->syncComplaints($row, $complaints);
            $this->syncDocuments($row, $documents);
            $this->syncPhotos($row, $photos);

            return $row;
        });

        if ($isCreate) {
            $this->editingId = $row->id;
            $this->pickup_drop_no = $row->fresh()->pickup_drop_no;
        }

        Flux::toast(
            text: 'Pickup/Drop '.$row->pickup_drop_no.($isCreate ? ' created.' : ' updated.'),
            variant: 'success',
        );

        return redirect()->route('pickup-drop.index');
    }

    /** @param  array<int, array<string, mixed>>  $complaints */
    protected function syncComplaints(PickupDrop $row, array $complaints): void
    {
        $kept = [];

        foreach (array_values($complaints) as $i => $line) {
            $kept[] = $row->complaints()->updateOrCreate(
                ['id' => $line['id'] ?? null],
                [
                    'complaint_type_id' => $line['complaint_type_id'] ?: null,
                    'job_description_id' => $line['job_description_id'] ?: null,
                    'description' => strtoupper(trim($line['description'])),
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $row->complaints()->whereKeyNot($kept)->delete();
    }

    /** @param  array<int, array<string, mixed>>  $documents */
    protected function syncDocuments(PickupDrop $row, array $documents): void
    {
        $kept = [];

        foreach (array_values($documents) as $i => $line) {
            $kept[] = $row->documents()->updateOrCreate(
                ['id' => $line['id'] ?? null],
                [
                    'label' => strtoupper(trim($line['label'])),
                    'is_required' => (bool) ($line['is_required'] ?? false),
                    'is_collected' => (bool) ($line['is_collected'] ?? false),
                    'notes' => $line['notes'] ?: null,
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $row->documents()->whereKeyNot($kept)->delete();
    }

    /** @param  array<int, array<string, mixed>>  $photos */
    protected function syncPhotos(PickupDrop $row, array $photos): void
    {
        $kept = [];

        foreach (array_values($photos) as $i => $line) {
            $path = $line['path'] ?? null;
            $originalName = null;
            $size = null;

            $upload = $this->photoFiles[$i] ?? null;
            if ($upload) {
                $path = $upload->store('pickup-drops/'.$row->id, 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
            }

            // A row with no image at all is not worth persisting.
            if ($path === null) {
                continue;
            }

            $kept[] = $row->photos()->updateOrCreate(
                ['id' => $line['id'] ?? null],
                [
                    'photo_type_id' => $line['photo_type_id'] ?: null,
                    'leg' => $line['leg'],
                    'path' => $path,
                    'original_name' => $originalName,
                    'size_bytes' => $size,
                    'notes' => $line['notes'] ?: null,
                    'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $row->photos()->whereKeyNot($kept)->delete();
        $this->photoFiles = [];
    }

    public function render()
    {
        return view('pickup-drop::edit');
    }
}
