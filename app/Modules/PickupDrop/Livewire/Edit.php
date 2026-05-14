<?php

namespace App\Modules\PickupDrop\Livewire;

use App\Modules\Appointment\Models\Appointment;
use App\Modules\CourierCompanyMaster\Models\CourierCompanyMaster;
use App\Modules\CustomerMaster\Models\CustomerAddress;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\PickupDrop\Models\PickupDrop;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Pickup / Drop')]
class Edit extends Component
{
    public ?int $editingId = null;

    public ?string $pickup_drop_no = null;

    public string $direction = PickupDrop::DIRECTION_PICKUP;

    public ?int $appointment_id = null;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public string $scheduled_date = '';

    public string $scheduled_time = '';

    public string $address_choice = 'custom';

    public ?string $address = null;

    public ?string $contact_phone = null;

    public ?int $driver_employee_id = null;

    public ?int $vendor_courier_id = null;

    public string $status = PickupDrop::STATUS_SCHEDULED;

    public ?string $notes = null;

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
        $this->appointment_id = $p->appointment_id;
        $this->customer_id = $p->customer_id;
        $this->customer_vehicle_id = $p->customer_vehicle_id;
        $this->scheduled_date = $p->scheduled_at?->format('Y-m-d') ?? '';
        $this->scheduled_time = $p->scheduled_at?->format('H:i') ?? '';
        $this->address = $p->address;
        $this->contact_phone = $p->contact_phone;
        $this->driver_employee_id = $p->driver_employee_id;
        $this->vendor_courier_id = $p->vendor_courier_id;
        $this->status = $p->status;
        $this->notes = $p->notes;
    }

    /**
     * "Add from Appointment" flow — copy customer, vehicle, schedule and pickup
     * details from an existing appointment.
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

        if ($appointment->requires_pickup) {
            $this->direction = PickupDrop::DIRECTION_PICKUP;
            $this->address = $appointment->pickup_address;
            $this->contact_phone = $appointment->pickup_contact_phone;
        }
    }

    protected function rules(): array
    {
        return [
            'direction' => ['required', Rule::in(array_keys(PickupDrop::directions()))],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('is_active', true)],
            'customer_vehicle_id' => [
                'required', 'integer',
                Rule::exists('customer_vehicles', 'id')->where(fn ($q) => $q->where('customer_id', $this->customer_id)->where('is_active', true)),
            ],
            'scheduled_date' => ['required', 'date_format:Y-m-d'],
            'scheduled_time' => ['required', 'date_format:H:i'],
            'address' => ['required', 'string', 'max:1000'],
            'contact_phone' => ['nullable', 'string', 'min:10', 'max:20'],
            'driver_employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'vendor_courier_id' => ['nullable', 'integer', Rule::exists('courier_companies', 'id')->where('is_active', true)],
            'status' => ['required', Rule::in(array_keys(PickupDrop::statuses()))],
            'notes' => ['nullable', 'string', 'max:1000'],
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
            $this->address = $picked['full'];
        }
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

    public function save()
    {
        $this->authorize($this->editingId ? 'pickup_drop.update' : 'pickup_drop.create');

        $data = $this->validate();

        if (! $this->validateAssignment()) {
            return;
        }

        $data['scheduled_at'] = Carbon::parse($data['scheduled_date'].' '.$data['scheduled_time'].':00');
        unset($data['scheduled_date'], $data['scheduled_time']);

        foreach (['address', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        if ($isCreate) {
            $row = PickupDrop::create($data);
            $this->editingId = $row->id;
            $this->pickup_drop_no = $row->fresh()->pickup_drop_no;
        } else {
            $row = PickupDrop::findOrFail($this->editingId);
            $row->update($data);
        }

        Flux::toast(
            text: 'Pickup/Drop '.$row->pickup_drop_no.($isCreate ? ' created.' : ' updated.'),
            variant: 'success',
        );

        return redirect()->route('pickup-drop.index');
    }

    public function render()
    {
        return view('pickup-drop::edit');
    }
}
