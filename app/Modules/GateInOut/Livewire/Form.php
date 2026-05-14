<?php

namespace App\Modules\GateInOut\Livewire;

use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\GateInOut\Models\GateInOut;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $direction = GateInOut::DIRECTION_IN;

    public string $gated_date = '';

    public string $gated_time = '';

    public string $registration_no = '';

    public ?int $customer_vehicle_id = null;

    public ?int $customer_id = null;

    public string $source = GateInOut::SOURCE_MANUAL;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'direction' => ['required', Rule::in(array_keys(GateInOut::directions()))],
            'gated_date' => ['required', 'date_format:Y-m-d'],
            'gated_time' => ['required', 'date_format:H:i'],
            'registration_no' => ['required', 'string', 'max:20'],
            'source' => ['required', Rule::in([GateInOut::SOURCE_MANUAL, GateInOut::SOURCE_ANPR])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[On('gate-in-out:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        $now = now();
        $this->gated_date = $now->format('Y-m-d');
        $this->gated_time = $now->format('H:i');

        if ($id === null) {
            return;
        }

        $r = GateInOut::findOrFail($id);
        $this->editingId = $r->id;
        $this->direction = $r->direction;
        $this->gated_date = $r->gated_at?->format('Y-m-d') ?? $this->gated_date;
        $this->gated_time = $r->gated_at?->format('H:i') ?? $this->gated_time;
        $this->registration_no = $r->registration_no;
        $this->customer_vehicle_id = $r->customer_vehicle_id;
        $this->customer_id = $r->customer_id;
        $this->source = $r->source;
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

    public function save(): void
    {
        $this->authorize($this->editingId ? 'gate_in_out.update' : 'gate_in_out.create');

        $data = $this->validate();

        $data['gated_at'] = Carbon::parse($data['gated_date'].' '.$data['gated_time'].':00');
        unset($data['gated_date'], $data['gated_time']);

        $data['customer_vehicle_id'] = $this->customer_vehicle_id;
        $data['customer_id'] = $this->customer_id;
        $data['recorded_by_user_id'] = auth()->id();

        if ($this->editingId) {
            GateInOut::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Gate event #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $row = GateInOut::create($data);
            Flux::toast(text: 'Gate event '.$row->fresh()->gate_event_no.' recorded.', variant: 'success');
        }

        $this->dispatch('gate-in-out:saved');
        $this->resetForm();
        Flux::modal('gate-in-out-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->direction = GateInOut::DIRECTION_IN;
        $this->gated_date = '';
        $this->gated_time = '';
        $this->registration_no = '';
        $this->customer_vehicle_id = null;
        $this->customer_id = null;
        $this->source = GateInOut::SOURCE_MANUAL;
        $this->notes = null;
    }

    public function render()
    {
        return view('gate-in-out::form');
    }
}
