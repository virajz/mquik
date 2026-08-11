<?php

namespace App\Modules\GateInOut\Livewire;

use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\GateInOut\Models\GateInOut;
use App\Modules\GateMaster\Models\GateMaster;
use App\Modules\JobCard\Models\JobCard;
use App\Modules\ParkingSlotMaster\Models\ParkingSlotMaster;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Gate Visit')]
class Edit extends Component
{
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

    /** The job card already raised off this visit, if any. */
    #[Computed]
    public function linkedJobCard()
    {
        if (! $this->editingId) {
            return null;
        }

        return JobCard::query()
            ->where('gate_event_id', $this->editingId)
            ->first(['id', 'job_card_no']);
    }

    public function render()
    {
        return view('gate-in-out::edit');
    }
}
