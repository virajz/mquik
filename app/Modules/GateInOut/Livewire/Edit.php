<?php

namespace App\Modules\GateInOut\Livewire;

use App\Concerns\CanQuickAddCustomerVehicle;
use App\Concerns\SearchesPickerOptions;
use App\Modules\Appointment\Models\Appointment;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\GateInOut\Models\GateInOut;
use App\Modules\GateInOut\Models\GateVisitMovement;
use App\Modules\GateMaster\Models\GateMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\ParkingSlotMaster\Models\ParkingSlotMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Gate Visit')]
class Edit extends Component
{
    use CanQuickAddCustomerVehicle;
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    // Inward leg.
    public string $entered_date = '';

    public string $entered_time = '';

    public ?int $entry_gate_id = null;

    public ?int $parking_slot_id = null;

    public string $registration_no = '';

    public ?int $customer_vehicle_id = null;

    public ?int $customer_id = null;

    public ?int $job_card_id = null;

    /** The booking this arrival fulfils, when the car came in against one. */
    public ?int $appointment_id = null;

    // Outward leg — left blank while the vehicle is still on site.
    public ?string $exited_date = null;

    public ?string $exited_time = null;

    public ?int $exit_gate_id = null;

    public ?string $outward_type = null;

    public ?string $driver_type = null;

    public ?int $delivered_by_id = null;

    public ?int $exit_by_id = null;

    /** Derived, never edited — pending until the delivery stamp lands. */
    public string $status = GateInOut::STATUS_PENDING;

    /** Photo taken at the gate (vehicle at the barrier). */
    public $capturedImage = null;

    public ?string $captured_image_path = null;

    public string $source = GateInOut::SOURCE_MANUAL;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            // Stamped when the record is created and never edited afterwards —
            // the moment a car arrived is a fact, not a preference.
            'entered_date' => ['required', 'date_format:Y-m-d'],
            'entered_time' => ['required', 'date_format:H:i'],
            'entry_gate_id' => ['required', 'integer', Rule::exists('gates', 'id')->where('is_active', true)],
            'parking_slot_id' => ['nullable', 'integer', Rule::exists('parking_slots', 'id')->where('is_active', true)],
            'registration_no' => ['required', 'string', 'max:20'],
            'job_card_id' => ['nullable', 'integer', 'exists:job_cards,id'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            // Both halves of the exit timestamp travel together or not at all.
            'exited_date' => ['nullable', 'date_format:Y-m-d', 'required_with:exited_time'],
            'exited_time' => ['nullable', 'date_format:H:i', 'required_with:exited_date'],
            'exit_gate_id' => ['nullable', 'integer', Rule::exists('gates', 'id')->where('is_active', true)],
            // Not asked for any more: the only departure that ends a visit is the
            // final delivery. Trial runs and vendor trips are movements.
            'outward_type' => ['nullable', Rule::in(array_keys(GateInOut::outwardTypes()))],
            'driver_type' => ['nullable', Rule::in(array_keys(GateInOut::driverTypes()))],
            'delivered_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'exit_by_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],

            'source' => ['required', Rule::in(array_keys(GateInOut::sources()))],
            'notes' => ['nullable', 'string', 'max:1000'],
            'capturedImage' => ['nullable', 'image', 'max:8192'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'entered_date' => 'entry date',
            'entered_time' => 'entry time',
            'exited_date' => 'exit date',
            'exited_time' => 'exit time',
        ];
    }

    public function mount(?GateInOut $gateInOut = null): void
    {
        $now = now();
        $this->entered_date = $now->format('Y-m-d');
        $this->entered_time = $now->format('H:i');

        // Cars come in through gate 1 and leave through gate 2 — pre-picked so
        // the guard only changes it on the exception.
        $this->entry_gate_id = $this->defaultGateId('GATE NO. 1');

        if ($gateInOut && $gateInOut->exists) {
            $this->load($gateInOut);
        }

        if ($this->exit_gate_id === null) {
            $this->exit_gate_id = $this->defaultGateId('GATE NO. 2');
        }
    }

    protected function defaultGateId(string $name): ?int
    {
        return GateMaster::query()->where('is_active', true)->where('name', $name)->value('id')
            ?? GateMaster::query()->where('is_active', true)->orderBy('id')->value('id');
    }

    protected function load(GateInOut $r): void
    {
        $this->editingId = $r->id;
        $this->entered_date = $r->entered_at?->format('Y-m-d') ?? $this->entered_date;
        $this->entered_time = $r->entered_at?->format('H:i') ?? $this->entered_time;
        $this->entry_gate_id = $r->entry_gate_id;
        $this->parking_slot_id = $r->parking_slot_id;
        $this->registration_no = $r->registration_no;
        $this->captured_image_path = $r->captured_image_path;
        $this->customer_vehicle_id = $r->customer_vehicle_id;
        $this->customer_id = $r->customer_id;
        $this->job_card_id = $r->job_card_id;
        $this->appointment_id = $r->appointment_id;
        $this->exited_date = $r->exited_at?->format('Y-m-d');
        $this->exited_time = $r->exited_at?->format('H:i');
        $this->exit_gate_id = $r->exit_gate_id;
        $this->outward_type = $r->outward_type;
        $this->driver_type = $r->driver_type;
        $this->delivered_by_id = $r->delivered_by_id;
        $this->exit_by_id = $r->exit_by_id;
        $this->status = $r->status ?? GateInOut::STATUS_PENDING;
        $this->source = $r->source ?? GateInOut::SOURCE_MANUAL;
        $this->notes = $r->notes;
    }

    /** Search term for the vehicle picker. */
    public string $vehicleSearch = '';

    // --- "send out" form state ---
    public ?string $tripPurpose = GateVisitMovement::PURPOSE_TRIAL_RUN;

    public ?int $tripJobCardId = null;

    public ?int $tripVendorId = null;

    public ?int $tripDriverId = null;

    public ?string $tripExpectedBackDate = null;

    public ?string $tripExpectedBackTime = null;

    public ?int $tripOdometerOut = null;

    public ?string $tripNotes = null;

    /**
     * Known vehicles to pick from — typing a plate every time is both slow and
     * how walk-in duplicates get created.
     */
    /** The quick-add drops the new vehicle straight into this picker. */
    protected function quickCustomerVehicleTargetProperty(): string
    {
        return 'customer_vehicle_id';
    }

    #[Computed]
    public function vehicleOptions()
    {
        return $this->pickerOptions(
            query: CustomerVehicleMaster::query()->where('is_active', true)
                ->with(['customer:id,first_name,last_name', 'model:id,name,brand_id', 'model.brand:id,name']),
            searchColumns: ['registration_no', 'customer.first_name', 'customer.last_name', 'customer.phone'],
            term: $this->vehicleSearch,
            selected: $this->customer_vehicle_id,
            columns: ['id', 'registration_no', 'customer_id', 'model_id'],
            limit: 25,
        );
    }

    /** Picking a vehicle fills the plate and the customer. */
    public function updatedCustomerVehicleId($value): void
    {
        if (! $value) {
            $this->appointment_id = null;
            unset($this->openAppointments);

            return;
        }

        $vehicle = CustomerVehicleMaster::find($value);
        if ($vehicle) {
            $this->registration_no = $vehicle->registration_no;
            $this->customer_id = $vehicle->customer_id;
        }

        $this->suggestAppointment();
    }

    /**
     * Unfinished bookings for the vehicle at the barrier, nearest first.
     *
     * @return Collection<int, Appointment>
     */
    #[Computed]
    public function openAppointments()
    {
        if (! $this->customer_vehicle_id) {
            return collect();
        }

        return Appointment::query()
            ->where('customer_vehicle_id', $this->customer_vehicle_id)
            ->whereNull('cancelled_at')
            ->whereIn('status', Appointment::pendingStatuses())
            // The one it is standing in front of is the one it is nearest to.
            ->orderByRaw('abs(julianday(appointment_at) - julianday(?))', [now()->toDateTimeString()])
            ->when(
                DB::connection()->getDriverName() === 'pgsql',
                fn ($q) => $q->reorder()->orderByRaw('abs(extract(epoch from (appointment_at - ?)))', [now()->toDateTimeString()]),
            )
            ->with('serviceType:id,name')
            ->limit(10)
            ->get();
    }

    /**
     * Fill the booking in when there is only one it could be.
     *
     * Guessing between several would be the vehicle-matching heuristic all over
     * again, so more than one is left for the guard to choose.
     */
    protected function suggestAppointment(): void
    {
        unset($this->openAppointments);

        if ($this->appointment_id) {
            return;
        }

        $open = $this->openAppointments;

        $this->appointment_id = $open->count() === 1 ? $open->first()->id : null;
    }

    /**
     * As soon as the user types a registration number, try to resolve it to an
     * existing CustomerVehicle so we can stamp the FK and link the customer.
     */
    public function updatedRegistrationNo(string $value): void
    {
        $normalised = strtoupper(preg_replace('/\s+/', ' ', trim($value)));
        if ($normalised === '') {
            $this->customer_vehicle_id = null;
            $this->customer_id = null;

            return;
        }

        $vehicle = CustomerVehicleMaster::query()
            ->where('registration_no', $normalised)
            ->first();

        $this->customer_vehicle_id = $vehicle?->id;
        $this->customer_id = $vehicle?->customer_id;

        $this->suggestAppointment();
    }

    /**
     * Trips taken during this visit — out for a trial run, to a vendor, back.
     *
     * @return Collection<int, GateVisitMovement>
     */
    #[Computed]
    public function movements()
    {
        if (! $this->editingId) {
            return collect();
        }

        return GateVisitMovement::query()
            ->where('gate_visit_id', $this->editingId)
            ->with(['jobCard:id,job_card_no', 'vendor:id,name', 'driver:id,name'])
            ->orderByDesc('out_at')
            ->get();
    }

    /** True while the car is away on a trip — it is not an outward. */
    #[Computed]
    public function isOffSite(): bool
    {
        return $this->movements->contains(fn (GateVisitMovement $m) => $m->isOut());
    }

    /** Send the vehicle out on a trip. */
    public function sendOut(): void
    {
        $this->authorize('gate_in_out.update');

        if (! $this->editingId) {
            Flux::toast(text: 'Save the inward first.', variant: 'warning');

            return;
        }

        if ($this->isOffSite) {
            Flux::toast(text: 'The vehicle is already out — bring it back in first.', variant: 'warning');

            return;
        }

        $data = $this->validate([
            'tripPurpose' => ['required', Rule::in(array_keys(GateVisitMovement::purposes()))],
            'tripJobCardId' => ['nullable', 'integer', Rule::exists('job_cards', 'id')],
            'tripVendorId' => ['nullable', 'integer', Rule::exists('vendors', 'id')],
            'tripDriverId' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'tripExpectedBackDate' => ['nullable', 'date'],
            'tripExpectedBackTime' => ['nullable', 'string'],
            'tripOdometerOut' => ['nullable', 'integer', 'min:0'],
            'tripNotes' => ['nullable', 'string', 'max:500'],
        ]);

        GateVisitMovement::create([
            'gate_visit_id' => $this->editingId,
            'job_card_id' => $data['tripJobCardId'] ?: null,
            'purpose' => $data['tripPurpose'],
            'vendor_id' => $data['tripVendorId'] ?: null,
            'driver_employee_id' => $data['tripDriverId'] ?: null,
            'out_at' => now(),
            'expected_back_at' => $this->expectedBackAt(),
            'odometer_out' => $data['tripOdometerOut'] ?: null,
            'notes' => $data['tripNotes'] ?: null,
        ]);

        $this->reset(['tripPurpose', 'tripJobCardId', 'tripVendorId', 'tripDriverId', 'tripExpectedBackDate', 'tripExpectedBackTime', 'tripOdometerOut', 'tripNotes']);
        unset($this->movements, $this->isOffSite);

        Flux::modal('send-out')->close();
        Flux::toast(text: 'Vehicle sent out.', variant: 'success');
    }

    /**
     * Combine the date and time pickers into one timestamp.
     *
     * A date on its own still means something ("back sometime Thursday"), so it
     * defaults to end of day rather than being thrown away.
     */
    protected function expectedBackAt(): ?Carbon
    {
        if (blank($this->tripExpectedBackDate)) {
            return null;
        }

        $time = filled($this->tripExpectedBackTime) ? $this->tripExpectedBackTime : '23:59';

        return Carbon::parse($this->tripExpectedBackDate.' '.$time);
    }

    /** Bring it back from a trip. */
    public function bringBack(int $movementId): void
    {
        $this->authorize('gate_in_out.update');

        GateVisitMovement::query()
            ->where('gate_visit_id', $this->editingId)
            ->whereKey($movementId)
            ->stillOut()
            ->update(['in_at' => now()]);

        unset($this->movements, $this->isOffSite);

        Flux::toast(text: 'Vehicle back on site.', variant: 'success');
    }

    #[Computed]
    public function vendors()
    {
        return VendorMaster::query()->where('is_active', true)->orderBy('name')->limit(50)->get(['id', 'name']);
    }

    /** "MARUTI SWIFT" for the linked vehicle — what the guard confirms visually. */
    #[Computed]
    public function linkedVehicleName(): ?string
    {
        if (! $this->customer_vehicle_id) {
            return null;
        }

        $vehicle = CustomerVehicleMaster::with('model.brand')->find($this->customer_vehicle_id);

        return $vehicle
            ? trim(($vehicle->model?->brand?->name ?? '').' '.($vehicle->model?->name ?? '')) ?: null
            : null;
    }

    #[Computed]
    public function gates()
    {
        return GateMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function parkingSlots()
    {
        return ParkingSlotMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function employees()
    {
        return EmployeeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    /** Only the people who hand cars over: advisors and cashiers. */
    #[Computed]
    public function deliveryStaff()
    {
        return EmployeeMaster::query()
            ->where('is_active', true)
            ->whereHas('designation', fn ($q) => $q->where('name', 'like', '%ADVISOR%')->orWhere('name', 'like', '%CASHIER%'))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /** Only the guards can let a car out. */
    #[Computed]
    public function securityGuards()
    {
        return EmployeeMaster::query()
            ->where('is_active', true)
            ->whereHas('designation', fn ($q) => $q->where('name', 'like', '%SECURITY%')->orWhere('name', 'like', '%GUARD%'))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function jobCards()
    {
        return JobCard::query()->orderByDesc('id')->limit(200)->get(['id', 'job_card_no']);
    }

    /** The saved exit stamp, if any — only markDelivered() ever writes it. */
    protected function exitedAt(): ?Carbon
    {
        $raw = $this->editingId ? GateInOut::whereKey($this->editingId)->value('exited_at') : null;

        return $raw ? Carbon::parse($raw) : null;
    }

    /**
     * The delivery is a moment, not a form field: everything the exit needs is
     * checked here, then the stamp is now() — backdating a departure is exactly
     * what a gate register exists to prevent.
     */
    public function markDelivered(): void
    {
        $this->authorize('gate_in_out.update');

        if (! $this->editingId) {
            return;
        }

        $this->resetErrorBag();
        $this->validate([
            'exit_gate_id' => ['required', 'integer', Rule::exists('gates', 'id')->where('is_active', true)],
            'driver_type' => ['required', Rule::in(array_keys(GateInOut::driverTypes()))],
            'delivered_by_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'exit_by_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
        ], attributes: [
            'exit_gate_id' => 'exit gate',
            'driver_type' => 'driver type',
            'delivered_by_id' => 'delivered by',
            'exit_by_id' => 'exit by (security guard)',
        ]);

        $row = GateInOut::findOrFail($this->editingId);
        $row->forceFill([
            'exited_at' => now(),
            'exit_gate_id' => $this->exit_gate_id,
            'driver_type' => $this->driver_type,
            'delivered_by_id' => $this->delivered_by_id,
            'exit_by_id' => $this->exit_by_id,
            'outward_type' => 'final_delivery',
            'status' => GateInOut::STATUS_COMPLETED,
        ])->save();

        $this->exited_date = $row->exited_at->format('Y-m-d');
        $this->exited_time = $row->exited_at->format('H:i');
        $this->status = GateInOut::STATUS_COMPLETED;

        Flux::toast(text: 'Vehicle delivered — visit '.$row->gate_event_no.' completed.', variant: 'success');
    }

    public function cancelVisit(): void
    {
        $this->authorize('gate_in_out.update');

        $row = GateInOut::findOrFail($this->editingId);
        $row->forceFill(['status' => GateInOut::STATUS_CANCELLED])->save();
        $this->status = GateInOut::STATUS_CANCELLED;

        Flux::toast(text: 'Visit '.$row->gate_event_no.' cancelled.', variant: 'success');
    }

    public function restoreVisit(): void
    {
        $this->authorize('gate_in_out.update');

        $row = GateInOut::findOrFail($this->editingId);
        $row->forceFill(['status' => $row->exited_at ? GateInOut::STATUS_COMPLETED : GateInOut::STATUS_PENDING])->save();
        $this->status = $row->fresh()->status;

        Flux::toast(text: 'Visit '.$row->gate_event_no.' restored.', variant: 'success');
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'gate_in_out.update' : 'gate_in_out.create');

        $data = $this->validate();

        $data['entered_at'] = Carbon::parse($data['entered_date'].' '.$data['entered_time'].':00');
        unset($data['entered_date'], $data['entered_time'], $data['exited_date'], $data['exited_time'], $data['capturedImage']);

        // The arrival stamp belongs to the record, not the form — an edit must
        // not be able to move it. The exit stamp is markDelivered()'s alone.
        if ($this->editingId) {
            unset($data['entered_at']);
        }

        // Status is derived: completed once the delivery stamp exists, pending
        // until then; a cancellation is its own deliberate action.
        if ($this->status !== GateInOut::STATUS_CANCELLED) {
            $data['status'] = $this->exitedAt() ? GateInOut::STATUS_COMPLETED : GateInOut::STATUS_PENDING;
        }

        if ($this->capturedImage) {
            $data['captured_image_path'] = $this->capturedImage->store('gate-visits', 'public');
        }

        $data['customer_vehicle_id'] = $this->customer_vehicle_id;
        $data['customer_id'] = $this->customer_id;
        $data['appointment_id'] = $this->appointment_id;
        $data['recorded_by_user_id'] = auth()->id();

        if ($this->editingId) {
            GateInOut::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Gate visit #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $row = GateInOut::create($data);
            Flux::toast(text: 'Gate visit '.$row->fresh()->gate_event_no.' recorded.', variant: 'success');
        }

        return redirect()->route('gate-in-out.index');
    }

    /**
     * Job cards raised off this visit.
     *
     * One inward can carry several — a vehicle in for a service and a separate
     * bodyshop job is still one arrival.
     */
    #[Computed]
    public function linkedJobCards()
    {
        if (! $this->editingId) {
            return collect();
        }

        return JobCard::query()
            ->where('gate_event_id', $this->editingId)
            ->orderByDesc('id')
            ->get(['id', 'job_card_no', 'status']);
    }

    public function render()
    {
        return view('gate-in-out::edit');
    }
}
