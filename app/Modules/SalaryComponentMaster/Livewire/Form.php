<?php

namespace App\Modules\SalaryComponentMaster\Livewire;

use App\Modules\SalaryComponentMaster\Models\SalaryComponentMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public ?string $code = null;

    public string $component_type = 'earning';

    public string $calc_method = 'fixed';

    public float $default_value = 0;

    public bool $is_taxable = false;

    public bool $is_active = true;

    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255',
                Rule::unique('salary_components', 'name')->ignore($this->editingId),
            ],
            'code' => ['nullable', 'string', 'max:20',
                Rule::unique('salary_components', 'code')->ignore($this->editingId),
            ],
            'component_type' => ['required', Rule::in(array_keys(SalaryComponentMaster::componentTypes()))],
            'calc_method' => ['required', Rule::in(array_keys(SalaryComponentMaster::calcMethods()))],
            'default_value' => ['numeric', 'min:0'],
            'is_taxable' => ['boolean'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[On('salary-component-master:edit')]
    public function load(?int $id): void
    {
        $this->resetForm();
        $this->resetErrorBag();

        if ($id === null) {
            return;
        }

        $record = SalaryComponentMaster::findOrFail($id);
        $this->editingId = $record->id;
        $this->name = $record->name;
        $this->code = $record->code;
        $this->component_type = $record->component_type;
        $this->calc_method = $record->calc_method;
        $this->default_value = (float) $record->default_value;
        $this->is_taxable = $record->is_taxable;
        $this->is_active = $record->is_active;
        $this->notes = $record->notes;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'salary_component_master.update' : 'salary_component_master.create');

        $data = $this->validate();

        // Workshop convention: capital typing on textual fields (skip enums/flags).
        $skip = ['is_active', 'is_taxable', 'component_type', 'calc_method'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        if ($this->editingId) {
            SalaryComponentMaster::findOrFail($this->editingId)->update($data);
            Flux::toast(text: 'Salary Component #'.$this->editingId.' updated.', variant: 'success');
        } else {
            $record = SalaryComponentMaster::create($data);
            Flux::toast(text: 'Salary Component #'.$record->id.' created.', variant: 'success');
        }

        $this->dispatch('salary-component-master:saved');
        $this->resetForm();
        Flux::modal('salary-component-master-form')->close();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->code = null;
        $this->component_type = 'earning';
        $this->calc_method = 'fixed';
        $this->default_value = 0;
        $this->is_taxable = false;
        $this->is_active = true;
        $this->notes = null;
    }

    public function render()
    {
        return view('salary-component-master::form');
    }
}
