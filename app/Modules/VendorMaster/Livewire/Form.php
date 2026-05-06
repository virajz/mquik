<?php

namespace App\Modules\VendorMaster\Livewire;

use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorTypeMaster\Models\VendorTypeMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $vendor_code = '';

    public string $name = '';

    public ?int $vendor_type_id = null;

    public string $phone = '';

    public ?string $alternate_phone = null;

    public ?string $email = null;

    public ?string $address = null;

    public ?string $city = null;

    public ?string $state = null;

    public ?string $pincode = null;

    public ?string $pan = null;

    public ?string $gstin = null;

    public ?string $bank_name = null;

    public ?string $bank_branch = null;

    public ?string $ifsc = null;

    public ?string $account_no = null;

    public ?string $account_holder = null;

    public int $credit_days = 0;

    public float $credit_limit = 0;

    public ?string $payment_terms = null;

    public bool $is_active = true;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'vendor_code' => ['required', 'string', 'max:30', Rule::unique('vendors', 'vendor_code')->ignore($this->editingId)],
            'name' => ['required', 'string', 'max:255'],
            'vendor_type_id' => ['required', 'integer', Rule::exists('vendor_types', 'id')->where('is_active', true)],
            'phone' => ['required', 'string', 'min:10', 'max:20'],
            'alternate_phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'pincode' => ['nullable', 'string', 'size:6'],
            'pan' => ['nullable', 'string', 'size:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/', Rule::unique('vendors', 'pan')->ignore($this->editingId)],
            'gstin' => ['nullable', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', Rule::unique('vendors', 'gstin')->ignore($this->editingId)],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
            'ifsc' => ['nullable', 'string', 'size:11'],
            'account_no' => ['nullable', 'string', 'max:30'],
            'account_holder' => ['nullable', 'string', 'max:255'],
            'credit_days' => ['integer', 'min:0', 'max:365'],
            'credit_limit' => ['numeric', 'min:0', 'max:99999999.99'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[On('vendor-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();
        if ($id === null) {
            return;
        }

        $r = VendorMaster::findOrFail($id);
        foreach (['vendor_code', 'name', 'phone', 'alternate_phone', 'email', 'address', 'city', 'state', 'pincode', 'pan', 'gstin', 'bank_name', 'bank_branch', 'ifsc', 'account_no', 'account_holder', 'payment_terms', 'notes'] as $k) {
            $this->{$k} = $r->{$k};
        }
        $this->editingId = $r->id;
        $this->vendor_type_id = $r->vendor_type_id;
        $this->credit_days = (int) $r->credit_days;
        $this->credit_limit = (float) $r->credit_limit;
        $this->is_active = $r->is_active;
    }

    public function save(): void
    {
        $data = $this->validate();

        $skip = ['email', 'vendor_type_id', 'phone', 'alternate_phone', 'pincode', 'account_no', 'credit_days', 'credit_limit', 'is_active'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            VendorMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Vendor #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $r = VendorMaster::create($data);
            Flux::toast(text: 'Vendor #'.$r->id.' created.', variant: 'success');
        }

        $this->dispatch('vendor-master:saved');
        $this->resetForm();
        Flux::modal('vendor-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->vendor_code = '';
        $this->name = '';
        $this->vendor_type_id = null;
        $this->phone = '';
        $this->alternate_phone = null;
        $this->email = null;
        $this->address = null;
        $this->city = null;
        $this->state = null;
        $this->pincode = null;
        $this->pan = null;
        $this->gstin = null;
        $this->bank_name = null;
        $this->bank_branch = null;
        $this->ifsc = null;
        $this->account_no = null;
        $this->account_holder = null;
        $this->credit_days = 0;
        $this->credit_limit = 0;
        $this->payment_terms = null;
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('vendor-master::form', [
            'vendorTypes' => VendorTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
