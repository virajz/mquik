<?php

namespace App\Modules\CustomerMaster\Livewire;

use App\Modules\CustomerMaster\Models\CustomerMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $customer_type = 'walking';

    public string $phone = '';

    public ?string $alternate_phone = null;

    public ?string $email = null;

    public ?string $address = null;

    public ?string $city = null;

    public ?string $pincode = null;

    public ?string $aadhar = null;

    public ?string $pan = null;

    public ?string $date_of_birth = null;

    public ?string $notes = null;

    public bool $is_active = true;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'customer_type' => ['required', 'in:walking,loyal,corporate'],
            'phone' => ['required', 'string', 'min:10', 'max:20'],
            'alternate_phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:255'],
            'pincode' => ['nullable', 'string', 'size:6'],
            'aadhar' => [
                'nullable', 'string', 'size:12',
                Rule::unique('customers', 'aadhar')->ignore($this->editingId),
            ],
            'pan' => [
                'nullable', 'string', 'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/',
                Rule::unique('customers', 'pan')->ignore($this->editingId),
            ],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    #[On('customer-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $r = CustomerMaster::findOrFail($id);
        $this->editingId = $r->id;
        $this->name = $r->name;
        $this->customer_type = $r->customer_type;
        $this->phone = $r->phone;
        $this->alternate_phone = $r->alternate_phone;
        $this->email = $r->email;
        $this->address = $r->address;
        $this->city = $r->city;
        $this->pincode = $r->pincode;
        $this->aadhar = $r->aadhar;
        $this->pan = $r->pan;
        $this->date_of_birth = $r->date_of_birth?->format('Y-m-d');
        $this->notes = $r->notes;
        $this->is_active = $r->is_active;
    }

    public function save(): void
    {
        $data = $this->validate();

        // Capital typing on textual fields. Skip email, enums, dates, booleans, numeric strings.
        $skip = ['email', 'customer_type', 'date_of_birth', 'is_active', 'pincode', 'phone', 'alternate_phone', 'aadhar'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            CustomerMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Customer #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $record = CustomerMaster::create($data);
            Flux::toast(text: 'Customer #'.$record->id.' created.', variant: 'success');
        }

        $this->dispatch('customer-master:saved');
        $this->resetForm();
        Flux::modal('customer-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->customer_type = 'walking';
        $this->phone = '';
        $this->alternate_phone = null;
        $this->email = null;
        $this->address = null;
        $this->city = null;
        $this->pincode = null;
        $this->aadhar = null;
        $this->pan = null;
        $this->date_of_birth = null;
        $this->notes = null;
        $this->is_active = true;
    }

    public function render()
    {
        return view('customer-master::form');
    }
}
