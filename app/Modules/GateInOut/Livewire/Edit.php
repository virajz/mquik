<?php

namespace App\Modules\GateInOut\Livewire;

use App\Concerns\CanQuickAddCustomerVehicle;
use App\Concerns\SearchesPickerOptions;
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
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Gate Visit')]
class Edit extends Component
{
    use CanQuickAddCustomerVehicle;
    use SearchesPickerOptions;

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

    // Outward leg — left blank while the vehicle is still on site.
    public ?string $exited_date = null;

    public ?string $exited_time = null;

    public ?int $exit_gate_id = null;

    public ?string $outward_type = null;

    public ?string $driver_type = null;

    public ?int $delivered_by_id = null;

    public ?int $exit_by_id = null;

    public string $status = GateInOut::STATUS_PENDING;

    public string $source = GateInOut::SOURCE_MANUAL;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            // Stamped when the record is created and never edited afterwards —
            // the moment a car arrived is a fact, not a preference.
            'entered_date' => ['required', 'date_format:Y-m-d'],
            'entered_time' => ['required', 'date_format:H:i'],
            'entry_gate_id' => ['nullable', 'integer', Rule::exists('gates', 'id')->where('is_active', true)],
            'parking_slot_id' => ['nullable', 'integer', Rule::exists('parking_slots', 'id')->where('is_active', true)],
            'registration_no' => ['required', 'string', 'max:20'],
            'job_card_id' => ['nullable', 'integer', 'exists:job_cards,id'],
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
            'status' => ['required', Rule::in(array_keys(GateInOut::statuses()))],
            'source' => ['required', Rule::in(array_keys(GateInOut::sources()))],
            'notes' => ['nullable', 'string', 'max:1000'],
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

        if ($gateInOut && $gateInOut->exists) {
            $this->load($gateInOut);
        }
    }

    protected function load(GateInOut $r): void
    {
        $this->editingId = $r->id;
        $this->entered_date = $r->entered_at?->format('Y-m-d') ?? $this->entered_date;
        $this->entered_time = $r->entered_at?->format('H:i') ?? $this->entered_time;
        $this->entry_gate_id = $r->entry_gate_id;
        $this->parking_slot_id = $r->parking_slot_id;
        $this->registration_no = $r->registration_no;
        $this->customer_vehicle_id = $r->customer_vehicle_id;
        $this->customer_id = $r->customer_id;
        $this->job_card_id = $r->job_card_id;
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
            query: CustomerVehicleMaster::query()->where('is_active', true)->with('customer:id,first_name,last_name'),
            searchColumns: ['registration_no', 'customer.first_name', 'customer.last_name', 'customer.phone'],
            term: $this->vehicleSearch,
            selected: $this->customer_vehicle_id,
            columns: ['id', 'registration_no', 'customer_id'],
            limit: 25,
        );
    }

    /** Picking a vehicle fills the plate and the customer. */
    public function updatedCustomerVehicleId($value): void
    {
        if (! $value) {
            return;
        }

        $vehicle = CustomerVehicleMaster::find($value);
        if ($vehicle) {
            $this->registration_no = $vehicle->registration_no;
            $this->customer_id = $vehicle->customer_id;
        }
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

    #[Computed]
    public function jobCards()
    {
        return JobCard::query()->orderByDesc('id')->limit(200)->get(['id', 'job_card_no']);
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'gate_in_out.update' : 'gate_in_out.create');

        $data = $this->validate();

        $data['entered_at'] = Carbon::parse($data['entered_date'].' '.$data['entered_time'].':00');
        $data['exited_at'] = $data['exited_date'] && $data['exited_time']
            ? Carbon::parse($data['exited_date'].' '.$data['exited_time'].':00')
            : null;
        unset($data['entered_date'], $data['entered_time'], $data['exited_date'], $data['exited_time']);

        if ($data['exited_at'] !== null && $data['exited_at']->lt($data['entered_at'])) {
            $this->addError('exited_date', 'The vehicle cannot leave before it arrived.');

            return;
        }

        // The arrival stamp belongs to the record, not the form — an edit must
        // not be able to move it.
        if ($this->editingId) {
            unset($data['entered_at']);
        }

        // Every departure that closes a visit is the delivery; the other reasons
        // a car leaves are movements, which do not end the visit.
        if ($data['exited_at'] !== null) {
            $data['outward_type'] = 'final_delivery';
        }

        $data['customer_vehicle_id'] = $this->customer_vehicle_id;
        $data['customer_id'] = $this->customer_id;
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
