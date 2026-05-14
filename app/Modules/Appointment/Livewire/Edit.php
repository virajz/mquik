<?php

namespace App\Modules\Appointment\Livewire;

use App\Concerns\CanQuickAddCustomer;
use App\Modules\Appointment\Models\Appointment;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\CustomerVehicleMaster\Models\CustomerVehicleMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\WorkshopDepartmentMaster\Models\WorkshopDepartmentMaster;
use Flux\Flux;
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

    public ?int $editingId = null;

    /** Display-only — generated server-side after first save. */
    public ?string $appointment_no = null;

    public string $appointment_at = '';

    public string $channel = Appointment::CHANNEL_PHONE_CALL;

    public ?int $customer_id = null;

    public ?int $customer_vehicle_id = null;

    public ?int $service_type_id = null;

    public ?int $workshop_department_id = null;

    public ?int $assigned_advisor_id = null;

    public ?int $assigned_technician_id = null;

    public bool $requires_pickup = false;

    public ?string $pickup_address = null;

    public ?string $pickup_contact_phone = null;

    public string $status = Appointment::STATUS_PENDING;

    public ?string $notes = null;

    public function mount(?Appointment $appointment = null): void
    {
        if ($appointment && $appointment->exists) {
            $this->load($appointment);

            return;
        }

        // New appointment defaults: tomorrow at 10:00.
        $this->appointment_at = now()->addDay()->setTime(10, 0)->format('Y-m-d\TH:i');
    }

    protected function load(Appointment $a): void
    {
        $this->editingId = $a->id;
        $this->appointment_no = $a->appointment_no;
        $this->appointment_at = $a->appointment_at?->format('Y-m-d\TH:i') ?? '';
        $this->channel = $a->channel;
        $this->customer_id = $a->customer_id;
        $this->customer_vehicle_id = $a->customer_vehicle_id;
        $this->service_type_id = $a->service_type_id;
        $this->workshop_department_id = $a->workshop_department_id;
        $this->assigned_advisor_id = $a->assigned_advisor_id;
        $this->assigned_technician_id = $a->assigned_technician_id;
        $this->requires_pickup = (bool) $a->requires_pickup;
        $this->pickup_address = $a->pickup_address;
        $this->pickup_contact_phone = $a->pickup_contact_phone;
        $this->status = $a->status;
        $this->notes = $a->notes;
    }

    protected function rules(): array
    {
        return [
            'appointment_at' => ['required', 'date'],
            'channel' => ['required', Rule::in(array_keys(Appointment::channels()))],
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('is_active', true)],
            'customer_vehicle_id' => [
                'required', 'integer',
                Rule::exists('customer_vehicles', 'id')->where(fn ($q) => $q->where('customer_id', $this->customer_id)->where('is_active', true)),
            ],
            'service_type_id' => ['nullable', 'integer', Rule::exists('service_types', 'id')->where('is_active', true)],
            'workshop_department_id' => ['required', 'integer', Rule::exists('workshop_departments', 'id')->where('is_active', true)],
            'assigned_advisor_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'assigned_technician_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('is_active', true)],
            'requires_pickup' => ['boolean'],
            'pickup_address' => ['nullable', 'string', 'max:1000', 'required_if:requires_pickup,true'],
            'pickup_contact_phone' => ['nullable', 'string', 'min:10', 'max:20'],
            'status' => ['required', Rule::in(array_keys(Appointment::statuses()))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function updatedCustomerId(): void
    {
        // Customer changed → the existing vehicle pick almost certainly belongs to the old customer.
        $this->customer_vehicle_id = null;
    }

    public function updatedRequiresPickup(bool $value): void
    {
        if (! $value) {
            $this->pickup_address = null;
            $this->pickup_contact_phone = null;

            return;
        }

        // Pre-fill the contact from the customer's phone if they're picked.
        if ($this->customer_id && $this->pickup_contact_phone === null) {
            $this->pickup_contact_phone = CustomerMaster::find($this->customer_id)?->phone;
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
        return CustomerMaster::query()
            ->where('is_active', true)
            ->orderBy('first_name')
            ->limit(200)
            ->get(['id', 'first_name', 'last_name', 'phone']);
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
    public function serviceTypes()
    {
        return ServiceTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
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

    public function save()
    {
        $this->authorize($this->editingId ? 'appointment.update' : 'appointment.create');

        $data = $this->validate();

        foreach (['pickup_address', 'notes'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = strtoupper($data[$k]);
            }
        }

        $isCreate = $this->editingId === null;

        if ($isCreate) {
            $appointment = Appointment::create($data);
            $this->editingId = $appointment->id;
            $this->appointment_no = $appointment->fresh()->appointment_no;
        } else {
            $appointment = Appointment::findOrFail($this->editingId);
            $appointment->update($data);
        }

        Flux::toast(
            text: 'Appointment '.$appointment->appointment_no.($isCreate ? ' created.' : ' updated.'),
            variant: 'success',
        );

        return redirect()->route('appointment.index');
    }

    public function render()
    {
        return view('appointment::edit');
    }
}
