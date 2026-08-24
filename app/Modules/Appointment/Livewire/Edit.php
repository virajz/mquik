<?php

namespace App\Modules\Appointment\Livewire;

use App\Concerns\CanQuickAddCustomer;
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
                    ->where('workshop_department_id', $this->workshop_department_id),
            ],
            'workshop_department_id' => ['required', 'integer', Rule::exists('workshop_departments', 'id')->where('is_active', true)],
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

    /** Changing department invalidates a service type belonging to the old one. */
    public function updatedWorkshopDepartmentId(): void
    {
        $this->service_type_id = null;

        unset($this->serviceTypes, $this->frequentServiceGroups, $this->otherJobDescriptions);

        // The checklist is scoped to the department, so jobs picked for the old
        // one no longer belong on this booking.
        $this->dropServicesOutsideDepartment();
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
        if (! $this->workshop_department_id) {
            return collect();
        }

        return ServiceTypeMaster::query()
            ->where('is_active', true)
            ->where('workshop_department_id', $this->workshop_department_id)
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
     * Capacity is advisory — the form warns but still lets the advisor book.
     *
     * @return Collection<int, array{id:int, label:string, booked:int, capacity:int, isFull:bool}>
     */
    #[Computed]
    public function timeSlots()
    {
        $slots = TimeSlotMaster::query()->where('is_active', true)->orderBy('slot_start_time')->get();

        if ($slots->isEmpty()) {
            return collect();
        }

        $counts = Appointment::query()
            ->whereDate('appointment_at', $this->appointment_date ?: now()->toDateString())
            ->whereNotIn('status', [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW])
            ->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))
            ->selectRaw('time_slot_id, count(*) as aggregate')
            ->groupBy('time_slot_id')
            ->pluck('aggregate', 'time_slot_id');

        return $slots->map(function (TimeSlotMaster $slot) use ($counts) {
            $booked = (int) ($counts[$slot->id] ?? 0);
            $capacity = (int) $slot->max_vehicles_per_slot;

            return [
                'id' => $slot->id,
                'label' => $slot->window(),
                'booked' => $booked,
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
                $warnings[] = $date->format('d M Y').' is a non-working day ('.$holiday->name.').';
            }
        }

        if ($this->time_slot_id) {
            $slot = $this->timeSlots->firstWhere('id', $this->time_slot_id);

            if ($slot && $slot['isFull']) {
                $warnings[] = 'Slot '.$slot['label'].' is already at capacity ('.$slot['booked'].'/'.$slot['capacity'].' vehicles).';
            }
        }

        return $warnings;
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
        if ($this->cancelled_at === null) {
            $data['cancel_reason_id'] = null;
        }

        $isCreate = $this->editingId === null;

        $appointment = DB::transaction(function () use ($data, $complaints, $isCreate) {
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
