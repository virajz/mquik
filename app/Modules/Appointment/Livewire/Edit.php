<?php

namespace App\Modules\Appointment\Livewire;

use App\Concerns\CanQuickAddCustomer;
use App\Concerns\HasQuickCreate;
use App\Concerns\PicksAddressRegions;
use App\Concerns\PicksQuickServices;
use App\Concerns\SearchesPickerOptions;
use App\Modules\Appointment\Models\Appointment;
use App\Modules\Appointment\Models\AppointmentService;
use App\Modules\BookingChannelMaster\Models\BookingChannelMaster;
use App\Modules\CancelReasonMaster\Models\CancelReasonMaster;
use App\Modules\ComplaintTypeMaster\Models\ComplaintTypeMaster;
use App\Modules\CustomerMaster\Models\CustomerAddress;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\DistanceSlabMaster\Models\DistanceSlabMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\HolidayMaster\Models\HolidayMaster;
use App\Modules\JobDescriptionMaster\Models\JobDescriptionMaster;
use App\Modules\PendingReasonMaster\Models\PendingReasonMaster;
use App\Modules\PickupDropOptionMaster\Models\PickupDropOptionMaster;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\TimeSlotMaster\Models\TimeSlotMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use App\Support\ChildRows;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Appointment')]
class Edit extends Component
{
    use CanQuickAddCustomer;
    use HasQuickCreate;
    use PicksAddressRegions;
    use PicksQuickServices;
    use SearchesPickerOptions;

    public ?int $editingId = null;

    /** Display-only — generated server-side after first save. */
    public ?string $appointment_no = null;

    /** Date portion (Y-m-d) — combined with appointment_time on save. */
    public string $appointment_date = '';

    /** Time portion (H:i) — combined with appointment_date on save. */
    public string $appointment_time = '';

    public ?int $time_slot_id = null;

    /** The return leg's slot — only meaningful when the workshop drops the car back. */
    public ?int $drop_time_slot_id = null;

    /** One-way trip distance; drives the slab and the charge quoted on the call. */
    public ?string $distance_km = null;

    public ?int $distance_slab_id = null;

    public ?string $distance_charge = null;

    public ?int $booking_channel_id = null;

    public ?int $priority_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    /** Search term for the server-backed customer picker (~9.7k rows). */
    public string $customerSearch = '';

    /** Search term for the server-backed vehicle picker (can pick vehicle-first). */
    public string $vehicleSearch = '';

    public ?int $service_type_id = null;

    /** Every department this visit spans; the first is the primary. */
    public array $department_ids = [];

    /** Derived: the primary department — what downstream readers consume. */
    public ?int $workshop_department_id = null;

    public ?int $assigned_advisor_id = null;

    public ?int $assigned_technician_id = null;

    public ?int $pickup_drop_option_id = null;

    /** Sentinel: customer_address id, or 'custom' to type a fresh one. */
    public string $pickup_address_choice = 'custom';

    public ?string $pickup_address = null;

    /** Set when the pickup address is a saved customer address (resolved live). */
    public ?int $pickup_address_id = null;

    // ---- Drop leg. Separate from pickup: a car collected from home is often
    // returned to an office, so the two addresses are not interchangeable.
    public string $drop_address_choice = 'custom';

    public ?string $drop_address = null;

    public ?int $drop_address_id = null;

    public ?int $drop_region_id = null;

    public ?string $drop_contact_phone = null;

    public ?string $pickup_contact_phone = null;

    /** Derived, never edited — kept only so the header badge has something to read. */
    public string $status = Appointment::STATUS_CONFIRMED;

    public ?string $cancelled_at = null;

    /** The customer's own note, kept apart from the workshop's internal `notes`. */
    public ?string $customer_note = null;

    public ?int $cancel_reason_id = null;

    public ?int $pending_reason_id = null;

    /** Combobox text for the inline "Create …" option. */
    public string $pendingReasonSearch = '';

    /**
     * Customer complaints captured at booking time.
     *
     * @var array<int, array{id:?int, complaint_type_id:?int, job_description_id:?int, description:string}>
     */
    public array $complaints = [];

    public ?string $notes = null;

    public function mount(?Appointment $appointment = null): void
    {
        if ($appointment && $appointment->exists) {
            $this->load($appointment);

            return;
        }

        // New appointment defaults: tomorrow at 10:00.
        $tomorrow = now()->addDay()->setTime(10, 0);
        $this->appointment_date = $tomorrow->format('Y-m-d');
        $this->appointment_time = $tomorrow->format('H:i');
    }

    protected function load(Appointment $a): void
    {
        $this->editingId = $a->id;
        $this->appointment_no = $a->appointment_no;
        $this->appointment_date = $a->appointment_at?->format('Y-m-d') ?? '';
        $this->appointment_time = $a->appointment_at?->format('H:i') ?? '';
        $this->time_slot_id = $a->time_slot_id;
        $this->drop_time_slot_id = $a->drop_time_slot_id;
        $this->distance_km = $a->distance_km === null ? null : (string) $a->distance_km;
        $this->distance_slab_id = $a->distance_slab_id;
        $this->distance_charge = $a->distance_charge === null ? null : (string) $a->distance_charge;
        $this->booking_channel_id = $a->booking_channel_id;
        $this->priority_id = $a->priority_id;
        $this->customer_id = $a->customer_id;
        $this->customer_vehicle_id = $a->customer_vehicle_id;
        $this->workshop_department_id = $a->workshop_department_id;
        $ids = $a->workshopDepartments()->pluck('workshop_departments.id')->map(fn ($id) => (string) $id)->all();
        $this->department_ids = $ids !== [] ? $ids : array_filter([(string) ($a->workshop_department_id ?? '')]);
        // Rows saved before service types were scoped to a department can hold a
        // pairing the form no longer offers. Drop it rather than open a form that
        // fails validation on a field nobody touched.
        $this->service_type_id = $this->serviceTypes->contains('id', $a->service_type_id)
            ? $a->service_type_id
            : null;
        $this->assigned_advisor_id = $a->assigned_advisor_id;
        $this->assigned_technician_id = $a->assigned_technician_id;
        $this->pickup_drop_option_id = $a->pickup_drop_option_id;
        $this->pickup_address = $a->pickup_address;
        $this->pickup_address_id = $a->pickup_address_id;
        $this->pickup_region_id = $a->pickup_region_id;
        $this->drop_address = $a->drop_address;
        $this->drop_address_id = $a->drop_address_id;
        $this->drop_region_id = $a->drop_region_id;
        $this->drop_contact_phone = $a->drop_contact_phone;
        $this->drop_address_choice = $a->drop_address_id ? (string) $a->drop_address_id : 'custom';
        $this->seedDropRegionPickers();
        $this->pickup_contact_phone = $a->pickup_contact_phone;
        $this->status = $a->status;
        $this->cancelled_at = $a->cancelled_at?->toDateTimeString();
        $this->cancel_reason_id = $a->cancel_reason_id;
        $this->pending_reason_id = $a->pending_reason_id;
        $this->notes = $a->notes;
        $this->customer_note = $a->customer_note;

        $this->complaints = $a->complaints()->get()->map(fn ($c) => [
            'id' => $c->id,
            'complaint_type_id' => $c->complaint_type_id,
            'job_description_id' => $c->job_description_id,
            'description' => $c->description,
        ])->all();
        $this->seedSelectedServices();
        // Reflect a linked saved address in the picker; otherwise treat it as custom text.
        $this->pickup_address_choice = $a->pickup_address_id ? (string) $a->pickup_address_id : 'custom';
        if ($a->pickup_address_id) {
            $this->pickup_address = $a->pickupAddress?->fullAddress();
        }
        $this->seedRegionPickers('pickup');
    }

    protected function rules(): array
    {
        return [
            'appointment_date' => ['required', 'date_format:Y-m-d'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'time_slot_id' => [
                Rule::requiredIf(fn () => $this->optionInvolvesPickup()),
                'nullable', 'integer',
                Rule::exists('time_slots', 'id')->where('is_active', true),
            ],
            'drop_time_slot_id' => [
                Rule::requiredIf(fn () => $this->optionInvolvesDrop()),
                'nullable', 'integer',
                Rule::exists('time_slots', 'id')->where('is_active', true),
            ],
            'distance_km' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'distance_slab_id' => ['nullable', 'integer', Rule::exists('distance_slabs', 'id')->where('is_active', true)],
            'distance_charge' => ['nullable', 'numeric', 'min:0'],
            'booking_channel_id' => ['required', 'integer', Rule::exists('booking_channels', 'id')->where('is_active', true)],
            'priority_id' => ['required', 'integer', Rule::exists('priorities', 'id')->where('is_active', true)],
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('is_active', true)],
            'customer_vehicle_id' => [
                'required', 'integer',
                Rule::exists('customer_vehicles', 'id')->where(fn ($q) => $q->where('customer_id', $this->customer_id)->where('is_active', true)),
            ],
            'service_type_id' => [
                'nullable', 'integer',
                // Scoped to the department, so a stale pairing cannot be posted
                // past the dropdown that no longer offers it.
                Rule::exists('service_types', 'id')
                    ->where('is_active', true)
                    ->whereIn('workshop_department_id', $this->selectedDepartmentIdInts()),
            ],
            'department_ids' => ['required', 'array', 'min:1'],
            'department_ids.*' => ['integer', Rule::exists('workshop_departments', 'id')->where('is_active', true)],
            'assigned_advisor_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'assigned_technician_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'pickup_drop_option_id' => ['required', 'integer', Rule::exists('pickup_drop_options', 'id')->where('is_active', true)],
            // Address only matters when the workshop is the one moving the vehicle.
            'pickup_address' => [Rule::requiredIf(fn () => $this->optionInvolvesPickup() && $this->pickup_address_choice === 'custom'), 'nullable', 'string', 'max:1000'],
            'pickup_address_id' => ['nullable', 'integer', Rule::exists('customer_addresses', 'id')],
            'pickup_region_id' => ['nullable', 'integer', Rule::exists('regions', 'id')],
            // Required only when the workshop is actually returning the vehicle.
            'drop_address' => [Rule::requiredIf(fn () => $this->optionInvolvesDrop() && $this->drop_address_choice === 'custom'), 'nullable', 'string', 'max:1000'],
            'drop_address_id' => ['nullable', 'integer', Rule::exists('customer_addresses', 'id')],
            'drop_region_id' => ['nullable', 'integer', Rule::exists('regions', 'id')],
            'drop_contact_phone' => ['nullable', 'string', 'max:20'],
            'pickup_contact_phone' => ['nullable', 'string', 'min:10', 'max:20'],
            'cancel_reason_id' => ['nullable', 'integer', Rule::exists('cancel_reasons', 'id')->where('is_active', true)],
            'pending_reason_id' => ['nullable', 'integer', Rule::exists('pending_reasons', 'id')->where('is_active', true)],
            'complaints' => ['array'],
            'complaints.*.complaint_type_id' => ['nullable', 'integer', Rule::exists('complaint_types', 'id')->where('is_active', true)],
            'complaints.*.job_description_id' => ['nullable', 'integer', Rule::exists('job_descriptions', 'id')->where('is_active', true)],
            'complaints.*.description' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'booking_channel_id' => 'booking channel',
            'pickup_drop_option_id' => 'pickup/drop option',
            'cancel_reason_id' => 'cancel reason',
            'time_slot_id' => 'pickup time slot',
            'drop_time_slot_id' => 'drop time slot',
        ];
    }

    public function updatedCustomerId(): void
    {
        // Customer changed → the existing vehicle pick almost certainly belongs to the old customer.
        $this->customer_vehicle_id = null;
        // Same for any saved-address pick that belonged to the old customer.
        $this->pickup_address_choice = 'custom';
    }

    /** Vehicle-first: picking a vehicle auto-sets its owner (matches the Job Card flow). */
    public function updatedCustomerVehicleId(): void
    {
        if ($this->customer_vehicle_id) {
            $this->customer_id = CustomerVehicleMaster::whereKey($this->customer_vehicle_id)->value('customer_id');
        }
    }

    /** True when the picked option means the workshop returns the vehicle. */
    /**
     * Typing a distance resolves its slab and quotes that slab's charge.
     * The charge stays editable — a slab is the default price, not a fixed one.
     */
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

    /** @return Collection<int, DistanceSlabMaster> */
    #[Computed]
    public function distanceSlabs(): Collection
    {
        return DistanceSlabMaster::query()->where('is_active', true)->orderBy('min_km')->get();
    }

    public function optionInvolvesDrop(): bool
    {
        if (! $this->pickup_drop_option_id) {
            return false;
        }

        return (bool) PickupDropOptionMaster::find($this->pickup_drop_option_id)?->involves_drop;
    }

    /** True when the picked option means the workshop collects the vehicle. */
    public function optionInvolvesPickup(): bool
    {
        if (! $this->pickup_drop_option_id) {
            return false;
        }

        return (bool) PickupDropOptionMaster::find($this->pickup_drop_option_id)?->involves_pickup;
    }

    /** Required by PicksQuickServices — the booking's saved job rows. */
    protected function savedServiceRows(): Collection
    {
        return $this->editingId
            ? AppointmentService::where('appointment_id', $this->editingId)->orderBy('sequence_no')->get()
            : collect();
    }

    /** @return list<int> */
    protected function selectedDepartmentIdInts(): array
    {
        return array_values(array_map('intval', $this->department_ids));
    }

    /** The checklist and pickers draw from every selected department. */
    protected function serviceDepartmentIds(): array
    {
        return $this->selectedDepartmentIdInts();
    }

    /** Changing the selection invalidates anything the dropped departments owned. */
    public function updatedDepartmentIds(): void
    {
        $ids = $this->selectedDepartmentIdInts();
        $this->workshop_department_id = $ids[0] ?? null;

        if ($this->service_type_id && ! in_array(
            (int) ServiceTypeMaster::whereKey($this->service_type_id)->value('workshop_department_id'),
            $ids,
            true,
        )) {
            $this->service_type_id = null;
        }

        unset($this->serviceTypes, $this->frequentServiceGroups, $this->frequentServiceGroupsByDepartment, $this->otherJobDescriptions, $this->departmentName, $this->employeesByDepartment);

        $this->dropStaffOutsideDepartment();

        $this->dropServicesOutsideDepartment();
    }

    /**
     * Clear an advisor or technician the new department selection no longer
     * offers. Only fires on an edit to the picker — a saved booking keeps
     * whoever it was assigned to, even if that person has since moved.
     */
    protected function dropStaffOutsideDepartment(): void
    {
        $staff = $this->employeesByDepartment;

        // Never clear a selection we cannot offer a replacement for. An empty
        // list means nobody carries that designation at all, which is a gap in
        // Employee Master — silently wiping a valid advisor over it would make
        // a required field impossible to satisfy.
        if ($staff['advisors']->isNotEmpty()
            && $this->assigned_advisor_id
            && ! $staff['advisors']->contains('id', (int) $this->assigned_advisor_id)) {
            $this->assigned_advisor_id = null;
        }

        if ($staff['technicians']->isNotEmpty()
            && $this->assigned_technician_id
            && ! $staff['technicians']->contains('id', (int) $this->assigned_technician_id)) {
            $this->assigned_technician_id = null;
        }
    }

    public function updatedPickupDropOptionId(): void
    {
        // A slot is meaningless for a leg the workshop is not doing.
        if (! $this->optionInvolvesPickup()) {
            $this->time_slot_id = null;
        }

        if (! $this->optionInvolvesPickup() && ! $this->optionInvolvesDrop()) {
            // Nobody drives anywhere, so there is no trip to measure or charge for.
            $this->distance_km = null;
            $this->distance_slab_id = null;
            $this->distance_charge = null;
        }

        if (! $this->optionInvolvesDrop()) {
            $this->drop_time_slot_id = null;
        }

        // The drop leg defaults to the customer's address too — most returns go
        // back where the car came from, and the user can override.
        if (! $this->optionInvolvesDrop()) {
            $this->drop_address = null;
            $this->drop_address_id = null;
            $this->drop_region_id = null;
            $this->drop_contact_phone = null;
            $this->drop_address_choice = 'custom';
            $this->drop_state_id = null;
            $this->drop_city_id = null;
        } else {
            $this->defaultDropAddress();
        }

        if (! $this->optionInvolvesPickup()) {
            $this->pickup_address = null;
            $this->pickup_address_id = null;
            $this->pickup_contact_phone = null;
            $this->pickup_address_choice = 'custom';
            $this->pickup_region_id = null;
            $this->seedRegionPickers('pickup');

            return;
        }

        // If the customer has saved addresses, default to the primary one (linked live).
        $primary = $this->customerAddresses->firstWhere('is_primary', true) ?? $this->customerAddresses->first();
        if ($primary) {
            $this->pickup_address_choice = (string) $primary['id'];
            $this->pickup_address_id = (int) $primary['id'];
            $this->pickup_address = $primary['full'];
            $this->pickup_region_id = $primary['region_id'];
            $this->seedRegionPickers('pickup');
        }
        // The contact phone defaults to the customer's live phone — no stale snapshot;
        // leave it null and let resolvedContactPhone() supply it unless the user overrides.
    }

    /** Prefill the drop address from the customer's primary address. */
    protected function defaultDropAddress(): void
    {
        if ($this->drop_address_id || filled($this->drop_address)) {
            return;   // already chosen or typed — never overwrite the user
        }

        $primary = $this->customerAddresses->firstWhere('is_primary', true) ?? $this->customerAddresses->first();

        if ($primary) {
            $this->drop_address_choice = (string) $primary['id'];
            $this->drop_address_id = (int) $primary['id'];
            $this->drop_address = $primary['full'];
            $this->drop_region_id = $primary['region_id'];
            $this->seedDropRegionPickers();
        }
    }

    public function updatedDropAddressChoice(string $value): void
    {
        if ($value === 'custom') {
            // Unlink the saved address but keep the text, so it can be edited.
            $this->drop_address_id = null;

            return;
        }

        $picked = $this->customerAddresses->firstWhere('id', (int) $value);

        if ($picked) {
            $this->drop_address_id = (int) $value;
            $this->drop_address = $picked['full'];
            $this->drop_region_id = $picked['region_id'];
            $this->seedDropRegionPickers();
        }
    }

    /** Typing an address unlinks it from the saved one it was copied from. */
    public function updatedDropAddress(): void
    {
        if ($this->drop_address_choice !== 'custom') {
            return;
        }

        $this->drop_address_id = null;
    }

    public function updatedPickupAddressChoice(string $value): void
    {
        if ($value === 'custom') {
            // Switching to custom: unlink the saved address; keep any typed text.
            $this->pickup_address_id = null;

            return;
        }

        $picked = $this->customerAddresses->firstWhere('id', (int) $value);
        if ($picked) {
            $this->pickup_address_id = (int) $value;
            $this->pickup_address = $picked['full'];
            $this->pickup_region_id = $picked['region_id'];
            $this->seedRegionPickers('pickup');
        }
    }

    /**
     * Cancelling is the one thing about a booking nobody can observe from the
     * workshop floor, so it stays a deliberate act — but it is a flag with a
     * reason, not a status somebody picks off a list.
     */
    public function confirmCancel(): void
    {
        Flux::modal('cancel-appointment')->show();
    }

    public function cancelAppointment(): void
    {
        $this->authorize('appointment.update');

        $this->validate([
            'cancel_reason_id' => ['required', 'integer', Rule::exists('cancel_reasons', 'id')->where('is_active', true)],
        ], attributes: ['cancel_reason_id' => 'cancel reason']);

        $appointment = Appointment::findOrFail($this->editingId);
        $appointment->forceFill([
            'cancelled_at' => now(),
            'cancel_reason_id' => $this->cancel_reason_id,
        ])->save();

        $this->cancelled_at = $appointment->cancelled_at->toDateTimeString();
        $this->status = $appointment->fresh()->status;

        Flux::modal('cancel-appointment')->close();
        Flux::toast(text: 'Appointment '.$appointment->appointment_no.' cancelled.', variant: 'success');
    }

    /** Undo a cancellation; the ladder re-derives from wherever the car actually is. */
    public function restoreAppointment(): void
    {
        $this->authorize('appointment.update');

        $appointment = Appointment::findOrFail($this->editingId);
        $appointment->forceFill(['cancelled_at' => null, 'cancel_reason_id' => null])->save();

        $this->cancelled_at = null;
        $this->cancel_reason_id = null;
        $this->status = $appointment->fresh()->status;

        Flux::toast(text: 'Appointment restored.', variant: 'success');
    }

    public function createPendingReason(): void
    {
        if ($this->quickCreate(PendingReasonMaster::class, 'pending_reason_id', 'pendingReasonSearch', 'pending_reason_master.create', label: 'Pending Reason')) {
            unset($this->pendingReasons);
        } else {
            Flux::toast(text: 'Type the new reason into the picker first, then hit +.', variant: 'warning');
        }
    }

    /** Required by CanQuickAddCustomer — receives the new customer's id. */
    protected function quickCustomerTargetProperty(): string
    {
        return 'customer_id';
    }

    #[Computed]
    public function customers()
    {
        // Server-side search: the customer master is ~9.7k rows, so a fixed
        // client-side slice would hide everyone past the first page. The
        // currently selected customer is always retained so the edit form
        // renders its label even when it falls outside the search results.
        return $this->pickerOptions(
            query: CustomerMaster::query()
                ->where('is_active', true)
                ->orderBy('first_name'),
            searchColumns: ['first_name', 'last_name', 'phone'],
            term: $this->customerSearch,
            selected: $this->customer_id,
            columns: ['id', 'first_name', 'last_name', 'phone'],
            limit: 30,
        );
    }

    #[Computed]
    public function customerVehicles()
    {
        // Scoped to the chosen customer when one is set; otherwise a global,
        // server-searchable list so a vehicle can be picked first (owner is then
        // derived). Always includes the current selection.
        $query = CustomerVehicleMaster::query()
            ->with(['model.brand'])
            ->where('is_active', true)
            ->when($this->customer_id, fn ($q) => $q->where('customer_id', $this->customer_id))
            ->orderBy('registration_no');

        return $this->pickerOptions(
            query: $query,
            searchColumns: ['registration_no'],
            term: $this->vehicleSearch,
            selected: $this->customer_vehicle_id,
            columns: ['id', 'registration_no', 'model_id', 'customer_id'],
            limit: 30,
        )->map(fn ($v) => [
            'id' => $v->id,
            'label' => trim(($v->model?->brand?->name ?? '').' '.($v->model?->name ?? '')).' — '.$v->registration_no,
        ]);
    }

    /**
     * Service types offered by the chosen department.
     *
     * `service_types.workshop_department_id` is a real link, so this filters
     * strictly — nothing from another department is offered, and nothing at all
     * until a department is picked.
     */
    #[Computed]
    public function serviceTypes()
    {
        $ids = $this->selectedDepartmentIdInts();

        if ($ids === []) {
            return collect();
        }

        return ServiceTypeMaster::query()
            ->where('is_active', true)
            ->whereIn('workshop_department_id', $ids)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function bookingChannels()
    {
        return BookingChannelMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function priorities()
    {
        return PriorityMaster::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']);
    }

    #[Computed]
    public function pickupDropOptions()
    {
        return PickupDropOptionMaster::query()->where('is_active', true)->orderBy('name')
            ->get(['id', 'name', 'involves_pickup', 'involves_drop']);
    }

    #[Computed]
    public function cancelReasons()
    {
        return CancelReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function pendingReasons()
    {
        return PendingReasonMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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

    /**
     * Active slots with how many vehicles are already booked on the chosen date.
     *
     * Both legs count against the same capacity: a collection and a return each
     * consume a driver and a vehicle movement in that window, so counting only
     * pickups let a slot with twenty drops still read "5 left".
     *
     * Capacity is advisory — the form warns but still lets the advisor book.
     *
     * @return Collection<int, array{id:int, label:string, start:string, end:string, booked:int, pickups:int, drops:int, capacity:int, isFull:bool}>
     */
    #[Computed]
    public function timeSlots()
    {
        $slots = TimeSlotMaster::query()->where('is_active', true)->orderBy('slot_start_time')->get();

        if ($slots->isEmpty()) {
            return collect();
        }

        $onDate = fn () => Appointment::query()
            ->whereDate('appointment_at', $this->appointment_date ?: now()->toDateString())
            ->whereNotIn('status', [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW])
            ->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId));

        $pickups = $onDate()
            ->whereNotNull('time_slot_id')
            ->selectRaw('time_slot_id, count(*) as aggregate')
            ->groupBy('time_slot_id')
            ->pluck('aggregate', 'time_slot_id');

        $drops = $onDate()
            ->whereNotNull('drop_time_slot_id')
            ->selectRaw('drop_time_slot_id, count(*) as aggregate')
            ->groupBy('drop_time_slot_id')
            ->pluck('aggregate', 'drop_time_slot_id');

        return $slots->map(function (TimeSlotMaster $slot) use ($pickups, $drops) {
            $pickedUp = (int) ($pickups[$slot->id] ?? 0);
            $droppedOff = (int) ($drops[$slot->id] ?? 0);
            $booked = $pickedUp + $droppedOff;
            $capacity = (int) $slot->max_vehicles_per_slot;

            return [
                'id' => $slot->id,
                'label' => $slot->window(),
                // "H:i:s" on both drivers, so H:i strings compare correctly.
                'start' => substr((string) $slot->slot_start_time, 0, 5),
                'end' => substr((string) $slot->slot_end_time, 0, 5),
                'booked' => $booked,
                'pickups' => $pickedUp,
                'drops' => $droppedOff,
                'capacity' => $capacity,
                'isFull' => $booked >= $capacity,
            ];
        });
    }

    /**
     * Advisory warnings shown above the form — a full slot or a non-working day
     * never blocks the booking, it just makes the advisor aware.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function schedulingWarnings(): array
    {
        $warnings = [];

        if ($this->appointment_date !== '') {
            $date = Carbon::parse($this->appointment_date);

            $holiday = HolidayMaster::query()
                ->where('is_active', true)
                ->where(fn ($q) => $q
                    ->whereDate('holiday_date', $date)
                    // Recurring holidays repeat on the same day each year.
                    ->orWhere(fn ($r) => $r->where('is_recurring', true)
                        ->whereMonth('holiday_date', $date->month)
                        ->whereDay('holiday_date', $date->day)))
                ->first();

            if ($holiday) {
                $warnings[] = $date->format('d/m/Y').' is a non-working day ('.$holiday->name.').';
            }
        }

        return $warnings;
    }

    /** H:i is what the comparisons use; h:i A is what anyone reads. */
    protected function clockTime(string $time): string
    {
        return Carbon::createFromFormat('H:i', $time)->format('h:i A');
    }

    /**
     * Slot problems, keyed by the leg that caused them.
     *
     * These render under their own picker rather than in the callout at the top
     * of the form: the slots sit several sections below it, so a warning up
     * there is out of sight exactly when it matters.
     *
     * @return array{pickup: array<int, string>, drop: array<int, string>}
     */
    #[Computed]
    public function slotWarnings(): array
    {
        $warnings = ['pickup' => [], 'drop' => []];

        $legs = [
            'pickup' => $this->time_slot_id ? $this->timeSlots->firstWhere('id', $this->time_slot_id) : null,
            'drop' => $this->drop_time_slot_id ? $this->timeSlots->firstWhere('id', $this->drop_time_slot_id) : null,
        ];

        foreach ($legs as $leg => $slot) {
            if (! $slot) {
                continue;
            }

            if ($slot['isFull']) {
                $warnings[$leg][] = 'At capacity — '.$slot['booked'].' of '.$slot['capacity'].' vehicles already booked.';
            }

            if ($this->appointment_time === '') {
                continue;
            }

            // The car cannot be at the workshop before the driver has finished
            // collecting it, and returning it before the appointment makes no sense.
            if ($leg === 'pickup' && $slot['end'] > $this->appointment_time) {
                $warnings[$leg][] = 'Collection ends '.$this->clockTime($slot['end']).', after the '.$this->clockTime($this->appointment_time).' appointment.';
            }

            if ($leg === 'drop' && $slot['start'] < $this->appointment_time) {
                $warnings[$leg][] = 'Return starts '.$this->clockTime($slot['start']).', before the '.$this->clockTime($this->appointment_time).' appointment.';
            }
        }

        return $warnings;
    }

    /**
     * The vehicle's day in the order it is meant to happen.
     *
     * Three times entered in two sections read as three unrelated fields; laid
     * out in sequence the relationship explains itself, and a step whose clock
     * time contradicts its position is flagged where it stands.
     *
     * @return array<int, array{label: string, time: string, ok: bool}>
     */
    #[Computed]
    public function scheduleTimeline(): array
    {
        if ($this->appointment_time === '') {
            return [];
        }

        $steps = [];

        $pickup = $this->optionInvolvesPickup() && $this->time_slot_id
            ? $this->timeSlots->firstWhere('id', $this->time_slot_id)
            : null;

        $drop = $this->optionInvolvesDrop() && $this->drop_time_slot_id
            ? $this->timeSlots->firstWhere('id', $this->drop_time_slot_id)
            : null;

        if ($pickup) {
            $steps[] = [
                'label' => 'Collect from customer',
                'time' => $pickup['label'],
                'ok' => $pickup['end'] <= $this->appointment_time,
            ];
        }

        $steps[] = [
            'label' => 'At workshop',
            'time' => $this->clockTime($this->appointment_time),
            'ok' => true,
        ];

        if ($drop) {
            $steps[] = [
                'label' => 'Return to customer',
                'time' => $drop['label'],
                'ok' => $drop['start'] >= $this->appointment_time,
            ];
        }

        // A single step is not a sequence and explains nothing.
        return count($steps) > 1 ? $steps : [];
    }

    public function addComplaint(): void
    {
        $this->complaints[] = [
            'id' => null,
            'complaint_type_id' => null,
            'job_description_id' => null,
            'description' => '',
        ];
    }

    public function removeComplaint(int $index): void
    {
        unset($this->complaints[$index]);
        $this->complaints = array_values($this->complaints);
    }

    #[Computed]
    public function workshopDepartments()
    {
        return WorkshopDepartmentMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Everything this booking has spawned, as links.
     *
     * The form only ever offered to create a job card or a pickup/drop; it never
     * showed what already existed, so the links ran forward and never back. All
     * three are real foreign keys — `pickup_drops.appointment_id`,
     * `job_cards.appointment_id` and now `gate_visits.appointment_id`.
     *
     * @return array<int, array{type: string, label: string, meta: ?string, url: string}>
     */
    #[Computed]
    public function linkedRecords(): array
    {
        if (! $this->editingId) {
            return [];
        }

        $appointment = Appointment::with([
            'pickupDrops:id,appointment_id,pickup_drop_no,status',
            'jobCards:id,appointment_id,job_card_no',
            'gateVisits:id,appointment_id,gate_event_no,entered_at',
        ])->find($this->editingId);

        if (! $appointment) {
            return [];
        }

        $links = [];

        foreach ($appointment->pickupDrops as $leg) {
            $links[] = [
                'type' => 'Pickup / Drop',
                'label' => $leg->pickup_drop_no ?? ('#'.$leg->id),
                'meta' => $leg->status ? str_replace('_', ' ', $leg->status) : null,
                'url' => route('pickup-drop.edit', $leg),
            ];
        }

        foreach ($appointment->jobCards as $card) {
            $links[] = [
                'type' => 'Job Card',
                'label' => $card->job_card_no ?? ('#'.$card->id),
                'meta' => null,
                'url' => route('job-card.edit', $card),
            ];
        }

        foreach ($appointment->gateVisits as $visit) {
            $links[] = [
                'type' => 'Inward',
                'label' => $visit->gate_event_no ?? ('#'.$visit->id),
                'meta' => $visit->entered_at?->format('d/m/Y h:i A'),
                'url' => route('gate-in-out.edit', $visit),
            ];
        }

        return $links;
    }

    /**
     * Advisors and technicians for the picked departments.
     *
     * `workshop_departments.department_id` links a workshop department to the HR
     * department its staff belong to, so this is a real join rather than a name
     * comparison — the same filter the job card uses.
     *
     * A department with nobody mapped falls back to every advisor / technician
     * rather than an empty list: Advisor is required, and five of the eight
     * departments currently have no staff mapped at all, so a hard filter would
     * make them unbookable. `fellBack` lets the form say so instead of quietly
     * offering people from elsewhere.
     *
     * @return array{advisors: Collection, technicians: Collection, fellBack: bool}
     */
    #[Computed]
    public function employeesByDepartment(): array
    {
        $designation = fn ($e) => mb_strtoupper((string) $e->designation?->name);

        $staff = EmployeeMaster::query()
            ->where('is_active', true)
            ->with('designation:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'designation_id', 'department_id']);

        $advisors = $staff->filter(fn ($e) => str_contains($designation($e), 'ADVISOR'))->values();
        $technicians = $staff->filter(fn ($e) => $designation($e) === 'TECHNICIAN')->values();

        $hrDepartmentIds = WorkshopDepartmentMaster::query()
            ->whereIn('id', $this->selectedDepartmentIdInts())
            ->pluck('department_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($hrDepartmentIds === []) {
            return ['advisors' => collect(), 'technicians' => collect(), 'fellBack' => false];
        }

        $scopeToDepartment = fn (Collection $people) => $people
            ->filter(fn ($e) => in_array((int) $e->department_id, $hrDepartmentIds, true))
            ->values();

        $scopedAdvisors = $scopeToDepartment($advisors);
        $scopedTechnicians = $scopeToDepartment($technicians);

        return [
            'advisors' => $scopedAdvisors->isNotEmpty() ? $scopedAdvisors : $advisors,
            'technicians' => $scopedTechnicians->isNotEmpty() ? $scopedTechnicians : $technicians,
            'fellBack' => $scopedAdvisors->isEmpty() || $scopedTechnicians->isEmpty(),
        ];
    }

    /**
     * Saved addresses for the picked customer — used by the pickup address picker.
     * Returns an empty collection if no customer is selected.
     *
     * @return Collection<int, array{id:int, label:?string, full:string, is_primary:bool}>
     */
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
            ->orderBy('id')
            ->get()
            ->map(fn ($addr) => [
                'id' => $addr->id,
                'label' => $addr->label,
                'is_primary' => (bool) $addr->is_primary,
                'region_id' => $addr->region_id,
                'full' => trim(($addr->address_line ?? '').($addr->regionChain() ? ', '.$addr->regionChain() : '')),
            ]);
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'appointment.update' : 'appointment.create');

        $data = $this->validate();

        // Combine the two pickers into the single datetime column the schema persists.
        $data['appointment_at'] = Carbon::parse($data['appointment_date'].' '.$data['appointment_time'].':00');
        unset($data['appointment_date'], $data['appointment_time']);

        // Line items are written to their own table, never onto the parent row.
        $complaints = $data['complaints'] ?? [];
        unset($data['complaints']);

        // A saved address is stored as a live link (FK), not a stale text snapshot;
        // a custom one keeps the free text and no link.
        if (is_numeric($this->pickup_address_choice)) {
            $data['pickup_address_id'] = (int) $this->pickup_address_choice;
            $data['pickup_address'] = null;
        } else {
            $data['pickup_address_id'] = null;
        }

        foreach (['pickup_address', 'notes', 'customer_note'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        // Status is derived on save from what has happened to the vehicle, so the
        // form never sends one. A cancel reason only means anything alongside the
        // cancellation itself, which is its own action.
        unset($data['status']);

        // The selection is the input; the single FK stays the primary for every
        // downstream reader (job-card handoff, routing, filters).
        $departmentIds = array_map('intval', $data['department_ids']);
        unset($data['department_ids']);
        $data['workshop_department_id'] = $departmentIds[0] ?? null;
        if ($this->cancelled_at === null) {
            $data['cancel_reason_id'] = null;
        }

        $isCreate = $this->editingId === null;

        $appointment = DB::transaction(function () use ($data, $complaints, $isCreate, $departmentIds) {
            if ($isCreate) {
                $appointment = Appointment::create($data);
            } else {
                $appointment = Appointment::findOrFail($this->editingId);

                // Moving a booked appointment keeps the slot it came from.
                if ($appointment->appointment_at?->ne($data['appointment_at'])) {
                    $data['rescheduled_from_at'] = $appointment->appointment_at;
                }

                $appointment->update($data);
            }

            $this->syncComplaints($appointment, $complaints);
            $this->syncSelectedServices($appointment);
            $appointment->workshopDepartments()->sync($departmentIds);

            return $appointment;
        });

        if ($isCreate) {
            $this->editingId = $appointment->id;
            $this->appointment_no = $appointment->fresh()->appointment_no;
        }

        Flux::toast(
            text: 'Appointment '.$appointment->appointment_no.($isCreate ? ' created.' : ' updated.'),
            variant: 'success',
        );

        return redirect()->route('appointment.index');
    }

    /**
     * Upsert the complaint lines and drop any the advisor removed.
     *
     * @param  array<int, array{id:?int, complaint_type_id:?int, job_description_id:?int, description:string}>  $complaints
     */
    protected function syncComplaints(Appointment $appointment, array $complaints): void
    {
        $keptIds = [];

        foreach (array_values($complaints) as $i => $line) {
            $payload = [
                'complaint_type_id' => $line['complaint_type_id'] ?: null,
                'job_description_id' => $line['job_description_id'] ?: null,
                'description' => strtoupper(trim($line['description'])),
                'sequence_no' => $i + 1,
            ];

            $row = ChildRows::upsert($appointment->complaints(), $line['id'] ?? null,
                $payload,
            );

            $keptIds[] = $row->id;
        }

        $appointment->complaints()->whereKeyNot($keptIds)->delete();
    }

    public function render()
    {
        return view('appointment::edit');
    }
}
