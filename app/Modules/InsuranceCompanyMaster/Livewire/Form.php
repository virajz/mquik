<?php

namespace App\Modules\InsuranceCompanyMaster\Livewire;

use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:20')]
    public ?string $short_name = null;

    #[Validate('nullable|string|size:15')]
    public ?string $gstin = null;

    #[Validate('nullable|string|max:255')]
    public ?string $contact_person = null;

    #[Validate('nullable|string|max:20')]
    public ?string $phone = null;

    #[Validate('nullable|email|max:255')]
    public ?string $email = null;

    #[Validate('required|numeric|min:0|max:100')]
    public float $default_pass_percent = 100;

    #[Validate('nullable|string|max:1000')]
    public ?string $address = null;

    #[Validate('nullable|string|max:1000')]
    public ?string $notes = null;

    #[Validate('boolean')]
    public bool $is_active = true;

    #[On('insurance-company-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $record = InsuranceCompanyMaster::findOrFail($id);
        $this->editingId = $record->id;
        $this->name = $record->name;
        $this->short_name = $record->short_name;
        $this->gstin = $record->gstin;
        $this->contact_person = $record->contact_person;
        $this->phone = $record->phone;
        $this->email = $record->email;
        $this->default_pass_percent = (float) $record->default_pass_percent;
        $this->address = $record->address;
        $this->notes = $record->notes;
        $this->is_active = $record->is_active;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'insurance_company_master.update' : 'insurance_company_master.create');

        $data = $this->validate();

        // Workshop convention: capital typing on textual fields (skip email).
        $skipUppercase = ['email', 'default_pass_percent', 'is_active'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skipUppercase, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            InsuranceCompanyMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Insurance company #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $record = InsuranceCompanyMaster::create($data);
            Flux::toast(text: 'Insurance company #'.$record->id.' created.', variant: 'success');
        }

        $this->dispatch('insurance-company-master:saved');
        $this->resetForm();
        Flux::modal('insurance-company-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->short_name = null;
        $this->gstin = null;
        $this->contact_person = null;
        $this->phone = null;
        $this->email = null;
        $this->default_pass_percent = 100;
        $this->address = null;
        $this->notes = null;
        $this->is_active = true;
    }

    public function render()
    {
        return view('insurance-company-master::form');
    }
}
