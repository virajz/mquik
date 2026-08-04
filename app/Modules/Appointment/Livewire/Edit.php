<?php

namespace App\Modules\Appointment\Livewire;

use App\Concerns\CanQuickAddCustomer;
use App\Concerns\SearchesPickerOptions;
use App\Modules\Appointment\Models\Appointment;
use App\Modules\BookingChannelMaster\Models\BookingChannelMaster;
use App\Modules\CancelReasonMaster\Models\CancelReasonMaster;
use App\Modules\ComplaintTypeMaster\Models\ComplaintTypeMaster;
use App\Modules\CustomerMaster\Models\CustomerAddress;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\HolidayMaster\Models\HolidayMaster;
use App\Modules\JobDescriptionMaster\Models\JobDescriptionMaster;
use App\Modules\PendingReasonMaster\Models\PendingReasonMaster;
use App\Modules\PickupDropOptionMaster\Models\PickupDropOptionMaster;
use App\Modules\PriorityMaster\Models\PriorityMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\TimeSlotMaster\Models\TimeSlotMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
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
    use SearchesPickerOptions;

    public ?int $editingId = null;

    /** Display-only — generated server-side after first save. */
    public ?string $appointment_no = null;

    /** Date portion (Y-m-d) — combined with appointment_time on save. */
    public string $appointment_date = '';

    /** Time portion (H:i) — combined with appointment_date on save. */
    public string $appointment_time = '';

    public ?int $time_slot_id = null;

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

    public ?string $pickup_contact_phone = null;

    public string $status = Appointment::STATUS_PENDING;

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
        $this->booking_channel_id = $a->booking_channel_id;
        $this->priority_id = $a->priority_id;
        $this->customer_id = $a->customer_id;
        $this->customer_vehicle_id = $a->customer_vehicle_id;
        $this->service_type_id = $a->service_type_id;
        $this->workshop_department_id = $a->workshop_department_id;
        $this->assigned_advisor_id = $a->assigned_advisor_id;
        $this->assigned_technician_id = $a->assigned_technician_id;
        $this->pickup_drop_option_id = $a->pickup_drop_option_id;
        $this->pickup_address = $a->pickup_address;
        $this->pickup_address_id = $a->pickup_address_id;
        $this->pickup_contact_phone = $a->pickup_contact_phone;
        $this->status = $a->status;
        $this->cancel_reason_id = $a->cancel_reason_id;
        $this->pending_reason_id = $a->pending_reason_id;
        $this->notes = $a->notes;

        $this->complaints = $a->complaints()->get()->map(fn ($c) => [
            'id' => $c->id,
            'complaint_type_id' => $c->complaint_type_id,
            'job_description_id' => $c->job_description_id,
            'description' => $c->description,
        ])->all();
        // Reflect a linked saved address in the picker; otherwise treat it as custom text.
        $this->pickup_address_choice = $a->pickup_address_id ? (string) $a->pickup_address_id : 'custom';
        if ($a->pickup_address_id) {
            $this->pickup_address = $a->pickupAddress?->fullAddress();
        }
    }

    protected function rules(): array
    {
        return [
            'appointment_date' => ['required', 'date_format:Y-m-d'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'time_slot_id' => ['nullable', 'integer', Rule::exists('time_slots', 'id')->where('is_active', true)],
            'booking_channel_id' => ['required', 'integer', Rule::exists('booking_channels', 'id')->where('is_active', true)],
            'priority_id' => ['nullable', 'integer', Rule::exists('priorities', 'id')->where('is_active', true)],
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('is_active', true)],
            'customer_vehicle_id' => [
                'required', 'integer',
                Rule::exists('customer_vehicles', 'id')->where(fn ($q) => $q->where('customer_id', $this->customer_id)->where('is_active', true)),
            ],
            'service_type_id' => ['nullable', 'integer', Rule::exists('service_types', 'id')->where('is_active', true)],
            'workshop_department_id' => ['required', 'integer', Rule::exists('workshop_departments', 'id')->where('is_active', true)],
            'assigned_advisor_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'assigned_technician_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'pickup_drop_option_id' => ['required', 'integer', Rule::exists('pickup_drop_options', 'id')->where('is_active', true)],
            // Address only matters when the workshop is the one moving the vehicle.
            'pickup_address' => [Rule::requiredIf(fn () => $this->optionInvolvesPickup() && $this->pickup_address_choice === 'custom'), 'nullable', 'string', 'max:1000'],
            'pickup_address_id' => ['nullable', 'integer', Rule::exists('customer_addresses', 'id')],
            'pickup_contact_phone' => ['nullable', 'string', 'min:10', 'max:20'],
            'status' => ['required', Rule::in(array_keys(Appointment::statuses()))],
            'cancel_reason_id' => [
                Rule::requiredIf(fn () => $this->status === Appointment::STATUS_CANCELLED),
                'nullable', 'integer',
                Rule::exists('cancel_reasons', 'id')->where('is_active', true),
            ],
            'pending_reason_id' => ['nullable', 'integer', Rule::exists('pending_reasons', 'id')->where('is_active', true)],
            'complaints' => ['array'],
            'complaints.*.complaint_type_id' => ['nullable', 'integer', Rule::exists('complaint_types', 'id')->where('is_active', true)],
            'complaints.*.job_description_id' => ['nullable', 'integer', Rule::exists('job_descriptions', 'id')->where('is_active', true)],
            'complaints.*.description' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'booking_channel_id' => 'booking channel',
            'pickup_drop_option_id' => 'pickup/drop option',
            'cancel_reason_id' => 'cancel reason',
            'time_slot_id' => 'time slot',
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

    /** True when the picked option means the workshop collects the vehicle. */
    public function optionInvolvesPickup(): bool
    {
        if (! $this->pickup_drop_option_id) {
            return false;
        }

        return (bool) PickupDropOptionMaster::find($this->pickup_drop_option_id)?->involves_pickup;
    }

    public function updatedPickupDropOptionId(): void
    {
        if (! $this->optionInvolvesPickup()) {
            $this->pickup_address = null;
            $this->pickup_address_id = null;
            $this->pickup_contact_phone = null;
            $this->pickup_address_choice = 'custom';

            return;
        }

        // If the customer has saved addresses, default to the primary one (linked live).
        $primary = $this->customerAddresses->firstWhere('is_primary', true) ?? $this->customerAddresses->first();
        if ($primary) {
            $this->pickup_address_choice = (string) $primary['id'];
            $this->pickup_address_id = (int) $primary['id'];
            $this->pickup_address = $primary['full'];
        }
        // The contact phone defaults to the customer's live phone — no stale snapshot;
        // leave it null and let resolvedContactPhone() supply it unless the user overrides.
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

    #[Computed]
    public function serviceTypes()
    {
        return ServiceTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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

        foreach (['pickup_address', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        // Reasons only belong to the state that asks for them.
        if ($this->status !== Appointment::STATUS_CANCELLED) {
            $data['cancel_reason_id'] = null;
        }
        if ($this->status !== Appointment::STATUS_PENDING) {
            $data['pending_reason_id'] = null;
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

            $row = $appointment->complaints()->updateOrCreate(
                ['id' => $line['id'] ?? null],
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
