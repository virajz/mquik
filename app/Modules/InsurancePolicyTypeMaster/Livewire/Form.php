<?php

namespace App\Modules\InsurancePolicyTypeMaster\Livewire;

use App\Modules\InsurancePolicyTypeMaster\Models\InsurancePolicyTypeMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public ?string $code = null;

    public float $default_pass_percent = 100;

    public bool $is_active = true;

    public ?string $notes = null;

    /**
     * Validation rules — defined as a method (not #[Validate] attributes)
     * so we can use Rule::unique with the editing record's ID for updates.
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255',
                Rule::unique('insurance_policy_types', 'name')->ignore($this->editingId),
            ],
            'code' => ['nullable', 'string', 'max:20',
                Rule::unique('insurance_policy_types', 'code')->ignore($this->editingId),
            ],
            'default_pass_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[On('insurance-policy-type-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $record = InsurancePolicyTypeMaster::findOrFail($id);
        $this->editingId = $record->id;
        $this->name = $record->name;
        $this->code = $record->code;
        $this->default_pass_percent = (float) $record->default_pass_percent;
        $this->is_active = $record->is_active;
        $this->notes = $record->notes;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'insurance_policy_type_master.update' : 'insurance_policy_type_master.create');

        $data = $this->validate();

        // Workshop convention: capital typing on textual fields.
        $skip = ['is_active', 'default_pass_percent'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            InsurancePolicyTypeMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Insurance Policy Type #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $record = InsurancePolicyTypeMaster::create($data);
            Flux::toast(text: 'Insurance Policy Type #'.$record->id.' created.', variant: 'success');
        }

        $this->dispatch('insurance-policy-type-master:saved');
        $this->resetForm();
        Flux::modal('insurance-policy-type-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = null;
        $this->default_pass_percent = 100;
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('insurance-policy-type-master::form');
    }
}
